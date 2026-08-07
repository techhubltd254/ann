# KICC ONE — Master Architecture (Best-of Combo)

> Consolidated from: `kicc-platform` (Laravel + engine + web + mobile), `kicc-admin`,
> `kicc-portable-admin`, `kenya-3d-platform`. Scope locked against `docs/BLUEPRINT.md`
> (BLUEPRINT.pdf source) and live data audit of TiDB + GitHub `techhubltd254/ann` + kicc.co.ke.

---

## 1. The Combo — what was picked and why

| Slot | Winner (source) | Why it won |
|---|---|---|
| **Public website backend** | `platform/` — Laravel 13 app (from kicc-platform root) | Fullest domain model (202 tables live in TiDB), all services (M-Pesa, Payments, n8n, Pipeline engines, MCP), Inertia+React, API. kicc-admin/portable-admin are derived copies — archived. |
| **Public 3D experiences** | `platform/public/3d/` (Three.js county map, sector explorer, booth viewer) | Proven, static, CDN-friendly. |
| **Admin app backend** | `engine/` — Kotlin/Spring Boot 3.5 (Java 21) | Already implements the exact 4-tier model: KICC / NATIONAL / COUNTY / EXHIBITOR, delegation w/ scopes + expiry, hash-chained audit, offline sync, settlements, 83+ tests, fat-jar distribution. |
| **Admin app frontend** | `admin-web/` (React 18 + Vite + Tailwind + shadcn kit) | Privilege-gated console; design unified with Figma prototype (`_archive/figma-prototype`). |
| **Admin app mobile** | `admin-mobile/` (Expo RN 0.86, SQLCipher, biometric) | Offline-first field admin. |
| **Media/AI pipeline** | `pipeline/` — Python workers (Wan2GP, Hailuo, Tripo3D, ROAD, DAv2/SBS/splat) + Laravel `PipelineService` engine registry | Both halves already exist; combined here behind one queue contract. |
| **Edge/CDN** | `edge/` — Cloudflare Workers + R2 | kicc-proxy pattern already serving R2 `kicc-media`. |
| **Distribution (manual install + OTA)** | `distro/` — fat jar + web bundle + Electron/installers + **new OTA update channel** | kicc-app "Mother Engine" pattern, extended with signed OTA updates. |
| **Data** | `data/` — 41/47 scraped counties + reference JSON + live TiDB `kicc` | Single source of truth. |
| **Automation** | `pipeline/agentic_loop` + `pipeline/n8n-workflows` | Observer→Decider→Actor→Learner. |

**Explicitly NOT carried forward:** `kicc-admin/` and `kicc-portable-admin/` (derived Laravel+Filament forks — superseded by the Kotlin admin app), `kenya-3d-platform/laravel-backend` (legacy public site), SkySQL (replaced by TiDB). All preserved read-only under `_archive/`.

---

## 2. System topology

```
                          ┌────────────────────────────────────────────┐
                          │           CLOUDFLARE (kicctest.org)         │
                          │  CDN cache · WAF/DDoS · TLS 1.3 · DNS       │
                          │  Worker: edge gateway (geo, auth pre-check, │
                          │  rate-limit, cache rules, A/B, purge)       │
                          └───────┬───────────────────────┬────────────┘
                                  │                       │
                    ┌─────────────▼──────────┐   ┌────────▼─────────┐
                    │  PUBLIC WEBSITE        │   │  R2 object store  │
                    │  Laravel 13 (FrankenPHP│   │  originals + HLS  │
                    │  or php-fpm container) │   │  ladders + .glb   │
                    │  kicctest.org          │   └──────────────────┘
                    └───────┬────────────────┘
                            │ reads/writes
              ┌─────────────▼──────────────────────────┐
              │  TiDB Cloud (MySQL wire) — `kicc` +     │
              │  `kicc_shard_0..3` (1024 partitions)    │
              └─────────────▲──────────────────────────┘
                            │ writes (admin edits)
        ┌───────────────────┴───────────────────────────┐
        │  ADMIN APP (manually installed per org)        │
        │  Kotlin engine :8091  +  admin-web (SPA)       │
        │  ┌───────────┬────────────┬─────────┬────────┐ │
        │  │ KICC      │ NATIONAL   │ COUNTY  │EXHIBITOR│ │
        │  │ (mother)  │ (national  │ ×47     │ (large  │ │
        │  │ full +    │ content,   │ scoped  │ corps   │ │
        │  │ DELEGATE  │ ministries │ county  │ only)   │ │
        │  └───────────┴────────────┴─────────┴────────┘ │
        │  OTA updater ◄── signed manifest on kicctest.org│
        └──────────────┬─────────────────────────────────┘
                       │ media jobs (upload 4K/8K, generate)
              ┌────────▼─────────┐        ┌──────────────┐
              │ PIPELINE WORKERS │───────►│ Redis queues │
              │ ffmpeg ladder ·  │        │ (cache, rate │
              │ Wan2GP · Hailuo  │        │ limit, jobs) │
              │ Tripo3D · ROAD   │        └──────────────┘
              └──────────────────┘
```

