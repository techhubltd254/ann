# KICC Platform — Kotlin-First Roadmap

> Premise: 4 admin tiers — KICC, NATIONAL, COUNTY, EXHIBITOR. All four default to full privileges.
> KICC admin can delegate/dedicate (grant, scope per county/sector, revoke, expire) privileges to the others.
> Core engine in Kotlin. Laravel becomes legacy/seed only. Outsource list at the bottom.

## Phase 0 — Decide & Setup
- [x] Lock architecture: Kotlin backend (Spring Boot) as the single API for all 4 admin tiers
- [ ] Decide outsourced pieces (see Outsource list) — get quotes in parallel
- [x] Monorepo: `kicc-engine` (Kotlin), `kicc-web` (React admin, reuse existing UI), `kicc-mobile` (later)
- [x] Data migration tool: Kotlin importer ingests the Laravel SQLite dump on first boot (6,137 rows: 47 counties, 218 sectors, 3,557 sector entities, 72 booths); TiDB hosting is outsourced

## Phase 1 — Kotlin Core Engine (in-house)
- [x] Auth: JWT + refresh tokens, API gateway, OpenAPI docs — `/api/auth/login|refresh|logout`, `/api/me` (port 8091, swagger at `/swagger-ui.html`)
- [x] RBAC centerpiece: 4 roles (KICC/NATIONAL/COUNTY/EXHIBITOR), each defaulted to full privileges (KICC alone holds DELEGATE)
- [x] Delegation engine: KICC admin grants/revokes roles, scoped per county/sector/booth, with expiry; effective-permission resolver runs per request (revocation is immediate)
- [x] Delegation audit log (who granted what, when, revoked by whom)
- [x] Multi-tenancy: county context middleware, tenant-scoped queries (context + `/api/me` tenant; scoped data queries land in Phase 2)

## Phase 2 — Feature Parity (migrate from PHP)
- [x] County data CRUD API (tourism, hotels, farms, health, institutions, transport, culture, products) — `/api/data/*`, tenant-scoped, countyId smuggling blocked, publish toggle, audit logged; 6,137 rows imported from Laravel SQLite (47 counties, 218 sectors, 3,557 sector entities, 72 booths)
- [x] Exhibitor admin: booths, bookings, self-serve — `/api/bookings` (KICC-xxxxxx refs, VAT 16%, capacity held on booth, cancel restores capacity, exhibitors see own bookings only, can only cancel own PENDING)
- [x] Payments: provider adapters (mock active now; M-PESA Daraja STK push/query + Stripe PaymentIntent wired to env keys), revenue share 80/20, daily settlements scheduler — `/api/payments`, `/api/payments/{id}/verify|refund`, `/api/payments/settlements`
- [x] Offline sync protocol: HMAC-SHA256 signed push/pull, last-write-wins + content-hash skip, county-scope enforced server-side, bundle versions — `/api/sync/pull|push|bundles`
- [x] AI pipeline: pluggable recommendation provider — mock (rule-based, live) + Gemini (swaps in with GEMINI_API_KEY) — `/api/recommendations`
- [x] Real-time layer: SSE notifications stream `/api/notifications/stream` (token query param), in-app notifications on bookings/delegations, bell UI
- [x] Media pipeline: upload → storage adapter (local now, GCS next), thumbnails, public serve — `/api/media` + Media Library UI

## Phase 3 — Admin UIs
- [x] Point existing React admin at Kotlin API (kicc-web on Vite :5173 proxying /api → engine :8091)
- [x] Delegation console for KICC admin (4 roles × features matrix, per-county scope)
- [x] Exhibitor self-serve portal

