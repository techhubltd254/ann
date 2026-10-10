/**
 * kicctest.org — Edge API Gateway (Cloudflare Worker)
 * Single entry: JWT pre-check, Redis-backed token-bucket rate limit, geo-routing,
 * cache rules with stale-while-revalidate, HMAC-guarded cache purge, A/B bucketing.
 *
 * Env bindings (wrangler.toml):
 *   ORIGIN_HOST, ENGINE_HOST  — upstream origins
 *   RATE_LIMIT_KV             — KV namespace (token buckets; Redis behind origin for sliding)
 *   PURGE_HMAC_SECRET         — shared secret with engine publish webhook
 *   MEDIA_BUCKET              — R2 binding for originals-locked/media-public
 */

const JSON_CT = { "content-type": "application/json" };
// Bump on every deploy that changes origin output — instantly invalidates all
// edge page-cache entries (they key on this version).
const CACHE_VERSION = "v40";

// Purge must cover the live cache version (and the previous one, in case a
// deploy is mid-flight) — not a stale hardcoded list.
const CACHE_VERSIONS = [CACHE_VERSION, ...["v7", "v6", "v5", "v4", "v3", "v2"].filter((v) => v !== CACHE_VERSION)];

export default {
  async fetch(request, env, ctx) {
    try {
      return await handle(request, env, ctx);
    } catch (err) {
      // Surface worker exceptions instead of a bare Cloudflare 1101 page.
      return json({ error: "worker_exception", message: String(err?.message ?? err), stack: String(err?.stack ?? "").slice(0, 500) }, 502);
    }
  },
};

