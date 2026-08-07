# KICC Platform — Senior Review & Gap Analysis

Date: 2026-08-01 | Reviewed by: (senior dev + CEO lens) | Scope: engine (:8091, 64 endpoints / 13 controllers / 27 entities), kicc-mobile, kicc-web, ops docs
Method: full codebase inventory (endpoints, entities, RBAC resolution, schedulers, seed data) + frontend screen audit.

**Read this first — the 6 findings that matter most as a CEO:**

1. **Delegation scopes are decorative.** `Delegation.scopeType/scopeValue` (COUNTY/SECTOR/BOOTH) are stored and shown in UI but **never enforced** — every request resolves full privileges; only the user's own `countySlug` filters data. A "SECTOR-only" delegate can read the entire county. This is a legal/trust liability, not a bug report.
2. **Five privileges are dead code.** `MEDIA_MANAGE, REPORTS_VIEW, MARKETPLACE_MANAGE, SYNC_MANAGE, SETTINGS_MANAGE` are defined, granted to everyone by default, and referenced by zero endpoint checks. The permission matrix *looks* fine-grained and is, in reality, a wall poster.
3. **Money does not close the loop.** Settlements are created as `PENDING` every night and never advance; no payout happens; the 80/20 county revenue share is computed but never moves money. Refund/verify exist, but there is no reconciliation, no provider webhooks (polling only), no receipts/invoicing (Kenya eTIMS compliance), no dispute path.
4. **There is no customer-complaint surface anywhere.** No tickets, no disputes, no report/flag on listings, no feedback loop. The trust layer of a marketplace is absent.
5. **There is zero analytics.** One endpoint (`/api/recommendations`, mock heuristics). No KPIs, no dashboards, no reports, no exports — you cannot currently answer "which county is actually using this?" with data.
6. **No central command over your own fleet.** Backups are local-only (a burned office loses its DB), no heartbeat/registry of county servers, no kill switch, no device management, no broadcast, no remote wipe.

---

## 1. E-commerce (the marketplace promise)

**Current state:** `county_products` (name, description, category, price, unit, status, published) + `product_categories` + media library. Bookings (booths) are the only commerce flow that transacts. `MARKETPLACE_MANAGE` privilege defined but unused.

**Missing loops:**
| Gap | Evidence / impact |
|---|---|
| No cart, checkout, or orders — products are catalog-only | The national marketplace is a billboard; there is no buying flow |
| No inventory/stock (quantity, SKUs, variants, low-stock alerts) | Can't sell what you can't count; no seller controls |
| No orders entity or lifecycle (placed→paid→fulfilled→delivered) | No order management, fulfilment, or delivery zones |
| No reviews/ratings (product + seller) | The trust loop that powers marketplaces is absent |
| No promotions (discounts, coupons, bundles, flash sales) | No lever for county-led campaigns |
| No seller account model (seller profile, payout account, performance) | Exhibitor tier is an admin role, not a merchant account |
| No product images on the entity | `Product` has no image field; marketplace listings are text-only |
| No wishlist/save-for-later, no guest browse→buy path | Conversion funnel missing |
| No listing moderation/flag queue | Rogue or duplicate listings have no review path |

**Recommendations (P1–P2):**
- P1: Order entity + lifecycle; checkout must reuse the payment engine (see §6) — do not build a second payment path.
- P1: Stock on `Product` (quantityAvailable + reserved), atomic deduction mirroring the booth-capacity pattern, low-stock notification (reuse Notification system).
- P2: Reviews/ratings with verified-purchase gating (buyer must hold a PAID booking/order).
- P2: Coupons engine (code, scope=county, validity, cap) + order-level discounts.
- P2: Seller profile: payout details (M-PESA), performance score, marketplace terms acceptance.
- Wire `MARKETPLACE_MANAGE` to all of the above so the dead privilege becomes real.

---

## 2. Command & Control (multi-tenant fleet ops)

**Current state:** per-org engines (Phase 6), local backups (startup + daily 02:05, 7 kept), `/api/health`, Actuator health/info, rate limiting, CORS lockdown, sync bundles, onboarding that provisions users. **There is no control plane.**