## Phase 4 — Security & Ops (outsource-ready)
- [x] Security hardening (in-house): rate limiting on `/api/auth/*` (10/min/IP → 429, live-verified), CORS lockdown via `KICC_CORS_ORIGINS`, h2-console disabled by default (dev-only via `H2_CONSOLE_ENABLED`), BCrypt + stateless JWT + method security + tenant scoping + audit log
- [x] Security audit + pen test (round 1, in-house): 15 attack-surface checks live-tested → findings fixed (user-tier escalation blocked, error-body info leak, tokens moved out of localStorage into HttpOnly cookies — console-inaccessible, exhibitor USERS_MANAGE trimmed) — see `docs/security-audit-2026-07-31.md`
- [x] At-rest encryption (server side): H2 DB AES-encrypted (`CIPHER=AES`, keys via `KICC_DB_FILE_KEY`/`KICC_DB_USER_PASSWORD`), verified 0 plaintext strings in DB file
- [x] CI/CD + monitoring + backups: GitHub Actions workflow (engine test/assemble + web build), Spring Actuator health/info, H2 backups → `./data/backups` (startup + daily 02:05 + manual `POST /api/admin/backups`, keep 7), restore drill passed (47 counties/72 booths/437 attractions/3557 entities)
- [x] Disaster recovery runbook + restore drill — see `docs/dr-runbook.md`
- [x] At-rest encryption on mobile edge devices (SQLCipher) — via kicc-mobile (Phase 5); verified on-device (fully encrypted file, key rotation works)
- [ ] Security audit + pen test (round 2, external) — outsource
- [x] Production go-live checklist written — see `docs/prod-go-live.md` (secret rotation, TLS, off-host backups, media GCS, mobile release checks; actual go-live follows the external pen test)

## Phase 5 — kicc-mobile (field officers, offline-first)
- [x] Expo (RN 0.86, SDK 57) scaffold, Android/iOS target; login with tokens in SecureStore (keychain, not AsyncStorage)
- [x] SQLCipher at-rest encrypted local DB via op-sqlite (`encryptionKey`, dev key overridable via `EXPO_PUBLIC_DB_KEY`)
- [x] Offline sync client: HMAC-SHA256 (pure-JS, matches engine — pull signature verified byte-for-byte), pull w/ signature verification, signed push (200, applied=1), tamper 401, offline edit queue with localId/LWW
- [x] County data screens: dashboard w/ per-resource counts, offline editor (new/edit JSON, pending badge), push queue
- [x] On-device QA passed (Android emulator, API 35, headless): login/session persistence (SecureStore), pull 176 rows into SQLCipher, offline create → pending badge → pull preserves pending → push applied on engine (id=439 verified via API), offline EDIT flow (edit → pending → in-place engine update, no duplicates — `saveOffline` localId fix), sign-out/in, key rotation (PRAGMA rekey, new salt on disk), biometric unlock full lifecycle (enroll → toggle → boot gate → fingerprint unlock; cancel → locked screen → retry) — see `docs/security-audit-2026-07-31.md` §Mobile
- [x] Safe-area handling (edge-to-edge status-bar cutout; RN SafeAreaView insufficient → `react-native-safe-area-context` v5.7)
- [x] SQLCipher actually enabled: op-sqlite needs `"op-sqlite": { "sqlcipher": true }` in package.json, else `encryptionKey` is silently ignored and the DB stays plaintext (found via on-device check — DB file now verified fully encrypted, no plaintext SQL)
- Config: `EXPO_PUBLIC_API_URL` (default `http://10.0.2.2:8091` = Android emulator → host), `EXPO_PUBLIC_SYNC_SECRET` (default dev `kicc-sync-dev-secret-2026`)

## Outsource (2026) — decisions in `docs/outsource-2026.md`
- Hosting: TiDB Cloud Starter free tier (no Africa region) vs AWS Aurora Serverless v2 af-south-1 (≈$50–70/mo, residency) — recommended: Aurora if residency required, else TiDB free tier
- Security pen test: automated scan (Intruder/Detectify ≈$100–500/yr) now + manual engagement with a Kenyan firm (≈KES 100k–600k) before first county production cutover
- UI/UX design + React frontend polish
- DevOps/cloud infra (TiDB hosting, CI/CD)
- Legal/compliance review
- QA/testing load suite

