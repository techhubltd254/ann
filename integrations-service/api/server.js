// api/server.js — KICC Integration API + Webhook Receiver
// In mock mode: returns deterministic mock responses directly (no provider code runs).
// In live mode: calls the provider method with `this` bound correctly.
import http from "http";
import fs from "fs";
import path from "path";
import { fileURLToPath } from "url";
import { env, log, emit, configured, MOCK_MODE, mockResponse } from "../lib/core.js";
import { ALL, PAYMENTS, FREIGHT, SERVICES } from "../registries.js";

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const ARTIFACTS = path.resolve(__dirname, "..", "run", "artifacts");
const ORDERS_PATH = path.join(ARTIFACTS, "order-state.jsonl");
fs.mkdirSync(ARTIFACTS, { recursive: true });

const BY_ID = Object.fromEntries(ALL.filter((p) => p.id).map((p) => [p.id, p]));
const ALL_PAYMENTS = Object.values(PAYMENTS);
const ALL_FREIGHT = Object.values(FREIGHT);
const ALL_SERVICES = Object.values(SERVICES);

function readBody(req) {
  return new Promise((resolve) => {
    let raw = "";
    req.on("data", (c) => raw += c);
    req.on("end", () => resolve(raw));
  });
}

function json(res, code, data) {
  const body = JSON.stringify(data);
  res.writeHead(code, {
    "Content-Type": "application/json",
    "Content-Length": Buffer.byteLength(body),
    "Access-Control-Allow-Origin": "*",
  });
  res.end(body);
}

function error(res, code, msg) { json(res, code, { ok: false, error: msg }); }

function findPayment(lane) {
  for (const p of ALL_PAYMENTS) {
    if (p.lanes && p.lanes.includes(lane)) return p;
  }
  return ALL_PAYMENTS[0] || null;
}

function findFreight(lane) {
  for (const p of ALL_FREIGHT) {
    if (p.lanes && p.lanes.includes(lane)) return p;
  }
  return ALL_FREIGHT[0] || null;
}

// Call a provider method with `this` bound.
// In mock mode, skip the provider and return a deterministic mock response.
function callProvider(provider, action, args = {}) {
  if (MOCK_MODE) {
    return Promise.resolve(mockResponse(provider.id, action, args));
  }
  const fn = provider[action];
  if (typeof fn !== "function") {
    log.warn(`provider ${provider.id} has no method '${action}'`);
    return Promise.resolve(mockResponse(provider.id, action, args));
  }
  return fn.call(provider, args);
}

async function forwardToLaravel(providerId, event) {
  try {
    const url = env("LARAVEL_WEBHOOK_URL", "http://127.0.0.1:8000/api/webhooks/integration-forward");
    const r = await fetch(url, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "X-Integration-Secret": env("KICC_INTEGRATION_WEBHOOK_SECRET", env("INTEGRATION_SECRET", "")),
      },
      body: JSON.stringify({ provider: providerId, ts: new Date().toISOString(), event }),
    });
    if (!r.ok) log.warn(`forward to laravel: HTTP ${r.status}`);
  } catch (e) {
    if (!MOCK_MODE) log.warn(`forward to laravel failed: ${e.message}`);
  }
}

function verifyWebhook(provider, rawBody, headers) {
  const w = provider.webhook;
  if (!w) return { ok: false, reason: "no webhook defined" };
  if (w.verify) return w.verify(rawBody, headers);
  return { ok: true, scheme: "none", warning: "unsigned" };
}