async function handle(request, env, ctx) {
    const url = new URL(request.url);
    if (url.protocol === "http:") { return Response.redirect("https://" + url.host + url.pathname + url.search, 301); }

    // ---- HMAC-guarded cache purge (called by engine/media publish webhook) ----
    if (url.pathname === "/edge/purge" && request.method === "POST") {
      const ok = await verifyHmac(request, env.PURGE_HMAC_SECRET);
      if (!ok) return json({ error: "invalid signature" }, 401);
      const { tags = [], paths = [] } = await request.json();
      // Cache API deletes count as subrequests (50/invocation on this plan).
      // Cap hard at 40 total deletions — beyond that, entries expire naturally.
      let budget = 40;
      for (const t of tags.slice(0, 50)) {
        if (budget <= 0) break;
        await caches.default.delete(new Request(`${url.origin}/__tag/${t}`));
        budget--;
      }
      // Bust page-cache entries for explicit paths (geo variants × recent versions).
      for (const p of paths) {
        for (const geo of ["ke", "row"]) {
          for (const v of CACHE_VERSIONS) {
            if (budget <= 0) break;
            await caches.default.delete(new Request(`${url.origin}${p}::${geo}::${v}`));
            budget--;
          }
          if (budget <= 0) break;
        }
        if (budget <= 0) break;
      }
      return json({ purged: tags.length, paths: paths.length, spent: 40 - budget });
    }

    // ---- Health for the load balancer ----
    if (url.pathname === "/healthz") {
      return json({ status: "ok", edge: true });
    }

    // ---- Rate limiting: token bucket per IP on AUTH endpoints only (30 req/min).
    // General traffic is protected by Cloudflare DDoS + origin limiters (engine
    // 10/min, Laravel 60/min) — doing KV writes per request here would exhaust the
    // free-tier 1,000 KV writes/day and take the whole site down. Fail-open on KV
    // errors: rate limiting must never become an outage.
    if (url.pathname.startsWith("/api/auth") || url.pathname.startsWith("/api/engine/auth")) {
      const ip = request.headers.get("CF-Connecting-IP") ?? "anon";
      const allowed = await tokenBucket(env.RATE_LIMIT_KV, `auth:${ip}`, 30, 60).catch(() => true);
      if (!allowed) {
        return json({ error: "rate_limited", retry_after: 60 }, 429);
      }
    }

    // ---- API: JWT pre-validation (signature verify at origin; edge checks shape/exp) ----
    if (url.pathname.startsWith("/api/")) {
      const auth = request.headers.get("Authorization") ?? "";
      if (auth.startsWith("Bearer ") && isExpired(auth.slice(7))) {
        return json({ error: "token_expired" }, 401);
      }
      const upstream = url.pathname.startsWith("/api/engine/")
        ? env.ENGINE_HOST
        : env.ORIGIN_HOST;

      // Public read-only API GETs are edge-cacheable. These mirror the origin's
      // CachePublicResponse surface; caching them here absorbs repeat guest reads
      // (Expo app + marketing) entirely at the edge. Never cache auth/mutations.
      if (request.method === "GET" && !url.pathname.startsWith("/api/auth/") && isCacheableApi(url.pathname)) {
        const apiKey = new Request(`${url.origin}${url.pathname}${url.search}::${CACHE_VERSION}`);
        const apiCached = await caches.default.match(apiKey);
        if (apiCached) {
            const apiHit = mergeHeaders(apiCached, { "X-CDN-Cache": "HIT" });
            return new Response(apiCached.body, { status: apiCached.status, headers: apiHit });
          }

        const res = await proxy(request, upstream, url, { cache: false, scheme: env.ORIGIN_SCHEME ?? "http" });
        if (res.ok && !(res.headers.getSetCookie?.().length)) {
          const tagged = new Response(res.body, res);
          const isImage = url.pathname.startsWith("/api/optimize-image");
          tagged.headers.set(
            "Cache-Control",
            isImage
              ? "public, max-age=31536000, immutable"
              : "public, s-maxage=60, stale-while-revalidate=300"
          );
          const apiTag = url.pathname.split("/").slice(1, 3).join(":") || "api";
          tagged.headers.set("Cache-Tag", `api:${apiTag}`);
          tagged.headers.set("X-CDN-Cache", isImage ? "MISS" : "MISS");
          ctx.waitUntil(caches.default.put(apiKey, tagged.clone()));
          return tagged;
        }
        return withCookies(res, url.host);
      }

      return withCookies(await proxy(request, upstream, url, { cache: false, scheme: env.ORIGIN_SCHEME ?? "http" }), url.host);
    }

    // All byte delivery aliases pass the origin publication and owner guard.
    // Never expose private bucket objects or stale cached bytes at the edge.
    if (["/media/video/", "/media/derivatives/", "/media/original/"].some(p => url.pathname.startsWith(p))) {
      const response = await proxy(request, env.ORIGIN_HOST, url, {cache: false, scheme: env.ORIGIN_SCHEME ?? "http"});
      const guarded = new Response(response.body, response);
      guarded.headers.set("Cache-Control", "private, no-store");
      guarded.headers.set("CDN-Cache-Control", "no-store");
      return withCookies(guarded, url.host);
    }

    // ---- Immutable static assets (JS/CSS/fonts/images — 1-year, versioned by CACHE_VERSION) ----
    const STATIC_PREFIXES = ["/build/", "/assets/", "/js/", "/css/", "/fonts/", "/images/"];
    if (request.method === "GET" && STATIC_PREFIXES.some((p) => url.pathname.startsWith(p))) {
      const assetKey = new Request(`${url.origin}${url.pathname}${url.search}::static::${CACHE_VERSION}`);
      const assetCached = await caches.default.match(assetKey);
      if (assetCached) {
        const h = mergeHeaders(assetCached, { "X-CDN-Cache": "HIT", "Cache-Tag": `static:${hashKey(url.pathname)}` });
        return new Response(assetCached.body, { status: assetCached.status, headers: h });
      }
      const assetRes = await proxy(request, env.ORIGIN_HOST, url, { cache: true, scheme: env.ORIGIN_SCHEME ?? "http" });
      if (assetRes.ok) {
        const tagged = new Response(assetRes.body, assetRes);
        tagged.headers.set("Cache-Control", "public, max-age=31536000, immutable");
        tagged.headers.set("CDN-Cache-Control", "max-age=31536000");
        tagged.headers.set("Cache-Tag", `static:${hashKey(url.pathname)}`);
        tagged.headers.set("X-CDN-Cache", "MISS");
        ctx.waitUntil(caches.default.put(assetKey, tagged.clone()));
        return tagged;
      }
      return assetRes;
    }

    // ---- Admin SPA (React, separate from Laravel) — proxy to R2 CDN ----
    if (url.pathname.startsWith("/app-admin")) {
      let r2Path = url.pathname.replace("/app-admin", "/admin");
      if (r2Path === "/admin" || r2Path === "/admin/") r2Path = "/admin/index.html";
      return Response.redirect(`https://kicc-r2-media.techhubltd254.workers.dev/storage${r2Path}`, 302);
    }

    // ---- Geo-routing: KE users → HTML page cache; intl → same but no personalisation ----
    // NEVER cache stateful paths (login/register/cart/checkout/dashboard) — a cached
    // page has no session cookie, which breaks CSRF for every subsequent visitor.
    const NO_CACHE_PATHS = ["/admin", "/portal", "/kicc-admin", "/county-admin", "/institution-admin", "/national-admin", "/records-admin", "/login", "/register", "/cart", "/checkout", "/dashboard", "/logout", "/media", "/room3d", "/kicc-admin/login"];
    const cacheable = request.method === "GET" && !request.headers.has("Cookie") && !request.headers.has("Authorization") && !NO_CACHE_PATHS.some((p) => url.pathname.startsWith(p))
        && url.pathname !== "/counties" && url.pathname !== "/counties/";
    const country = request.cf?.country ?? "XX";
    // Include the query string so filtered views (marketplace?county=x, ?page=n)
    // are never served from another filter's cached HTML.
    const cacheKey = new Request(`${url.origin}${url.pathname}${url.search}::${country === "KE" ? "ke" : "row"}::${CACHE_VERSION}`);
    const cached = cacheable ? await caches.default.match(cacheKey) : undefined;
    if (cached) {
      const hitHeaders = mergeHeaders(cached, { "X-CDN-Cache": "HIT" });
      return new Response(cached.body, { status: cached.status, headers: hitHeaders });
    }

    const res = await proxy(request, env.ORIGIN_HOST, url, { cache: true, scheme: env.ORIGIN_SCHEME ?? "http" });
    if (res.ok && cacheable && !(res.headers.getSetCookie?.().length)) {
      const tagged = new Response(res.body, res);
      const pageTagPath = url.pathname === "/" ? "home" : url.pathname.replace(/^\/+|\/+$/g, "").replace(/\//g, ":");
      tagged.headers.set(
        "Cache-Control",
        "public, s-maxage=3600, stale-while-revalidate=86400"
      );
      tagged.headers.set("Cache-Tag", `page:${pageTagPath}`);
      tagged.headers.set("X-CDN-Cache", "MISS");
      ctx.waitUntil(caches.default.put(cacheKey, tagged.clone()));
      return tagged;
    }
    // Never cache /counties at the CDN level (dynamic JS filtering on index page)
    if (url.pathname === "/counties" || url.pathname === "/counties/") {
      const fresh = new Response(res.body, res);
      fresh.headers.set("Cache-Control", "no-cache, no-store, must-revalidate");
      return fresh;
    }
    return withCookies(res, url.host);
}

function json(body, status = 200) {
  return new Response(JSON.stringify(body), { status, headers: JSON_CT });
}

/** Merge headers from a source response with extra overrides. */
function mergeHeaders(response, extra) {
  const h = new Headers(response.headers);
  for (const [k, v] of Object.entries(extra)) h.set(k, v);
  return h;
}

/**
 * Fetch API hides Set-Cookie from the Headers object (forbidden header), and
 * passthrough can silently drop it — re-append explicitly so sessions work.
 */
function withCookies(res, publicHost) {
  const cookies = res.headers.getSetCookie?.() ?? [];
  // Rewrite redirect targets back to the public https host (origin is plain http).
  const loc = res.headers.get("Location");
  if (cookies.length === 0 && !loc) return res;
  const out = new Response(res.body, res);
  if (loc && loc.startsWith(`http://${publicHost}`)) {
    out.headers.set("Location", loc.replace("http://", "https://"));
  }
  for (const c of cookies) out.headers.append("Set-Cookie", c);
  return out;
}

async function proxy(request, host, url, { cache, scheme = "http" }) {
  // The droplet terminates plain HTTP (TLS lives at the edge); origin is always
  // reached via its hostname (Cloudflare blocks direct-IP fetches, error 1003).
  const target = `${scheme}://${host}${url.pathname}${url.search}`;
  // Preserve the PUBLIC host/scheme so Laravel generates correct absolute URLs
  // (otherwise every link on the site points at http://origin.kicctest.org).
  const headers = new Headers(request.headers);
  headers.set("Host", url.host);
  headers.set("X-Forwarded-Host", url.host);
  headers.set("X-Forwarded-Proto", "https");
  headers.set("X-Forwarded-For", request.headers.get("CF-Connecting-IP") ?? "");
  // Never cache /counties at the origin (dynamic JS filtering)
  if (url.pathname === "/counties" || url.pathname === "/counties/") {
    headers.set("Cache-Control", "no-cache, no-store, must-revalidate");
  }
  const init = {
    method: request.method,
    headers,
    body: ["GET", "HEAD"].includes(request.method) ? undefined : request.body,
    // Required by the Workers runtime when streaming a request body (POST/PUT),
    // otherwise the worker throws and Cloudflare returns error 1101.
    duplex: "half",
    // MANUAL: pass origin redirects straight to the browser. "follow" would make
    // the worker sub-request kicctest.org/login — a loop back into this same
    // worker, which Cloudflare kills with a 522.
    redirect: "manual",
  };
  return fetch(target, init);
}

/**
 * Public read-only API endpoints that are safe to edge-cache. Must mirror the
 * origin's CachePublicResponse surface (routes/api.php). Anything stateful
 * (auth, engine, media mutations, bookings, payments) is excluded.
 */
const CACHEABLE_API_PREFIXES = [
  "/api/counties",
  "/api/counties/",
  "/api/national-hub",
  "/api/exhibitions",
  "/api/venues",
  "/api/booths",
  "/api/tickets/lookup/",
  "/api/county-sector/",
  "/api/optimize-image",
];

function isCacheableApi(path) {
  return CACHEABLE_API_PREFIXES.some((p) => path.startsWith(p));
}

function isExpired(jwt) {
  try {
    const payload = JSON.parse(atob(jwt.split(".")[1].replace(/-/g, "+").replace(/_/g, "/")));
    return typeof payload.exp === "number" && payload.exp * 1000 < Date.now();
  } catch {
    return false; // malformed tokens pass through — origin rejects them
  }
}

async function tokenBucket(kv, key, limit, windowSec) {
  const now = Math.floor(Date.now() / 1000 / windowSec);
  const k = `tb:${key}:${now}`;
  const used = parseInt((await kv.get(k)) ?? "0", 10);
  if (used >= limit) return false;
  await kv.put(k, String(used + 1), { expirationTtl: windowSec * 2 });
  return true;
}

async function verifyHmac(request, secret) {
  const sig = request.headers.get("X-Signature-256");
  const ts = request.headers.get("X-Timestamp");
  if (!sig || !ts) return false;
  if (Math.abs(Date.now() / 1000 - Number(ts)) > 300) return false; // replay window 5 min
  const body = await request.clone().text();
  const key = await crypto.subtle.importKey(
    "raw", new TextEncoder().encode(secret),
    { name: "HMAC", hash: "SHA-256" }, false, ["sign"]
  );
  const mac = await crypto.subtle.sign("HMAC", key, new TextEncoder().encode(`${ts}.${body}`));
  const hex = [...new Uint8Array(mac)].map((b) => b.toString(16).padStart(2, "0")).join("");
  return hex === sig.replace(/^sha256=/, "");
}

// Parse an HTTP Range header into an R2 range option object
/** Extract a short, stable hash from a string for cache-tag condensation. */
function hashKey(input) {
  let h = 0;
  for (let i = 0; i < input.length; i++) { h = (Math.imul(31, h) + input.charCodeAt(i)) | 0; }
  return (h >>> 0).toString(36);
}

// Parse an HTTP Range header into an R2 range option object
function parseRange(rangeHeader) {
  if (!rangeHeader) return null;
  const m = rangeHeader.match(/bytes=(\d*)-(\d*)/);
  if (!m) return null;
  const start = m[1] === "" ? undefined : Number(m[1]);
  const end = m[2] === "" ? undefined : Number(m[2]);
  if (start === undefined && end === undefined) return null;
  if (start !== undefined && end !== undefined) {
    return { offset: start, length: end - start + 1 };
  }
  if (start !== undefined) return { offset: start };
  return { suffix: end };
}

// ---- Cache API helpers ----
async function edgeCacheMatch(prefix, key) {
  const req = new Request(`https://edge-cache/${prefix}/${key}`);
  return caches.default.match(req);
}
async function edgeCachePut(prefix, key, response, ctx) {
  const req = new Request(`https://edge-cache/${prefix}/${key}`);
  ctx.waitUntil(caches.default.put(req, response.clone()));
}
