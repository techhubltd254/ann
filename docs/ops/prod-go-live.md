# KICC Platform — Production Go-Live Checklist

Based on findings in `docs/security-audit-2026-07-31.md` and `docs/dr-runbook.md`.
Walk every item with `docs/dr-runbook.md` and the outsource list in `kicc-roadmap.md` at hand.

## Secrets (rotate every dev default — F4)
- [ ] `JWT_SECRET` — generate: `openssl rand -hex 64` (never the `application.yml` default)
- [ ] `KICC_DB_FILE_KEY` + `KICC_DB_USER_PASSWORD` — H2 `CIPHER=AES` file encryption
- [ ] `KICC_SYNC_SECRET` — offline sync HMAC (also update kicc-mobile `EXPO_PUBLIC_SYNC_SECRET` and ship via env, not source)
- [ ] `KICC_CORS_ORIGINS` — exact origin allow-list (no `*`)
- [ ] `KICC_COOKIE_SECURE=true` — cookies must be HTTPS-only in prod
- [ ] M-PESA Daraja / Stripe keys — set real env keys, test STK push with a sandbox account first
- [ ] `GEMINI_API_KEY` — optional; recommendation provider falls back to rule-based mock

## TLS / Network
- [ ] Terminate TLS at the load balancer; engine behind it, no direct public port
- [ ] All four cookie/API paths verified over HTTPS (login sets `Secure` cookies; check `document.cookie` still empty in browser)
- [ ] Firewall: only 443 public; 8091 (engine), 5173 (web dev) internal only
- [ ] h2-console disabled (`H2_CONSOLE_ENABLED` unset) — already the default

## Data / Storage
- [ ] Run one full restore drill from a production-shaped backup (see `docs/dr-runbook.md`)
- [ ] Off-host backups: ship `./data/backups` (keep-7) to object storage daily; test a download+restore
- [ ] Media files: switch storage adapter to GCS (local adapter is dev-only); verify public URLs + signed paths
- [ ] Confirm the DB file on disk has zero plaintext strings (`strings` check, like the mobile M1 test)

## Runtime / Ops
- [ ] Actuator `/health` wired into the load balancer / uptime check
- [ ] Rate limit on `/api/auth/*` verified in front of prod (10/min/IP; tune if behind a shared NAT)
- [ ] Daily backup cron actually fired (check `./data/backups` timestamps after 02:05)
- [ ] Graceful shutdown + startup importer ordering (startup backup must not capture an empty DB)
- [ ] Log rotation + audit-log retention decided (who granted what, when — keep ≥ 1 year)

## Mobile (kicc-mobile)
- [ ] `EXPO_PUBLIC_API_URL` → prod HTTPS URL; `EXPO_PUBLIC_DB_KEY` replaced by SecureStore-generated key on first run (dev default must not ship)
- [ ] App signed with a release keystore (debug-signed APK is dev-only)
- [ ] Release build + smoke: login, pull, offline edit, push, key rotation on a physical device
- [ ] App attestation/pin-code fallback for devices without biometrics (field officers)

## Pre-cutover checklist
- [ ] External pen test (round 2, outsource) completed with no CRITICAL/HIGH open
- [ ] Full regression: `./gradlew.sh test` green (53 tests) + live smoke (login/me/refresh cookie-only, sync HMAC pull/push, media upload/serve/delete, SSE, exhibitor 403s)
- [ ] Web UI smoke via the SPA proxy against prod-staged engine
- [ ] Rollback plan: previous release image + `./data/backups` restore drill re-verified
- [ ] Runbook owners named for DR + on-call

## After cutover (first 72h)
- [ ] Monitor rate-limit 429s, auth failures, backup job success
- [ ] Verify one end-to-end delegation grant/revoke in prod and its audit entry
- [ ] Verify one offline push from a field device shows up on the server with correct `syncStatus`
