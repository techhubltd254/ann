# KICC Algorithm Layer — Production Reference Implementation

All 20 registered algorithms (repo audit Part 4) as ONE wired system.
Zero runtime dependencies (Python 3.11+ stdlib). Money is `Decimal`.
Every tunable lives in `core/config.py` (mirrors `config/kicc.php` convention).

## Million-user design, concretely
- **Async end-to-end** (`asyncio`) — I/O-bound reads scale horizontally.
- **Stateless per request** — replica count = CPU count (`docker compose --scale`).
- **TTL cache** on the hot read path (homepage stats/featured) — O(1) hits.
- **Idempotency keys** on every money-moving write (duplicate M-Pesa webhooks
  and client retries are no-ops) — durable mapping in `migrations/001_core.sql`.
- **Double-entry ledger** with hold/release primitives; `audit_sum() == 0`
  is a continuous integrity invariant, not a report.
- **Rate limiting** (token bucket per key) on the API surface.
- **k-anonymity gate** before any MCP data exposure.
- Ports (repo/cache/ledger/feed/flight/hotel/weather) are injectable —
  production adapters (asyncpg/Redis/Kafka/Amadeus) slot in without
  touching algorithm code.

## Run
    pip install pytest pytest-asyncio
    pytest tests/ -q          # 30+ tests, all algorithms
    python examples/demo.py   # full money chain demo

## Layout
    kicc_algorithms/core/          config, cache, ledger, events, idempotency, ratelimit, db
    kicc_algorithms/algorithms/    one module per algorithm (display, trust, vendors, pricing,
                                   analytics, billing, recommendations, correlation, payments,
                                   quality, pool, kyb, sponsorship, county, screening,
                                   itinerary, weather, anonymizer)
    kicc_algorithms/orchestrator.py  KiccPlatform — the single wiring point
    migrations/001_core.sql        durable schema (PostgreSQL/TiDB-compatible)
    Dockerfile / docker-compose.yml / .github/workflows/ci.yml
