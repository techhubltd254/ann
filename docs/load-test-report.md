# Load Test Report — kicctest.org

**Date:** 2026-08-17
**Tool:** k6 v2.2.0 (`infra/k6-load.js`)
**Origin:** 2 vCPU / 4 GB DO droplet (lon1), PHP 8.4-FPM, nginx, Redis, remote TiDB (eu-central-1), Cloudflare edge.

## Scenario mix
Each virtual user cycles through: homepage, county page, sector page, search, marketplace, exhibitions,
API `/api/counties`, API `/api/county-sector/{slug}/data`, and a CDN profile image.

## Results (before caching)

| Concurrency | Throughput | Avg latency | p95 | Errors |
|---|---|---|---|---|
| 300 VUs | ~20 req/s | 12.3 s | 20.4 s | 0% |
| 200 VUs (API-only) | 77 req/s* | 2.4 s | 6.2 s | ~0% (mostly 429s*) |

\* API-only runs saturate the per-IP rate limiter (originally 60 req/min) — a single k6 egress IP
gets throttled, not an origin failure.

## Root cause
- **2 vCPU origin, ~400 ms of work per live SSR request** → theoretical ceiling ~5-10 req/s of live rendering.
- FPM slowlog showed **no request exceeded 1 s** during load — requests are I/O-bound (TiDB round-trips),
  so the observed multi-second latencies are **queueing**, not slow execution.
- 40 FPM children on 2 vCPUs cause context-switch thrash; reducing to 16 *hurt* (fewer workers to absorb
  DB-wait concurrency) → restored to 40.

## Fixes applied (committed + deployed)
1. **`CachePublicResponse` middleware** — Redis full-response cache (60 s TTL) for public GET endpoints.
2. **Cached surface:** `/api/counties`, `/api/counties/{slug}`, `/api/counties/{slug}/sectors|weather`,
   `/api/national-hub`, `/api/exhibitions`, `/api/venues`, `/api/booths`, `/api/tickets/lookup`,
   `/api/county-sector/{county}/data`, and web **home / counties index / county show**.
   Sector, marketplace, search, booking remain live (they contain CSRF cart forms — caching would break them).
3. **`/api/counties` pagination** — was hard-coded to 20 of 47 counties; now returns all 47.
4. **API rate limit** raised 60 → 300 req/min/IP (public reads are cache-backed; limiter is per-user protection).
5. **FPM config template** (`infra/php-fpm/`): opcache with JIT, 16-child default documented as worse on 2 vCPU.

## Results (after caching)
- **91% cache-hit rate** on the cached surface; 0% errors.
- Cached pages served directly from Redis; live SSR tail (sector/search) still dominates p95.
- CDN: healthy (only worker per-IP 429s at high single-IP throughput — a test artifact).

## Scaling to 10K concurrent — required, not optional
A single 2-vCPU origin cannot serve 10K concurrent *live SSR*. Paths to reach it:
1. **Horizontal scale** — more droplets behind a load balancer (Cloudflare already fronts; add origin pool).
2. **Edge-cache the heavy SSR tail** — serve sector/marketplace/search HTML from Cloudflare cache
   (requires refactoring the cart POST to an API, or per-visitor cache-busting).
3. **Increase droplet size / dedicated DB** — TiDB serverless is shared; a dedicated cluster removes
   connection-limit pressure.
4. **Static/SSG homepage + API-driven SPA** for the public web (the Expo app already uses the API).

## Repro
```bash
k6 run infra/k6-load.js --stage "20s:100,40s:500,20s:0"
```