## Phase 7 — Senior Review (gap analysis) — full findings in `docs/gap-analysis-2026.md`
- [x] Deep-dive gap analysis across 10 domains (e-commerce, command & control, permissions, complaints, bookings, payments, audit, analytics, inclusivity, scalability) — 13 cross-cutting findings C1–C13, W0–W3 backlog
- [x] W0: enforce delegation scopes (COUNTY/SECTOR/BOOTH) — ScopeResolver envelope + query-layer enforcement (data, sync, users, audit, delegation grant/revoke containment); 66 tests green, live-verified; see docs/security-audit-2026-07-31.md §S1
- [x] W0: settlement lifecycle — Settlement states PENDING→APPROVED→PAID/FAILED + `POST /api/payments/settlements/{id}/approve|pay|fail` (PAYMENTS_MANAGE, audited) + `GET /api/payments/statements` per-county revenue-share statements (80/20, delegation-envelope scoped); 71 tests green, live-verified
- [x] W0: audit hash-chain + audit login/sync — SHA-256 prev-hash chain on audit rows, LOGIN_SUCCESS/FAILED/BLOCKED/TOKEN_REFRESH/LOGOUT + SYNC_PULL/PUSH auditing, tamper-verify endpoint + startup integrity check; 83 tests green, live-verified
- [x] W0: complaint intake (entity + API + web form) — public KICC-CMP reference + tracking, SLA (P1 4h / P2 24h / P3 72h), status machine, admin queue/assign/notes, notifications
- [x] W0: off-host backup sink — nightly/startup/manual push to sink with shared key, key-guarded ingest endpoint + off-host storage, audited
- [ ] W1: control plane (heartbeat registry, kill switch), analytics module + dashboard, password policy/MFA, provider webhooks, pagination
- [ ] W2: delta sync, e-commerce orders/inventory, waitlist/cancellation/invoices (eTIMS), i18n + accessibility, SMS/USSD adapter
- [ ] W3: GCS/CDN media, metrics, load suite, MySQL/Postgres migration path

## Definition of Done (Delegation Feature)
- KICC admin: grant NATIONAL/COUNTY/EXHIBITOR roles to any user, scope to county/sector, set expiry, revoke anytime
- Effective permissions resolved at request time (role + scopes, no caching holes)
- Every delegation/revocation written to audit log, visible to KICC admin only
- Non-KICC admins cannot elevate themselves or others
- [x] **Mobile-only admin** (web admin scrapped for security): Users (create/deactivate, countySlug for COUNTY tier), Delegations (grant/revoke/expiry), Audit log, Media library (upload via photo picker + delete), Bookings & payments dashboard — privilege-gated from `me.privileges` (Users=USERS_MANAGE, Delegations/Audit=DELEGATE, Media=CONTENT_MANAGE, Bookings=BOOKINGS_MANAGE); verified on-device for KICC + county tiers
- [x] Admin QA fixes: media upload FormData (expo-file-system `File` — Expo 57 winter fetch drops `{uri}` parts), DELETE returns 204 + tolerant client parse, MediaView DTO alignment

## Phase 6 — Per-Org Deployment (county servers + distribution)
- [x] Configurable server URL in the app (SecureStore override; login screen + Dashboard Security; Reset → default) — one APK serves KICC / any county / national
- [x] County seed mode (`KICC_SEED_MODE=county` seeds only county@kicc.go.ke with the org's `COUNTY_SLUG` + exhibitor; random DB key/JWT on first boot; H2 AES-encrypted verified on-disk)
- [x] Release APK (self-contained, no Metro; `usesCleartextTraffic` for LAN servers via expo-build-properties; verified: standalone boot, login, pull 176 rows)
- [x] Engine fat jar (`bootJar`) + `start-county-server.sh` (port/data-dir/slug/secrets via env; backups beside data dir)
- [x] Distribution package `/tmp/kicc-dist` (server jar + launcher, mobile APK, README, county-server guide, prod-go-live, audit) — 184M, thumbdrive-ready
- [x] Verified live: second engine on :8092 seeded muranga-only (admin login 401), app pointed at it → dashboard "COUNTY · muranga"; reset → mother :8091 → "COUNTY · kilifi"
- [x] Exhibitor onboarding: engine module (`/api/onboarding/apply` public; applicants list/approve/reject behind USERS_MANAGE; approve provisions an EXHIBITOR user with a one-time random password; scoring from 5 qualification answers routes large/IT-ready orgs to OWN_SERVER (score ≥ 6) vs TEMPLATE; idempotent on email; audit ONBOARDING_APPLY/APPROVED/REJECTED) — 61 engine tests green, live-verified end-to-end (apply → review → provisioned login; score 0→TEMPLATE, 5→TEMPLATE, 11→OWN_SERVER)
- [x] Onboarding web: kicc-web repurposed to a public SPA — `/` landing, `/apply` 2-step form (details + qualification), `/result` route outcome, `/login` + `/review` (USERS_MANAGE-gated applicant review, approve/reject with provisioned credentials shown once); old admin pages removed; verified live through the Vite proxy (:5173)
