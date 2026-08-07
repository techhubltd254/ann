# KICC Engine — Security Audit (Round 1)

Date: 2026-07-31 | Scope: engine (Spring Boot/Kotlin, :8091) + kicc-web | Result: 15 checks, 2 findings fixed + follow-up round (tokens/console, exhibitor privileges)

## Attack surface tested (all live against running engine)

| # | Test | Result | Notes |
|---|------|--------|-------|
| 1 | JWT tampered signature | 401 | rejected |
| 2 | JWT forged claims (privilege escalation, valid sig) | 401 | claims are trusted only via signature |
| 3 | JWT `alg=none` attack | 401 | rejected |
| 4 | Missing bearer token | 401 | rejected |
| 5 | Exhibitor → `/api/admin/users` | 200 → **403** | was by design; USERS_MANAGE trimmed from exhibitor defaults (F3) |
| 6 | Exhibitor → `/api/admin/delegations` | 403 | blocked |
| 7 | Exhibitor → `/api/admin/audit-logs` | 403 | blocked |
| 8 | County admin county-smuggling | blocked | county scope forced server-side (tests) |
| 9 | SQL injection (`' OR 1=1--`, UNION) | 200/empty | parameterized JPA, no injection |
| 10 | Media path traversal (`..%2f`, `%5c`) | 400/401 | blocked (key regex + URL normalization) |
| 11 | Malformed JSON body | was 500 + class-name leak | **FIXED** → 400 generic |
| 12 | Rate limiting on `/api/auth/*` | 10/min then 429 | verified |
| 13 | CORS (evil origin) | blocked | allow-list via `KICC_CORS_ORIGINS` |
| 14 | h2-console | 401 | disabled by default |
| 15 | Sync push tampered HMAC | 401 | verified (SyncFlowTest + live) |

## Findings

### F1 (CRITICAL, fixed)
Any tier (incl. EXHIBITOR) could create a **KICC-tier** user via `POST /api/admin/users` — full privilege escalation (DELEGATE + everything) in one call. Proven live (`evil@kicc.go.ke` created, then deactivated).
**Fix:** `UserAdminService.assertNoTierEscalation` — creating a KICC-tier user or any tier *higher than the actor's own* requires `DELEGATE`. Verified: exhibitor→KICC = 403, county→NATIONAL = 403, county→COUNTY = 200, KICC→KICC = 200.

### F2 (MEDIUM, fixed)
Malformed request bodies returned **500** and leaked internal class names (`ke.go.kicc.engine.auth.LoginRequest`).
**Fix:** `HttpMessageNotReadableException` handler → 400 "Malformed request body"; generic handler no longer echoes `ex.message`.

### F3 (FIXED)
Exhibitors defaulted to `USERS_MANAGE` (premise: all four tiers default to full privileges) — they could list all users, create peers, and previously (pre-F1-fix) KICC admins. Now exhibitors default to full privileges **minus** `USERS_MANAGE` and `DELEGATE` (KICC can still grant via delegation). Verified: exhibitor `/api/admin/users` → 403. User views never expose `passwordHash` (confirmed).

### F5 (CRITICAL, fixed) — tokens readable from browser console
Access/refresh tokens were stored in `localStorage` (`kicc_access`/`kicc_refresh`) — readable by any JS and trivially via DevTools console, and the SSE stream carried the token as a `?token=` URL query param (visible in logs/history).
**Fix:** token storage moved to **HttpOnly + SameSite=Strict cookies** (`kicc_access` 15 min, `kicc_refresh` 7 days). JS/console cannot read them (`document.cookie`/`localStorage` empty), tokens never appear in URLs (EventSource now cookie-authenticated). Bearer-header support kept for mobile/API clients. Verified live: login sets HttpOnly cookies, `/api/me` + refresh work cookie-only, logout clears both (→ 401 after), 3 new tests added (53 total green).

### F4 (INFO, accepted for dev)
Default `JWT_SECRET` and `KICC_DB_FILE_KEY` are dev defaults in `application.yml` — **must** be set in prod (env vars already supported). Flagged in runbook.

## Improvements shipped with this audit
- `DELETE /api/admin/users/{id}` — audited soft-deactivate (self-deactivate blocked)
- Manual backup endpoint `POST /api/admin/backups` (DELEGATE only, audited)
- Backup ordering fixed: startup backup now runs after data import (was capturing empty DB)
- HttpOnly-cookie token storage (see F5); `KICC_COOKIE_SECURE` env for TLS deployments

## Verification
- 53 engine tests green after fixes (incl. cookie auth, cookie refresh, exhibitor defaults)
- All checks re-run live post-fix; token flow verified through SPA proxy (:5173 → :8091)

---

## Mobile (Phase 5) on-device audit — Android emulator, API 35, headless