**Missing loops:**
| Gap | Evidence / impact |
|---|---|
| No registry/heartbeat of county servers | KICC cannot see which counties are up, on which engine version, or how stale their data is |
| No kill switch / quarantine per tenant | A compromised county server cannot be isolated remotely |
| No feature flags / remote config | `SETTINGS_MANAGE` is dead; config is env-only at boot |
| No off-host backups | `KICC_BACKUP_DIR` is local disk; fire in a county office = total loss (DR runbook's RPO assumes the box survives) |
| No cross-server audit/log aggregation | Muranga's audit log is invisible to the mother server — governance gap |
| No device management | No device registry, no remote wipe for lost/stolen phones, no lockout broadcast |
| No broadcast/command channel | Notifications are per-server and in-memory; no way to reach all field officers at once |
| No server-to-server trust | The OWN_SERVER onboarding route ends at "an administrator will contact you" — no automated registration/join handshake |
| No release management | No version inventory, no staged rollout, no rollback story for county servers |

**Recommendations (P0–P1):**
- P0: **Off-host backup sink** — nightly push of backups from each county engine to the mother server (or object storage); update DR runbook RPO/RTO accordingly.
- P1: Mother-server control-plane module: heartbeat endpoint on county engines (engine version, DB size, last backup, pending sync queue, uptime), registry table + `/api/control/tenants` (SETTINGS_MANAGE-gated), KICC dashboard view.
- P1: Kill switch = tenant-level `disabled` flag enforced in `JwtAuthFilter` + sync gate (instant revocation of access, preserved data).
- P1: Feature flags table with per-tenant overrides (kill switch generalizes to flags).
- P2: Device registry (install ID → user → last-seen), remote wipe (revoke sync secret + clear signal), broadcast notifications persisted in DB (fixes the in-memory SSE loss too).
- P2: Automated tenant registration handshake when an OWN_SERVER applicant is approved.

---

## 3. Permissions (RBAC depth)

**Current state:** 4 tiers × 11 privileges, per-request resolution from DB (revocation immediate — good), delegation grants with role/scope/expiry, tier-escalation guard on user creation, self-deactivation blocked, audit of grants/revokes.

**Missing loops:**
| Gap | Evidence / impact | Status |
|---|---|---|
| **SCOPE/BOOTH/SECTOR scopes unenforced** | The delegation console implies control it doesn't deliver; COUNTY-level only filtering exists (user.countySlug) | **FIXED 2026-08-01** |
| Sync and data endpoints are county-wide | Even with scopes enforced, `/api/sync/pull` and `/api/data/*` would still leak all county rows to a sector-scoped delegate — scoping must be applied in the query layer, not the privilege layer | **FIXED 2026-08-01** (query-layer enforcement: county-data, sector-entities by sector, sync, exhibitions/venues/booths, user admin, audit log) |
| 5 dead privileges granted to everyone by default | All tiers (incl. exhibitors) hold MARKETPLACE_MANAGE/SETTINGS_MANAGE etc. — the matrix is fiction | open |
| No least-privilege defaults | Premise "all tiers default to full privileges" is unsafe post-launch; new tenants should start minimal and be granted | open (W2) |
| No password policy (length/complexity/history/rotation) | Only a 10/min rate limit protects login; no lockout, no breach handling | open (W1) |
| No MFA / step-up auth for sensitive ops | Refunds, delegations, deactivations, backups ride on a single password | open (W1) |
| No session hygiene tooling | No admin "sign out all devices", no token audit, no list of active sessions | open |
| Scope on delegation can't be validated (free-string scopeValue) | `scopeValue` has no FK — typos silently grant nothing/enforce nothing | **FIXED 2026-08-01** (COUNTY slug / numeric SECTOR-BOOTH validated at grant) |

**Resolution (2026-08-01) — enforcement semantics:** new `ScopeResolver` computes a per-user access envelope = home reach (COUNTY/EXHIBITOR → own county, KICC/NATIONAL → unrestricted) ∪ active delegation scopes (COUNTY/SECTOR/BOOTH). Union semantics: scopes extend reach (a kilifi county admin granted muranga can now actually operate in muranga — previously impossible), they never narrow base access; narrowing requires the W2 least-privilege defaults. Enforced in `CountyDataService` (list/get/create/update/delete/publish, exhibitions, venues, booths), `SyncService.resolveCounty`, `UserAdminService` (list/create/deactivate bounded to envelope), `AuditLogService` (DELEGATE-scoped viewers see only envelope activity), and `DelegationService` (grant/revoke containment — a scoped grantor cannot issue ALL or out-of-envelope scopes, closing the re-grant escalation vector). 66 engine tests green (5 new); live-verified on :8091 (muranga reach in → out after revoke; containment 403s; county-admin user scope).

**Recommendations (P0–P1):**
- P0: ~~Enforce scopes~~ **DONE 2026-08-01** — see resolution above. Remaining from this block: kill or wire the 5 dead privileges; define what each gates (media ops → MEDIA_MANAGE, sync → SYNC_MANAGE, control plane → SETTINGS_MANAGE, reports → REPORTS_VIEW, marketplace → MARKETPLACE_MANAGE).
- P1: Password policy (min 10 chars, 3-class, history 5, 90-day expiry) + account lockout (5 fails → 15 min) on `/api/auth/login`.
- P1: TOTP for KICC-tier + anyone holding DELEGATE/PAYMENTS_MANAGE; step-up (re-auth) for refund, delegation grant, deactivation.
- P1: Session registry (login/refresh/revoke audited, see §7) + "sign out everywhere" endpoint.
- P2: New-tenant default = minimal privileges; grants explicit (keep legacy default for existing seeds, migration note).

---

## 4. Customer complaints & trust layer

**Current state:** **Nothing exists.** No complaint/ticket/dispute/support entity, endpoint, or UI in any tier. Notifications are one-way (system→user). No report/flag on listings, no refund dispute path, no onboarding feedback.

**Missing loops:**
| Gap | Evidence / impact |
|---|---|
| No ticket system (category, priority, status, assignee, SLA) | No mechanism to receive or resolve complaints at all |
| No public channel (web form, in-app) | Complaints go to WhatsApp/email — unmeasurable, unowned |
| No payment/booking dispute workflow | A rejected refund has no escalation path |
| No moderation/flag queue for listings, products, media | The marketplace has no content-governance loop |
| No SLA timers / escalation matrix | Even with tickets, nothing forces resolution |
| No CSAT/feedback loop | No way to know if the platform serves its users |
| No complaint analytics | Can't measure response time, resolution rate, top categories |

**Recommendations (P0–P2):**
- P0: **Complaint entity + API** (public create with reference no., internal view/workflow gated by `BOOKINGS_MANAGE`/`USERS_MANAGE`), status lifecycle OPEN→IN_PROGRESS→RESOLVED→CLOSED with resolution note, priority + SLA timer (P1: 4h, P2: 24h, P3: 72h), assignee, internal notes (not visible to complainant), status-change notifications via existing Notification engine.
- P1: Public intake on kicc-web (`/complaint` form, reuses onboarding style) + in-app "Report a problem" in kicc-mobile.
- P1: Dispute flag on payments (disputed status, holds settlement inclusion) wired to the refund flow.
- P2: Report/flag on product/listing/booth/media rows → moderation queue view.
- P2: CSAT prompt on complaint resolution; complaint KPI dashboard (see §8).

---

## 5. Booking

**Current state:** solid core — atomic capacity (`bookedQuantity ≤ maxQuantity`), KICC-xxxxxx refs, VAT 16% fixed, statuses PENDING/CONFIRMED/PAID/CANCELLED/REFUNDED, owner-only cancel of own PENDING, capacity freed on cancel/refund, notifications, audit. Missing: the commercial edges.

**Missing loops:**
| Gap | Evidence / impact |
|---|---|
| No reservation hold with timeout | Cart→payment abandon leaves no recovery path; no re-releasing of stale PENDING bookings (they hold capacity forever) |
| No waitlist | Full booths lose demand forever; no waitlist→offer→claim flow |
| No exhibition-date gating | Nothing prevents booking an exhibition whose dates have passed |
| No reschedule/upgrade path | Only create + status change; no change-request workflow with reprice |
| No cancellation policy (fee tiers, windows) | CANCELLED is free and unlimited; no revenue protection |
| No invoices/receipts (PDF/email, eTIMS) | Bookings produce no documentable VAT-compliant invoice — §6 too |
| No payment reminders | PENDING bookings never nudge the payer; exhibitors pay late, silently |
| No multi-booth cart / batch booking | Per-booth single create only |
| No search/filter/pagination on listings | Admin list is uncapped, unfiltered |
| No no-show handling | CONFIRMED that never pays has no lifecycle rule (auto-expire) |
| Booking dates/modifiers absent | Start/end, add-ons, catering — none modelable |

**Recommendations (P1–P2):**
- P1: Expiry job (hourly @Scheduled): PENDING booking older than X hours (config, default 24) → CANCELLED (reason `auto-expired`), capacity released, audit + notification.
- P1: Exhibition-date guard in create (reject if endDate < today), plus `bookingsOpenAt/closeAt` window.
- P1: List endpoints: pagination + filters (status, exhibition, county, date range).
- P2: Waitlist entity + offer flow (waitlist → capacity freed → notify → 48h claim window).
- P2: Cancellation policy on Exhibition (free-window, fee %), partial refunds in payment engine (§6), invoice PDF generation.

---

## 6. Payments

**Current state:** 3 provider adapters (mock/M-PESA Daraja STK push+query/Stripe), initiate/verify/refund, statuses, daily settlements per provider, revenue share 80/20 **informational**, KES hardcoded. **2026-08-01:** settlement lifecycle landed (PENDING→APPROVED→PAID/FAILED via PAYMENTS_MANAGE endpoints `POST /api/payments/settlements/{id}/approve|pay|fail`, all audited) + per-county revenue-share statements (`GET /api/payments/statements`, delegation-envelope scoped, 80/20 split).

**Missing loops:**
| Gap | Evidence / impact |
|---|---|
| ~~**Settlement lifecycle dead-ends**~~ | **RESOLVED 2026-08-01** — states + approval + payout refs + failure path, audited |
| ~~**Revenue share never moves money**~~ | **RESOLVED 2026-08-01** — county statements per delegation envelope; money still moves via external transfer (county admin sees what is due) |
| No reconciliation | No comparison of our records vs provider statements; no GL/journal; no daily netting |
| No provider webhooks | M-PESA result polling + manual verify only; missed callbacks strand INITIATED payments forever |
| No idempotency keys on initiate | Double-tap / retry can create duplicate STK pushes |
| No payment expiry/retry | INITIATED never times out; no "pay again" flow after FAILED |
| No receipts/invoices (eTIMS) | Kenya Revenue Authority compliance gap for VAT 16% transactions |
| No partial refunds / disputes | §4; chargebacks unmapped |
| No per-tenant provider config | Till/paybill numbers are global config, not per-county |
| No payment links | Can't send a pay link via SMS/WhatsApp without the app |
| No fraud signals | No velocity checks, no daily caps, no risk queue |
| No installment/deposit plans | Large booth fees are all-or-nothing |
| No FX handling | `priceRange`/`product.price` units untyped; KES-only payments vs possibly-USD pricing |

**Recommendations (P0–P2):**
- P0: ~~**Settlement engine**~~ **DONE 2026-08-01** — states PENDING→APPROVED→PAID(+FAILED); approval workflow (PAYMENTS_MANAGE); payout records (provider transfer ref); per-county statement (bookings, fees, share) — this converts §8's county reports into money movement. Remaining: PDF/CSV export, settlement↔statement reconciliation job.
- P0: **Webhook endpoints** for M-PESA result + Stripe events with signature verification; fallback verify scheduler for stragglers; expire INITIATED > 15 min.
- P1: Idempotency key on initiate; per-county till/paybill via config table; payment link generation (STK to any phone).
- P1: Receipt/invoice generation (mirrors booking invoice, eTIMS-ready numbering).
- P2: Partial refunds with reason codes; fraud queue (velocity: >5 initiates/hour/user, cap by amount); installment flag on bookings.

---

## 7. Audit

**Current state:** append-only *by convention* (no DB enforcement), 19 action types, actor fields, DELEGATE-gated, capped 200.

**Missing loops:**
| Gap | Evidence / impact |
|---|---|
| **No tamper-evidence** | Rows are mutable JPA; no hash-chain, no trigger, no append-only enforcement — an attacker with DB access can rewrite history |
| Login/logout/refresh not audited | Can't answer "who was in the system when the leak happened" |
| Sync pulls/pushes not audited | Offline channels are the riskiest path (shared secrets) and are silent |
| No update actors on entities | No `lastModifiedBy` anywhere; "who changed this price?" is unanswerable |
| No paging/cursor | 200-row cap with no cursor — older history unreachable via API |
| No retention/archive policy | Log grows unbounded; no purge/archive job, no compliance schedule |
| No search/filter | By action/user/date only via limit param; no filters |
| No export | No way to hand auditors a CSV/signed dump |
| No restore-trace | A restored backup leaves no marker in the post-restore log |
| No anomaly alerting | No pattern detection (e.g., midnight deactivations) |

**Recommendations (P0–P1):**
- P0: **Hash-chain the audit log**: each row stores `prevHash`; verification endpoint; reject mutation of linked rows; startup integrity check logs a warning. Cheap, high-trust.
- P1: Audit login success/failure (rate-limit-compatible), refresh, logout, sync pull/push (county, entityType, row counts — no payload), backup restore events.
- P1: Add `updatedByUserId` to core entities (Booking, Payment, Product, Booth, Exhibition) via a shared base entity + actor-aware save paths.
- P1: Cursor pagination + filters (action, actor, date range, target); CSV export (REPORTS_VIEW-gated — finally a use for it).
- P2: Retention job (archive > N years to backup volume, purge from live), anomaly checks (failed-login spikes, off-hours DELEGATE grants) → notification to KICC.

---

## 8. Data analysis & reporting

**Current state:** one endpoint — `/api/recommendations` (mock heuristics; ANALYTICS_VIEW). Mobile dashboard shows per-resource row counts. **No analytics, no reports, no dashboards, no exports.**

**Missing loops:**
| Gap | Evidence / impact |
|---|---|
| No KPI endpoints | Adoption (active users/tier/county, last-login), bookings (by county/exhibition/status), money (GMV by provider, success/refund rates), data health (rows/county, sync staleness, pending pushes), onboarding funnel (apply→approve→provision→first sync→first booking) |
| No time-series | No daily/weekly trends; cannot see the platform grow |
| No county statements | §6: counties need their revenue-share statement; regulators need exports |
| No data-health monitoring | Stale syncs, unverified listings, orphan rows — invisible |
| No export/BI bridge | No CSV/XLSX, no read-only warehouse export, no scheduled reports |
| No recommendation feedback loop | Mock/Gemini scoring is never validated against outcomes (did the visitor convert?) |
| No product analytics | Marketplace metrics undefined (§1) |
| No complaint analytics | §4 KPIs (response time, backlog) unbuilt |
| `REPORTS_VIEW` privilege is dead | The permission exists; the feature doesn't |

**Recommendations (P1):**
- P1: `/api/analytics/*` module (ANALYTICS_VIEW): county KPI snapshot, adoption (users, active-30d, bookings), money (GMV, success rate, refund rate, pending settlements), data health (rows/type, last-sync age, pending push count), onboarding funnel. All derived from existing tables — no new writes.
- P1: Web analytics dashboard (kicc-web `/analytics`, admin-only) with county filter + date range; reuse the mobile dashboard's stat-card pattern.
- P1: Scheduled report jobs (daily 03:00): county statements + KICC executive summary as CSV/PDF into `/api/admin/reports` (REPORTS_VIEW) + email later.
- P2: Recommendation conversion tracking (recommendationId on booking/payment init) to close the AI loop.
- P2: Data-health alerts → Notification + control-plane view (§2).

---

## 9. Inclusivity

**Current state:** English-only everywhere, dark-theme JSON editors on mobile, 100MB release APK, no accessibility properties, web onboarding requires a browser + connectivity, everything assumes a smartphone owner with data.

**Missing loops:**
| Gap | Evidence / impact |
|---|---|
| No i18n (Swahili first) | The platform excludes the majority of field users who work in Swahili |
| No SMS/USSD channel | In Kenya, USSD/SMS is *the* inclusive channel (M-PESA itself): check availability, place/receive orders, payment notifications, complaint acknowledgment — none exist |
| No low-data mode | Full-export sync (§10) burns data on metered networks; no thumbnails-only, no text-only listings |
| No accessibility | No screen-reader labels (RN/web), no font scaling support, tiny touch targets in JSON editor, color-only status badges |
| No device tiering | 100MB APK + SQLCipher native libs exclude low-end devices; no lightweight PWA/web-client fallback |
| No literacy accommodations | Raw JSON editing assumes developer-level literacy; pictogram-driven capture is absent |
| No gender/equity visibility | `sponsorFunderTag`/`verificationOwner` exist but no reporting on women/youth-led businesses (SDG-aligned reporting) |
| No digital-skills onboarding | No training/support path for new county admins |
| No accessibility of money flow | Exhibitors without smartphones cannot sell or buy |

**Recommendations (P1–P2):**
- P1: i18n layer (web first, mobile second): Swahili + English with locale from `/api/me`/user settings; start with onboarding + complaint + dashboard strings.
- P2: SMS/USSD gateway adapter (Africa's Talking / Safaricom APIs): check-in (balance/status), order confirmations, payment receipts, complaint reference confirmations; low-bandwidth path for non-smartphone users.
- P1: Accessibility pass: `accessibilityLabel`/roles in RN, semantic HTML + contrast compliance on web, `allowFontScaling`, larger tap targets; document a checklist in the design skill files already in `docs/design/`.
- P2: Sync delta (§10) is the biggest data-inclusion win; media thumbnails-only mode; text-only listing mode.
- P2: Equity report: women/youth-led business counts by county (already tagged in data) — one analytics query, strong government value.

---

## 10. Scalability

**Current state:** federation is the right call (per-org engines, one H2 each). Within a tenant: single H2 file (AES), full-export sync, in-memory SSE, local media, no pagination, no caching, no load tests, backups local-only.

**Missing loops:**
| Gap | Evidence / impact |
|---|---|
| Full-export pull | O(n) bytes + full HMAC per sync; fine at 176 rows, terminal at 100k+ on rural networks; no delta negotiation |
| No pagination on most list endpoints | `audit?limit=200`, media 200, notifications 50; data/sync lists unbounded |
| SSE in-memory | Events lost on restart; no replay; backpressure none |
| Media on one disk | Local storage only; no GCS/S3 adapter despite roadmap, no CDN, no chunked uploads, synchronous thumbnail generation blocks the request thread |
| No caching layer | Public data (counties/sectors) re-fetched every render; recommendations uncached |
| Single-writer H2 | Concurrent county editors serialize on one file DB; MySQL driver present but unused |
| No instrumentation | Actuator health/info only; no Prometheus/metrics, no slow-query insight |
| No load/soak tests | Outsource list mentions QA load suite; nothing measured today |
| Backups local | §2 — off-host required; also 7-file retention only |
| Growth of audit/media unbounded | §7 retention job; media dedupe/archive |
| 100MB release APK | Distribution burden for county officers on shared devices |
| Refresh token store OK; but no token purge job | `refresh_tokens` grows forever; no cleanup of expired rows |

**Recommendations (P1–P2):**
- P1: **Delta sync**: `since` version (bundle version already exists as integer) — pull only rows with `syncedAt > since`; keep full export as bootstrap. Biggest single scaling win + data-inclusion win (§9).
- P1: Pagination + cursor everywhere (audit, media, notifications, bookings, payments, data lists).
- P1: Persisted notifications (DB-backed SSE with last-event-id replay) — fixes both restart loss and §2 broadcast.
- P2: GCS/S3 media adapter (credential-gated), async thumbnail pipeline, CDN in front of `/api/media/**`.
- P2: Metrics endpoint (Micrometer) + Prometheus scrape; slow-query and DB-size gauges feed §2 control plane.
- P2: Redis (or in-DB cache) for counties/sectors/recommendations; MySQL/Postgres migration path kept open (driver present).
- P2: Load suite (gatling/k6) against bookings+payments+sync to size the H2 ceiling; token purge job (daily).

---

## Cross-cutting systemic findings

| # | Finding | Severity | Fix anchor |
|---|---|---|---|
| C1 | Delegation scopes unenforced (security + legal) — **RESOLVED 2026-08-01** (§3 fix landed: ScopeResolver envelope + query-layer enforcement + grant containment, 5 new tests, live-verified) | Critical | §3 |
| C2 | Settlements dead-end, revenue share never pays out — **RESOLVED 2026-08-01** (§6 fix landed: settlement lifecycle PENDING→APPROVED→PAID/FAILED + PAYMENTS_MANAGE approval/payout/fail endpoints with audit trail + per-county revenue-share statements scoped by delegation envelope; 5 new tests, live-verified) | Critical | §6 |
| C3 | No tamper-evident audit; key events unlogged — **RESOLVED 2026-08-01** (§7 fix landed: SHA-256 prev-hash chain, login/refresh/logout/failed-login/block + sync pull/push audit rows, tamper-verify endpoint + startup integrity check; 4 new tests, live-verified) | High | §7 |
| C4 | No complaints/dispute surface — **RESOLVED 2026-08-01** (§4 fix landed: public complaint intake with KICC-CMP reference, SLA per category, status machine OPEN→IN_PROGRESS→RESOLVED→CLOSED, admin queue + notes + assignment, notification on status change; 5 new tests, live-verified) | High | §4 |
| C5 | Dead privileges = fictional permission matrix | High | §3 |
| C6 | Zero analytics/reporting | High | §8 |
| C7 | Backups local-only; no off-host copy — **RESOLVED 2026-08-01** (§2 fix landed: backup sink push (startup + nightly + manual) with shared-key auth, key-guarded ingest endpoint storing off-host copies, audit BACKUP_INGESTED; 3 new tests, live-verified) | High | §2 |
| C8 | No control plane (heartbeat/kill switch/device mgmt) | High | §2 |
| C9 | No provider webhooks; stranded INITIATED payments | Medium-High | §6 |
| C10 | Full-export sync; no delta | Medium-High | §10 |
| C11 | No i18n/accessibility/USSD | Medium (strategic) | §9 |
| C12 | No password policy / MFA / lockout | Medium-High | §3 |
| C13 | No pagination; unbounded growth (audit, tokens, media) | Medium | §10 |

---

## Prioritized backlog (CEO-sequenced)

| Wave | Items | Business rationale |
|---|---|---|
| **W0 — Trust & money integrity (immediate, 3–4 weeks)** | ~~C1 scopes enforced + tests~~ **DONE 2026-08-01**; ~~C2 settlement lifecycle + statements~~ **DONE 2026-08-01**; ~~C3 audit hash-chain + login/sync audit~~ **DONE 2026-08-01**; ~~C4 complaint intake (web + API)~~ **DONE 2026-08-01**; ~~C7 off-host backups~~ **DONE 2026-08-01** | Everything here is a liability if it goes wrong publicly: unenforced permissions, unpayable statements, rewritable history, no complaint channel |
| **W1 — Command & analytics (next quarter)** | Control plane (heartbeat/registry, kill switch); analytics module + web dashboard; password policy/MFA step-up; webhooks + payment expiry; pagination everywhere | Running a fleet blind and answering budget-holders without numbers blocks every partnership conversation |
| **W2 — Growth (next 6 months)** | Delta sync; e-commerce orders/inventory/reviews; waitlist + cancellation policies + invoices (eTIMS); i18n (Swahili) + accessibility pass; SMS/USSD adapter; recommendation feedback loop | These convert the platform from "an admin tool" into "a marketplace with a distribution channel" — and widen the addressable user base |
| **W3 — Scale hardening** | GCS/CDN media, metrics, load suite, MySQL/Postgres migration path, cache layer, token purge, device management | Do when >2 counties exceed ~50k rows or concurrent editors >20; H2 ceiling is the trigger |

**Suggested next concrete step:** implement W1 first (control plane + analytics) — they are the two findings that would embarrass the platform in any external review or audit; each is a contained change with existing test patterns (SettlementFlowTest-style integration tests).