**Golden rule of the combo:** admins edit ONLY through the Kotlin admin app → TiDB is the
single source of truth → Laravel website reads and renders → Cloudflare caches at the edge →
publish actions purge/invalidate the edge cache. The Laravel Filament admin is retired.

---

## 3. Admin tiers (as specified)

| Tier | Scope | Backend | Notes |
|---|---|---|---|
| **KICC Admin** | Everything + DELEGATE (grant/scope/revoke/expire privileges) | `KICC_SEED_MODE=all` mother server | "Mother of all" — also the OTA update publisher & off-host backup sink (`/api/ops/backups/ingest`). |
| **National Govt Admin** | National content **independent of county sectors**: ministries, agencies, national exhibitions, national hub pages | `NATIONAL` role, national scope | Does not touch county sector trees. |
| **County Admin ×47** | Exactly one county's data (tourism, hotels, farms, health, institutions, transport, culture, products, sector media) | `COUNTY` role, county scope; `KICC_SEED_MODE=county COUNTY_SLUG=…` per-org server | Tenant scope enforced server-side (`ScopeResolver`); HMAC-signed sync to mother. |
| **Exhibitor Admin** | Own booths/bookings/media only — **large corporations only** | `EXHIBITOR` role; onboarding scoring routes large orgs (score ≥6) to OWN_SERVER | Smaller exhibitors use the public portal, not the installed app. |

**Manual install + OTA:**
- Install: `distro/KICC-Install.sh` (zenity GUI) / `.bat` / `.desktop` → fat jar + web bundle + launcher + systemd unit. No inbound ports required; app binds localhost and dials out TLS only.
- OTA: engine polls `GET https://kicctest.org/api/updates/manifest?channel=stable&current=<semver>` →
  `{version, url, sha256, signature(ed25519), minVersion, mandatory}` → download → verify signature + SHA-256 →
  atomic swap into `releases/<version>/` with symlink flip + automatic rollback on failed health check
  (`/api/health` 10 s after restart). County servers and mother update through the same channel;
  KICC admin can pin/exempt versions per org (kill switch via `mandatory=false` + pinned manifest).

---

## 4. Media pipeline — "upload 4K/8K → appears on site, without killing load speed"

The admin's core job is editing website content. The pipeline guarantees a 4K/8K master
**never** reaches a browser directly.

```
Admin app upload (tus resumable, chunk 8 MB, ≤2 GB cap)
  → engine MediaModule stores ORIGINAL to R2 `kicc-media/originals/…` (never public)
  → job enqueued (Redis stream `media:jobs`, idempotency key = asset sha256)
  → transcode worker (ffmpeg):
       probe (codec/duration/dimensions) → reject >20 min or non-video
       HLS ladder: 1080p@4.5M · 720p@2.8M · 480p@1.4M · 360p@0.8M (H.264 main)
       + VP9 .webm (or AV1 when worker has libsvtav1) for <video> fallback
       + poster.webp (1280w) + blur placeholder (32w, base64)
  → 3D assets (Tripo3D/ROAD jobs): .glb + Draco/Meshopt, decimated LODs, KTX2 textures
  → derivatives registered (`media_derivatives`), checksums verified
  → PUBLISH step (explicit admin action):
       • row becomes `ready`, attached to county/sector slot
       • engine fires outbound webhook → Laravel `/api/media/publish` (HMAC-SHA256)
       • Laravel warms origin URL + calls Cloudflare purge-by-tag (`county:{slug}`, `sector:{slug}`)
Website render:
  <video> HLS.js w/ native HLS fallback, `preload="none"`, muted autoplay+loop only in
  viewport (IntersectionObserver), poster + blur-up, `sizes`-aware; headers
  `Cache-Control: public, s-maxage=86400, stale-while-revalidate=604800`
```