### M1 (CRITICAL, fixed) — SQLCipher never engaged; DB was plaintext
op-sqlite only builds with encryption when `"op-sqlite": { "sqlcipher": true }` is set in `package.json` — otherwise the `encryptionKey` option is silently ignored and the on-device DB is written **in plaintext** (verified: `CREATE TABLE` SQL readable in the file; header `SQLite format 3`).
**Fix:** added the `sqlcipher: true` flag, rebuilt (SQLCipher native lib), wiped the plaintext DB, re-synced. Verified post-fix: file header is random bytes (no SQLite magic), zero plaintext SQL strings in the first 8 KB, 176 rows synced/pulled normally.
**Lesson:** "encrypted" local DBs must be verified on disk, not assumed from API usage.

### M2 (FIXED) — safe-area: header controls dead on edge-to-edge
Android 15 forces edge-to-edge; the app drew its header under the status-bar/cutout region (`overrideNonDecorInsets` top = 128 px) — taps at the top of the screen were eaten by the system, so `← Back` / `+ New` were unreachable. RN's deprecated `SafeAreaView` applied no insets on Android.
**Fix:** `react-native-safe-area-context` v5.7 (`SafeAreaProvider` + `SafeAreaView edges={['top','bottom']}`); header moved below the inset and all header controls became tappable.

### Mobile QA passed (post-fixes)
- Login / session persistence across relaunch (tokens in SecureStore, biometric gate degrades gracefully — toggle hidden without biometric hardware)
- Pull: 176 county rows → SQLCipher DB; counts + last-sync UI correct
- Offline create: JSON editor → row with generated localId, `pending` badge
- Offline-first: pull preserves pending rows (`Push (1)` intact)
- Push: signed → engine `applied:1`; row visible via API pull (id=439, localId `25aa0164-…`, countyId=3)
- Key rotation: `PRAGMA rekey` — success alert + new salt in file header
- Sign out → login → sign in round-trip
- Type-check clean; APK builds `assembleDebug` (SQLCipher build 1m08s incremental)

### M3 (FIXED) — editing a synced row created a duplicate identity
`saveOffline` always generated a fresh `Crypto.randomUUID()` localId, discarding the row's existing localId — editing an already-synced row pushed it as a *new* row (would duplicate data; the engine's id-fallback happened to update in place, but identity was still rewritten).
**Fix:** `saveOffline` now keeps `row.localId` when present (`(row.localId as string) || Crypto.randomUUID()`). Verified on-device: edit row → localId preserved in the pending row → push → engine updates the same row in place (id stable, no duplicate, count unchanged).
**Note:** engine `applyRow` already preserves identity via `setId(incoming, existing)` and matches by localId then by id — mobile was the only side that broke identity.

### M4 (FIXED) — unknown API paths returned 500
`/api/sync/bundles/999` (unmatched path) returned 500 "Internal server error" — wrong status, and the generic handler was masking a `NoResourceFoundException`.
**Fix:** explicit handler → 404 "Not found". Test added (`unknownApiPathReturns404Not500`); 54 engine tests green.

### M5 (VERIFIED) — biometric unlock flow, full lifecycle on emulator
Enrolled a virtual fingerprint (API 35 `emu finger touch`), the "Biometric unlock" switch appears only when `hasBiometrics()` (absent → gracefully hidden), enabling it arms the boot gate: restart → system biometric prompt → fingerprint → dashboard (data intact). Cancel path → in-app "🔒 KICC locked" screen → retry → fingerprint → unlocked.

### M6 (FIXED) — media upload broken on Expo SDK 57 + delete response misparse
Expo SDK 57's Winter fetch only accepts `string | Blob | {bytes}` FormData parts — the legacy RN `{uri, name, type}` part shape throws `Unsupported FormDataPart implementation` (expo/src/winter/fetch/convertFormData.ts). Fixed with `expo-file-system`'s `File` (implements `Blob`); `npx expo install expo-file-system` + prebuild (native module). Also fixed the engine's DELETE /api/media returning 200+empty body (client `res.json()` blew up): engine now returns 204, client `request()` returns `undefined` for empty bodies; MediaFlowTest expectations updated to `isNoContent` (still 54 green). MediaView interface aligned to engine DTO (`contentType`/`sizeBytes`/`thumbUrl`). Verified on-device: picker → upload (engine: image/png row + thumb) → delete → media list 0, no client errors.

### VERIFIED — mobile-only admin (web admin scrapped)
All 5 admin sections live on-device for KICC tier: Users (create incl. COUNTY tier requires countySlug — engine validation surfaced inline; deactivate; Muranga user created id 33 and logs in), Delegations (grant → engine row → revoke w/ confirm → revokedAt set), Audit log (full trail incl. USER_CREATED/DELEGATION_*/MEDIA_*), Media library (upload + delete), Bookings & payments (stats cards). Permission gating confirmed: county tier sees only Users/Media/Bookings — Delegations & Audit hidden (DELEGATE-gated).