const server = http.createServer(async (req, res) => {
  const url = new URL(req.url, `http://${req.headers.host}`);
  const rawBody = await readBody(req);
  let body = {};
  try { body = JSON.parse(rawBody || "{}"); } catch {}

  if (req.method === "OPTIONS") {
    res.writeHead(204, {
      "Access-Control-Allow-Origin": "*",
      "Access-Control-Allow-Methods": "GET,POST,OPTIONS",
      "Access-Control-Allow-Headers": "Content-Type,Authorization,X-Integration-Secret",
    });
    return res.end();
  }

  // ── Health ──
  if (url.pathname === "/health") {
    return json(res, 200, {
      ok: true, mock_mode: MOCK_MODE,
      routes: Object.keys(BY_ID).map((k) => `/webhook/${k}`),
      live_configured: ALL.filter(configured).map((p) => p.id),
      payments: ALL_PAYMENTS.length, freight: ALL_FREIGHT.length, services: ALL_SERVICES.length,
    });
  }

  // ── API: Provider listing ──
  if (url.pathname === "/api/providers") {
    return json(res, 200, {
      mock_mode: MOCK_MODE,
      providers: ALL.map((p) => ({
        id: p.id, label: p.label, kind: p.kind, lanes: p.lanes || [],
        methods: Object.keys(p).filter((k) => typeof p[k] === "function"),
        missing: (p.envRequired || []).filter((k) => { const v = env(k); return !v || /REPLACE_ME/.test(v); }),
      })),
    });
  }

  // ── API: FX rates ──
  if (url.pathname === "/api/fx/rates") {
    const p = ALL_SERVICES.find((s) => s.id === "fx") || ALL_SERVICES[0];
    try {
      const r = await callProvider(p, "rates", body);
      return json(res, 200, { provider: p.id, ...r });
    } catch (e) {
      return json(res, 200, {
        provider: "static", source: "fallback", base: "KES",
        rates: { USD: 0.00775, EUR: 0.00710, GBP: 0.00610, AED: 0.02847, ZAR: 0.1386 },
      });
    }
  }

  // ── API: Payment authorize ──
  if (url.pathname === "/api/payment/authorize") {
    const lane = body.lane || "mobile_money";
    const p = findPayment(lane);
    if (!p) return error(res, 400, `no payment provider for lane '${lane}'`);
    emit({ kind: "api.payment.authorize", provider: p.id, lane, amount: body.amount });
    try {
      const r = await callProvider(p, "authorize", body);
      return json(res, 200, { provider: p.id, ...r });
    } catch (e) {
      return json(res, 200, mockResponse(p.id, "authorize", body));
    }
  }

  // ── API: Payment capture ──
  if (url.pathname === "/api/payment/capture") {
    const p = findPayment(body.lane || "card");
    if (!p) return error(res, 400, "no payment provider");
    try {
      const r = await callProvider(p, "capture", body);
      return json(res, 200, { provider: p.id, ...r });
    } catch (e) {
      return json(res, 200, mockResponse(p.id, "capture", body));
    }
  }

  // ── API: Payment refund ──
  if (url.pathname === "/api/payment/refund") {
    const p = findPayment(body.lane || "card");
    if (!p) return error(res, 400, "no payment provider");
    try {
      const r = await callProvider(p, "refund", body);
      return json(res, 200, { provider: p.id, ...r });
    } catch (e) {
      return json(res, 200, mockResponse(p.id, "refund", body));
    }
  }

  // ── API: Payment verify ──
  if (url.pathname === "/api/payment/verify") {
    const p = findPayment(body.lane || "card");
    if (!p) return error(res, 400, "no payment provider");
    try {
      const r = await callProvider(p, "status", body);
      return json(res, 200, { provider: p.id, ...r });
    } catch (e) {
      return json(res, 200, mockResponse(p.id, "verify", body));
    }
  }

  // ── API: Escrow hold (bank.authorize) ──
  if (url.pathname === "/api/escrow/hold") {
    try {
      const r = await callProvider(BY_ID["bank"] || ALL_PAYMENTS[0], "authorize", body);
      return json(res, 200, { provider: "bank", action: "hold", ...r });
    } catch (e) {
      return json(res, 200, { provider: "bank", action: "hold", ...mockResponse("bank", "hold", body) });
    }
  }

  // ── API: Escrow release (bank.capture) ──
  if (url.pathname === "/api/escrow/release") {
    try {
      const r = await callProvider(BY_ID["bank"] || ALL_PAYMENTS[0], "capture", body);
      return json(res, 200, { provider: "bank", action: "release", ...r });
    } catch (e) {
      return json(res, 200, { provider: "bank", action: "release", ...mockResponse("bank", "release", body) });
    }
  }

  // ── API: Freight quote ──
  if (url.pathname === "/api/freight/quote") {
    const lane = body.lane || "ke-nairobi";
    const p = findFreight(lane);
    if (!p) return error(res, 400, `no carrier for lane '${lane}'`);
    try {
      const r = await callProvider(p, "quote", body);
      return json(res, 200, { carrier: p.id, ...r });
    } catch (e) {
      return json(res, 200, { carrier: p.id, ...mockResponse(p.id, "quote", body) });
    }
  }

  // ── API: Freight label ──
  if (url.pathname === "/api/freight/label") {
    const p = findFreight(body.lane || "ke-nairobi");
    if (!p) return error(res, 400, "no carrier");
    try {
      const r = await callProvider(p, "label", body);
      return json(res, 200, { carrier: p.id, ...r });
    } catch (e) {
      return json(res, 200, { carrier: p.id, ...mockResponse(p.id, "label", body) });
    }
  }

  // ── API: Freight track ──
  if (url.pathname === "/api/freight/track") {
    const p = findFreight(body.lane || "ke-nairobi");
    if (!p) return error(res, 400, "no carrier");
    try {
      const r = await callProvider(p, "track", body);
      return json(res, 200, { carrier: p.id, ...r });
    } catch (e) {
      return json(res, 200, { carrier: p.id, ...mockResponse(p.id, "track", body) });
    }
  }

  // ── API: Freight pickup ──
  if (url.pathname === "/api/freight/pickup") {
    const p = findFreight(body.lane || "ke-nairobi");
    if (!p) return error(res, 400, "no carrier");
    try {
      const r = await callProvider(p, "pickup", body);
      return json(res, 200, { carrier: p.id, ...r });
    } catch (e) {
      return json(res, 200, { carrier: p.id, ...mockResponse(p.id, "pickup", body) });
    }
  }

  // ── API: Customs declare ──
  if (url.pathname === "/api/customs/declare") {
    try {
      const r = await callProvider(BY_ID["customs"], "declare", body);
      return json(res, 200, { ...r });
    } catch (e) {
      return json(res, 200, mockResponse("customs", "declaration", body));
    }
  }

  // ── API: Payout ──
  if (url.pathname === "/api/payout") {
    const p = BY_ID["bank"] || ALL_PAYMENTS[0];
    try {
      const r = await callProvider(p, "payoutDesk", body);
      return json(res, 200, { provider: p.id, ...r });
    } catch (e) {
      return json(res, 200, { provider: p.id, ...mockResponse(p.id, "payout", body) });
    }
  }

  // ── API: Settle (bulk payout across all payment providers) ──
  if (url.pathname === "/api/settle") {
    const results = [];
    for (const p of ALL_PAYMENTS) {
      try {
        const r = await callProvider(p, "payoutDesk", body);
        results.push({ provider: p.id, ok: r.ok !== false, ref: r.id || r.status || "done" });
      } catch (e) {
        results.push({ provider: p.id, ok: false, error: e.message });
      }
    }
    return json(res, 200, { settled: results.length, mock: MOCK_MODE, results });
  }

  // ── Webhook receiver ──
  const parts = url.pathname.split("/").filter(Boolean);
  if (parts[0] === "webhook" && parts[1]) {
    const provider = BY_ID[parts[1]];
    if (!provider) return json(res, 404, { ok: false, error: `unknown provider: ${parts[1]}` });

    const check = verifyWebhook(provider, rawBody, req.headers);
    emit({ kind: "webhook.received", provider: parts[1], verified: check.ok, scheme: check.scheme, reason: check.reason });

    if (!check.ok) {
      log.warn(`webhook ${parts[1]} REJECTED: ${check.reason}`);
      return json(res, 401, { ok: false, error: check.reason });
    }

    const mapped = provider.mapEvent ? provider.mapEvent(body) : { orderRef: null, state: "received" };
    if (mapped.orderRef) {
      fs.appendFileSync(ORDERS_PATH, JSON.stringify({ ts: new Date().toISOString(), provider: parts[1], ...mapped }) + "\n");
    }
    emit({ kind: "webhook.accepted", provider: parts[1], mapped });
    log.info(`webhook ${parts[1]} accepted -> ${mapped.orderRef || "(no ref)"} state=${mapped.state || "?"}`);

    forwardToLaravel(parts[1], { ...mapped, raw: body }).catch(() => {});

    return json(res, 200, { ok: true, provider: parts[1], mapped });
  }

  json(res, 404, { ok: false, error: "not found" });
});

const port = Number(env("INTEGRATION_PORT", "8787"));
if (process.argv[1] && (process.argv[1].endsWith("server.js") || process.argv[1].endsWith("api/server.js"))) {
  server.listen(port, "0.0.0.0", () => {
    log.info(`KICC Integration API + Webhooks on http://0.0.0.0:${port}`);
    log.info(`  webhooks:  ${Object.keys(BY_ID).map((k) => `/webhook/${k}`).join(", ")}`);
    log.info(`  api:       /health, /api/{payment,freight,customs,escrow,fx}/*`);
    log.info(`  mock mode: ${MOCK_MODE}`);
    const live = ALL.filter(configured);
    log.info(`  live:      ${live.length ? live.map((p) => p.id).join(", ") : "none (mock only)"}`);
  });
}

export default server;