**Size guardrails (the "can't upload blindly" part):**
- Per-asset budget: original ≤ 2 GB; derived web ladder total ≤ 150 MB; hero videos ≤ 8 MB initial load (poster + first segment).
- Per-page media budget: ≤ 1.2 MB critical (poster/hero), everything else lazy (`loading="lazy"`, `preload="none"`).
- 3D: `.glb` only, Draco/Meshopt required, LOD switch by devicePixelRatio, lazy R3F `Suspense`.
- Uploads exceeding caps are **queued for re-encode, not rejected** — the admin sees progress via SSE/polling (skeleton loader → progress bar), matching the async UX spec.

---

## 5. Enterprise standards mapping (all 11 components)

| # | Requirement | Implementation in this combo |
|---|---|---|
| 1 | **Webhooks** | HMAC-SHA256 + timestamp (±5 min) + nonce (Redis `SET NX`, 24 h TTL) + idempotency keys; JSON-schema-validated payloads; retries with exponential backoff (1s→2s→…→15 min, 8 attempts, DLQ). Existing: sync HMAC (engine), n8n fires (Laravel). New: media publish webhook engine→Laravel. |
| 2 | **API Gateway** | Cloudflare Worker (`edge/kicctest-gateway.js`) as the single entry: JWT pre-validation, Redis-backed token-bucket rate limiting, schema transforms, routing `/api/*`→engine/Laravel, `/media/*`→R2. |
| 3 | **Load Balancer** | Cloudflare L7 LB: TLS 1.3 termination, HTTP/2→HTTP/1.1 to origin, `/healthz` active checks (Laravel + engine both expose DB+Redis connectivity), least-connections steering, failover to standby origin. |
| 4 | **Edge Functions** | Workers: geo-routing (KE vs intl), header normalization, A/B bucketing via cookie, edge auth checks, cache-tag purge endpoint (HMAC-guarded). |
| 5 | **CI/CD** | `.github/workflows/ci-cd.yml`: lint (PHPStan/ESLint/ktlint) → unit+integration tests (Pest/JUnit/Vitest) → SAST + dependency audit + Trivy → immutable artifacts tagged `ghcr.io/…:<git-sha>` + `server/kicc-engine-<sha>.jar` → blue-green deploy (two systemd slots + health-gated flip), canary = 1 county server first. |
| 6 | **CDN** | Cloudflare cache rules: static `s-maxage=604800`, media `s-maxage=86400, stale-while-revalidate=604800`, HTML `s-maxage=300, stale-while-revalidate=3600`; cache-tags per county/sector; origin shield on; purge pipeline wired to publish events. |
| 7 | **Serverless** | Workers (edge logic) + queue-driven workers for transcode/AI (no always-on GPU): Wan2GP/Hailuo/Tripo3D run on Vast.ai spot via `pipeline/deploy_vast.py` pattern; events on Redis streams. |
| 8 | **Env parity** | 12-factor: dev/stage/prod via env vars only; secrets in a manager (Doppler/Vault; **never** in repo — see §8 findings); staging seeded from anonymized prod dump (`infra/seed-staging.php` masks emails/phones/names). |
| 9 | **Relational DB** | **TiDB** (MySQL wire) fulfills this slot: B-tree + placement rules, Laravel migrations + Flyway-style SQL for engine, connection pooling (HikariCP engine-side, PDO persistent off + proxy), TiFlash read replicas for analytics, **PITR via TiDB `br`** — the PostgreSQL/PgBouncer requirement maps 1:1; Postgres remains a documented W3 migration path (`docs/ops/gap-analysis-2026.md`). |
| 10 | **Docker** | Multi-stage, non-root, distroless/Alpine: `infra/Dockerfile.platform` (composer→node→frankenphp runtime, `USER www-data`), `infra/Dockerfile.engine` (gradle→JRE 21 distroless, `USER app`), `infra/docker-compose.yml` (platform+engine+mysql8(TiDB-compat)+redis+minio(R2 S3-compat)+mailpit). |
| 11 | **NoSQL** | Redis: cache (LRU, `allkeys-lru`, TTLs, cache-aside in Laravel `Cache` + engine), sessions, rate-limit buckets, nonce store, job streams. Event/audit document log: append-only JSON lines → R2 (audit trail) — Mongo only if W3 needs it. |

---

## 6. Frontend & UX standards (binding for admin-web + platform UI)

- **Native semantics:** `<form>` + `<button>` only; `label htmlFor`, correct `type`, `name`,
  `autoComplete`, `required`, visible focus rings — no div-click handlers.
- **Derived state:** raw data in state; filtering/transforms computed in render or `useMemo`;
  no mirrored state, no sync `useEffect`.
- **Floating pill navbar:** glassmorphic pill, icon+label tabs left, high-contrast CTA right
  (Fitts), sliding highlight indicator, 44×44 targets, `role="navigation"`, arrow-key roving tabindex.
- **WCAG 2.2:** AA contrast minimum (AAA body), focus-visible, inline error recovery,
  auto-save indicators, ARIA on WebGL canvases, alt text.
- **UX laws:** Jakob (familiar patterns), Hick (≤5 choices/decision), Fitts (CTA in reach),
  Miller (chunked cards), Gestalt/proximity/similarity/common-region, top-aligned labels.
- **Visual system:** progressive-blur cards (0→80+ blur, gradient mask; dark mode swaps fill
  to #000), KICC palette (`brand/BRAND.md`), Inter/Manrope + Montserrat display.
- **Component rules:** skip option on onboarding; searchable combobox >10 options, radios 2–3;
  boxed fields w/ top labels; descriptive search placeholders; wizard steps + progress;
  radio=single/checkbox=multi; conversational CTAs; red only for destructive.
- Flagship implementation: `admin-web/src/shared/ui/FloatingNav.tsx` (see scaffold).

---

## 7. Schedules carried forward (now actually wired)

| Schedule | Job | Runner |
|---|---|---|
| Hourly | `travel:refresh-recommendations` (cold-market → n8n) | Laravel `schedule:run` cron on origin |
| Daily 02:00 | `recommendations:build` | Laravel scheduler |
| Daily 02:00 | engine settlements | `@Scheduled` (running jar) |
| Every 5 min | engine stalled-payment sweeper | `@Scheduled` |
| Daily 02:05 | engine backups → mother sink | `@Scheduled` |
| Daily 03:30 | engine refresh-token purge | `@Scheduled` |
| Every 15 min | complaint SLA sweeper | `@Scheduled` |
| Weekly Sun 08:00 | n8n trade promotion | n8n instance |
| 60 min | agentic loop cycle | systemd timer `infra/agentic-loop.timer` |

`infra/cron.d.kicc` + systemd units ship in this repo — the previous workspace had **zero** OS-level scheduling.

---

## 8. Data & repo audit — mistakes found (fix list)

**Live TiDB `kicc` (202 tables + 4×7 shard tables):**
1. `bookings` = 0, `screen_images` = 0 in production (media registry never run against prod).
2. `sectors` = 99 in TiDB vs 218 in the engine SQLite import — count mismatch to reconcile.
3. 6 counties have no scraped data: Busia, Embu, Garissa, Kirinyaga, Nairobi City, Taita-Taveta.
4. `venues` = 10 ✅ matches kicc.co.ke rooms; `counties` = 47 ✅; `shard_partitions` = 1024 ✅.

**Repo `techhubltd254/ann`:**
5. Binary bloat committed: ~10 `.docx`, ~7 `.pdf`, 431 KB zip, `__pycache__/`. → move to release assets; purge history.
6. No CI workflows beyond engine/web basics; no lint/security gates.

**Secrets (critical):**
7. Live credentials in plain text across the workspace: `tokens.txt`, `CREDENTIALS.md`,
   `deploy-package/server/application-prod.yml` (TiDB password), `scrips/generate_missing_sector_images.py` (OpenRouter key),
   `kenya-3d-platform/CREDENTIALS.md`. **Action: rotate all of them; they are excluded from `kicc-one` and gitignored.**
8. GitHub PAT + DB passwords were pasted in chat — treat as compromised, rotate after cutover.

**kicc.co.ke content errors** (see `brand/BRAND.md` §mistakes): Ugandan phone, gibberish board
text, UAT links in prod nav, broken stats counters.

**Revenue-share discrepancy:** BLUEPRINT 70/30 county/platform vs engine 80/20 KICC/county — decision needed (recommend: adopt 80/20 in blueprint docs).

---

## 9. Migration path (phased, no big-bang)

- **P0 (this repo):** consolidation ✅, secrets rotation, cron/systemd wiring, staging env on kicctest.org.
- **P1:** platform deployed to kicctest.org behind the edge worker; R2 media wired; publish webhook live.
- **P2:** admin app pilot — KICC mother + 2 counties (Kilifi, Mombasa — data is richest) installed manually; OTA channel proven with one signed update.
- **P3:** national tier onboarding (ministries/agencies content), exhibitor OWN_SERVER for first large corp.
- **P4:** remaining 45 counties via per-county bundles; 6 missing-county data backfill (`pipeline/scrapers`).

---

## 10. Deployment log — kicctest.org (2026-08-03)

**Status: LIVE.** Laravel platform on DO droplet `kicc-platform` (167.172.62.234, nginx+php8.4-fpm),
Kotlin engine on :8091, edge gateway worker on the apex + www custom domains, TiDB prod, R2 media.

Verified end-to-end: all 35 public GET routes → 200; full auth flow (session cookies → CSRF →
login → dashboard 200); register send-code → verify; `/api/counties`, `/api/counties/{slug}/sectors`,
`/api/venues`, `/api/exhibitions`, `/api/mcp/*`, `/api/engine/health`, `/api/updates/manifest`;
R2 derivatives served at `/media/derivatives/*` with `s-maxage`/`stale-while-revalidate`;
unsigned `/api/media/publish` → 401.

**Bugs found & fixed during cutover (all deployed):**

| # | Bug | Fix |
|---|---|---|
| 1 | Absolute URLs pointed at `http://origin.kicctest.org` | worker preserves Host/X-Forwarded-Proto; `trustProxies('*')` + `URL::forceScheme('https')` |
| 2 | `Rate limiter [api] is not defined` (500s on all API) | moved `RateLimiter::for('api')` from routes/api.php (never loaded under route:cache) to AppServiceProvider |
| 3 | 522 on all redirecting routes | worker was `redirect:"follow"` → sub-requested its own zone (loop protection) → `redirect:"manual"` |
| 4 | `/media/upload` 404 | worker R2 branch shadowed Laravel's `/media/*` → R2 only serves `/media/derivatives/*` |
| 5 | Login 419 for everyone | (a) worker cached `/login` (no session cookie) → NO_CACHE_PATHS + skip when Set-Cookie; (b) stale zone cache from the A-record era; (c) `cf.*` fetch options stripped Set-Cookie at subrequest level → removed; explicit `getSetCookie()` re-append |
| 6 | `DashboardController::middleware()` undefined | empty skeleton base controller → `Controller extends Illuminate\Routing\Controller` |
| 7 | register/send-code 500 | `phone_verification_codes.code` varchar(6) vs bcrypt hash → widened to 255 (migration `2026_08_03_000001`) |
| 8 | `users` insert failures | dual-schema NOT NULLs (`fullName`, `passwordHash`, `tier`, `active`) — fill both conventions |
| 9 | OTA: self-health-check impossibility, SIGPIPE watchdog kill, update loop | external `distro/server/ota-restart.sh` watchdog; `setsid`+file logging; symlink/pending guards |
| 10 | Spring Boot jar corruption via `zip -g` | never post-process fat jars — sign pristine bytes only |

**Data reconciliation (prod TiDB):** sectors 99 → 211 (112 inserts, HTML-entity/slug cleanup,
12 scraper-noise rows rejected); county backfill 41 → 47 covered (Busia via busiacounty.go.ke,
Embu/Kirinyaga via archive.org; Taita-Taveta partial — manual entry); `sector_entities` identified
as 100% synthetic placeholder data — deliberately NOT synced.

**Known gaps / next:** Origin is HTTP-only between edge and droplet (install Cloudflare Origin CA
cert → worker `ORIGIN_SCHEME=https` → Full Strict). Media rsync of legacy `storage/app/public`
(2.1 GB) in progress at cutover. `/api/products`, `/api/screens` have no routes by design
(products via MCP; screens are web-only). Worker rate limit is KV-based (eventual consistency —
backstopped by engine + Laravel limiters). Secrets pasted in chat remain to be rotated.

---

## 11. Admin app cutover — engine on TiDB (2026-08-03, later)

**Status: LIVE and proven.** The 4-tier admin app now shares the production TiDB with the website —
admin edits flow directly into site content.

**Working end-to-end:**
- `/admin` → 200 (React admin console SPA served by the engine)
- All 4 tiers authenticate via `/api/engine/auth/login`:
  `admin@kicc.go.ke` (KICC mother) · `national@kicc.go.ke` (NATIONAL) · `county@kicc.go.ke`
  (COUNTY, kilifi scope) · `exhibitor@kicc.go.ke` (EXHIBITOR) — engine-seeded
- Engine reads/writes the same TiDB `kicc` DB as Laravel: 47 counties visible via engine API
- **Edit-flow proven:** county admin created a Kilifi attraction via `/api/engine/data/attractions`
  → TiDB row carries BOTH column conventions → Laravel model read it back ("SITE SEES")
- **Scope enforcement proven:** kilifi admin posting `countyId:1` (mombasa) was server-side
  overridden to `countyId:3` — cross-county smuggling is blocked
- DELETE works (test rows cleaned)

**Dual-schema bridge (the core fix):** the shared tables carry both Laravel snake_case and engine
camelCase NOT NULL columns, and each system historically wrote only its own side — rows were
invisible to the other system, and inserts crashed on the other side's NOT NULLs. Fixes deployed:
- Engine `LegacyColumnMirror` (@MappedSuperclass) on all 9 county data entities + User + County
  mirrors: `@PostLoad` adopts legacy-only values, `@PrePersist/@PreUpdate` mirrors both conventions
- SQL backfill: users (fullName/passwordHash), all 8 county_* tables (countyId/isPublished) —
  211/211 attractions, 60/60 users mirrored
- Defaults added on engine-side NOT NULL columns so Laravel writes never fail
- Tier accounts repaired: roles corrected (were all COUNTY), `active=1`, exhibitor seeded by engine
- Boot log note: one FK constraint (`user_id`/`id` type mismatch) not created by ddl-auto — cosmetic,
  tracked for the schema-hardening pass

**Access (rotate after first login):** tier accounts share `KICC@Admin2026`; exhibitor `exhibitor@2026`.
The engine runs `--spring.profiles.active=prod` (TiDB) via systemd `kicc-engine.service`;
previous H2-only unit backed up at `/root/kicc-engine-unit-backup.txt`, previous jar at
`/opt/kicc/server/kicc-engine.jar.bak-*`.

---

## 12. NATIONAL tier — rebuilt professionally (2026-08-04)

**Was:** a KICC-minus-DELEGATE superuser (11 privileges incl. payments/users/marketplace),
unrestricted access to all 47 counties' data, and zero national-content endpoints. Unusable as designed.

**Now (least-privilege, verified end-to-end):**
- Privileges: `CONTENT_MANAGE, MEDIA_MANAGE, ANALYTICS_VIEW, REPORTS_VIEW` — nothing else
- `ScopedAccess.unrestricted` flag: **KICC-only**. NATIONAL's empty county envelope now means
  *no county access* (previously: all counties). Enforced in `dataCounties`, `requireInScope`,
  `resolveCountyId`, `createExhibition`. Verified: NATIONAL county write → 403, list → 403,
  users → 403, payments → 403; KICC + COUNTY flows unaffected (101/101 engine tests green).
- **New `NationalModule`** (`engine/.../national/NationalModule.kt`): full ministries + agencies
  management at `/api/national/*` — validated inputs (email/URL/#RRGGBB/code), unique slugs,
  soft-delete default (hard delete is KICC-only), agency→ministry referential checks, audit rows
  (`NATIONAL_MINISTRY_CREATE` etc.) on every mutation.
- **Admin console UI:** `/admin/national` page (`admin-web/src/pages/National.tsx`) — ministries
  + agencies tabs, search-filter, native-form semantics (labels/htmlFor/required/radios, 44px
  targets, visible focus), gated nav item (KICC/NATIONAL only). Deployed to `/opt/kicc/web`.
- **Edit-flow proven:** NATIONAL created "Ministry of Digital Economy" → TiDB → Laravel
  `Ministry` model reads it on the site.
- Data repaired: `counties.isActive/is_active` mirrors backfilled (47/47 active; Kilifi was
  wrongly inactive), test rows purged.
- Test suite updated: outdated tests asserted the old sloppy privileges — now assert the intended
  least-privilege behavior. Also fixed `GlobalExceptionHandler` to log 500 stacks (was silent).

---

## 13. Per-county install bundles (2026-08-04)

**All 47 county bundles built** at `dist/county-bundles/kicc-county-<slug>.tar.gz` (79 MB each).
Generator: `infra/make-county-bundle.sh <slug> "<Name>"` (rebuild any county on demand).

**Bundle contents:** engine fat jar · admin console build (`/admin`) · `start-county-server.sh`
(sourceable env, first-run secret generation into `county.env.local` — 0600) · `ota-restart.sh`
watchdog · systemd unit · `install.sh` (user creation, perms, service enable) · per-county README.

**Per-county config (`county.env`):** `COUNTY_SLUG`, `KICC_SEED_MODE=county` (seeds
`county@kicc.go.ke` scoped to the county + `exhibitor@kicc.go.ke`), OTA enabled on the `county`
channel (signed ed25519 manifest from kicctest.org, auto-rollback watchdog), nightly encrypted
backup → mother ingest (`BACKUP_SINK_URL=https://kicctest.org/api/engine/ops/backups/ingest`,
shared key in `infra/ota/backup_ingest_key.hex` — also set on the mother as `BACKUP_INGEST_KEY`).

**Verified on the Murang'a bundle (live boot, port 8096):** seeds county admin scoped to
`muranga` · login ✅ · admin console 200 ✅ · data write → `countyId=21` (muranga) ✅ ·
smuggle attempt with `countyId:1` (mombasa) **forced back to muranga** ✅ · county.env quoting
bug found & fixed (unquoted OTA restart command broke `source`).

**Fleet backup flow — proven end-to-end (2026-08-05):** county server booted → H2 startup backup
→ pushed to mother ingest → `200 OK` → file + audit row on the mother. Bugs found & fixed:
launcher sourced but never exported env vars to Java (`set -a` now used); ingest endpoint required
both a user session AND the key (redesigned to key-only + octet-stream body + 50 MB cap + audit
actor 0); ingest was missing from the SecurityConfig whitelist; `BACKUP TO` is H2-only (mother on
TiDB now skips local backups and relies on TiDB Cloud PITR); `sink-source` env binding added so
counties are identifiable by name in the offhost folder.

**Origin TLS (2026-08-04):** Let's Encrypt cert for `origin.kicctest.org` (certbot, auto-renew),
nginx 443 with HTTP/2 mirroring the :80 vhost, worker `ORIGIN_SCHEME=https` — edge→origin traffic
is now TLS end-to-end (verified in the nginx access log: Cloudflare IPs on 443/HTTP2). Worker
deploy tooling persisted to `infra/deploy/` (was repeatedly lost in /tmp).

---

## 15. Feature-completion pass (2026-08-07)

All built, deployed, and verified live unless marked EXTERNAL:

- **Holographic media:** local CPU Depth-Anything-V2 conversion → depth.png + wiggle.mp4 per image,
  stored on PC (`data/holographic/`) + R2; muranga sector tiles render holographic autoplay loops.
- **MFA:** TOTP (RFC 6238) in engine — enroll/enable/disable/verify, 5-min challenge tokens,
  mandatory for KICC tier, audited.
- **Disputes:** 2-tier (auto-refund rule <KES 5,000 / human queue ≥) — engine admin endpoints
  (`/api/disputes*`) + public buyer raise API.
- **Infra:** Redis on droplet (cache/session/queue switched); `pm.max_children` 5→40 (the real
  load bottleneck); edge HTML cache 1h + 1d SWR; ufw zero-trust (80/443 Cloudflare-only — direct
  origin blocked).
- **Account types:** +NIS (read-only oversight) +SCHOOL (institution exhibitor) → 8/8, least-privilege.
- **Vector search:** 2,257 OpenRouter embeddings (text-embedding-3-small), cosine-ranked
  `/api/search/semantic` (memory-bounded top-N heap), nightly rebuild.
- **Vendor grading:** Trust Score (verification/fulfillment/disputes/breadth/age → A–D) +
  Visibility Score (purchasable, Sponsored-labeled) — 51 vendors scored nightly.
- **Billing:** `billing:run` (cycles + invoices, 16% VAT) + `UsageMeter` service.
- **Ads:** 4 placements (featured/county-comarketing/referral/livestream-banner), budget-aware
  serve + click tracking, always-labeled Sponsored.
- **Anomaly detection:** 15-min sweeps (login/payment/refund spikes) → audit log.
- **Payments:** Stripe live driver + signed webhook; Airtel Money driver (stub-ready).
- **Courier:** adapter interface (Null/Partner) + HMAC tracking webhook.
- **Livestreaming:** channels + index/watch pages (2 demo channels).
- **Predictive:** nightly demand/trend rollups. **DBA:** weekly index audit (3 indexes added).
- **Observability:** engine `/api/metrics` (Prometheus counters).
- **Load test (k6):** honest result — tuned the pm bottleneck; remaining 429s are the rate
  limiter working as designed, not capacity. Single 2vCPU droplet + EU-region DB is the launch
  ceiling; scale path = resize droplet / add read replica region.
- **Compliance package:** `docs/compliance/` (KDPA data inventory, PCI SAQ-A posture, ISMS
  skeleton, external pen-test scope).

**EXTERNAL (credential/MOU/budget-gated — adapters + docs ready):** M-Pesa live keys, bank trust
account, Huduma/eCitizen + KRA + NTSA/KWS/ministry APIs, ISO 27001/PCI/SOC2 audits, external pen
test, Unity visitor app, physical 4D hardware.

---

## 14. Public site tile system — Murang'a launch state (2026-08-05)

**Sector tiles (county page):** all 8 economic sectors render as rich tiles — media slot
(`media('counties/{slug}/{route}.jpeg')` with graceful gradient+icon fallback when the image is
absent), entity-count badge, description, functional link to the sector page. Murang'a has all 8
tile images live (200). **Media convention for admins:** sector images land at
`storage/app/public/counties/{slug}/{route}.jpeg`; entity media at `counties/{slug}/{route}/{id}.jpeg`
— upload via the media library and tiles fill in automatically.

**Sector detail pages fixed:** were 404 for counties without a linked sector slug of the same name
(the lookup conflated scraped department slugs with the 8 public routes). Now route-driven against
the entity tables (`tourismAttractions` … `cultureSites`), with real entity cards: category badge,
description, location, entry fee, contact, pagination. Dead `href="#"` links removed.

**Government Departments:** proper informational tiles (name + description, sourced from official
county sites) replacing pills that incorrectly linked public users into the admin panel.

**National Government:** new public index `/national` — ministry tiles (color/logo, name,
description, agency count) linking to each ministry pavilion `/national/{slug}`; pavilion agency
tiles are functional (external `website` link when set). Nav (desktop/mobile/footer) gained
"National Gov".

Verified: all 8 muranga sector pages 200 with real entity cards; `/national` 200 with 11 ministry
pavilion links; all tile images 200; no dead links anywhere in the tile system.

**Brand enforcement (2026-08-05):** all off-brand colors removed from public views — teal `#14B8A6`,
forest `#2D6A4F`, sky `#0EA5E9`, amber `#F59E0B`, orange `#F97316`, purple `#a78bfa` and gold
variants mapped strictly onto the kicc.co.ke palette (`#901C1E` red, `#FFCD05` gold, `#11820B`
green, `#1890D7` blue, `#0A1024`/`#0B1E57` ink/navy, `#5A6480` slate, neutrals). Ministry brand
colors stored in the DB were mapped to the nearest official palette color (not stripped — each
ministry keeps its identity). Edge purge endpoint hardened with a subrequest budget (Cloudflare's
50-subrequest cap crashed bulk purges). Verified: 0 off-brand colors across home, counties,
muranga, national, marketplace, exhibitions, venues.

**Install on a county machine (as root):**
```bash
tar -xzf kicc-county-<slug>.tar.gz && cd kicc-county-<slug>
sudo ./install.sh && sudo systemctl start kicc-county
# console: http://localhost:8091/admin  (county@kicc.go.ke / county@2026 — change immediately)
```
Requires Java 21+. Bundles are offline-first: they run standalone and sync to the mother
(HMAC push/pull + nightly backup).

---

## 16. Real content injection + fake-data purge (2026-08-07)

**Source:** `kenya-3d-platform/SSSS/` — real Canon R5 photos + DJI 4K drone video (Murang'a county
content: waterfalls, tea highlands, Sagana rafting, community projects, farm aerials).

**Murang'a is now 100% real:** the 5 synthetic attractions (Beach/Coral Reef/etc. — geographically
impossible for a landlocked county) replaced with real, image-backed attractions:
Aberdare Forest Trails, Murang'a Waterfall, Sagana Rafting Experience, Tea Highlands Tour,
Murang'a Hills Viewpoint — each wired to its real photo (`counties/muranga/tourism/{id}.jpeg`),
plus a 6.4 MB web hero loop transcoded from the 1.9 GB drone master.

**Fake-data purge (prod TiDB):** 146 auto-generated generic attractions unpublished
("Garissa Beach", "Wajir Coral Reef"… same fake set per county) + 1,780 synthetic `sector_entities`
("Tea Estate - Mombasa 1") unpublished. All recoverable (soft-unpublish, not deleted).
Remaining published entities are real. Counties now show only genuine content until their admins add more.

**Media size discipline:** originals (15–45 MB photos, 1.9 GB video) never shipped to browsers —
web-sized derivatives (≈500 KB images, 6.4 MB hero loop) generated via the pipeline.

---

## 17. Murang'a real-content curation + 3D-everywhere (2026-08-07)

**Sector-accurate curation from SSSS:** the 900-photo roll clustered by capture-time into 8 shoots
(waterfall rappelling, river trek, forest canopy, tea estates, coffee/farm fields, garden retreat).
Curated best-frames became **9 real published entities** (7 tourism + 2 farms) with correct
categories, replacing all fakes. Full roll organized into `counties/muranga/library/<subject>/`
(web-sized) for the county admin to publish more.

**3D-everywhere:** every rendering image (sector tiles + all entity cards) now serves a
depth-wiggle holographic video (autoplay muted loop, poster fallback) — no bare stills.
Entity card holo path: `/media/derivatives/holo/{county}-{sector}-{id}/wiggle.mp4` on R2.
