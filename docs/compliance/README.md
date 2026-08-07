# Compliance Package — KICC Digital Economy Platform
> Prepared 2026-08-07. These are the *preparable* artifacts; the filings/audits themselves are external actions.

## 1. KDPA 2019 (Kenya Data Protection Act)
**Action required:** register as Data Controller + Data Processor with the Office of the Data Protection Commissioner (odpc.go.ke).

### Data inventory (what we hold, where, why)
| Data | Table(s) | Purpose | Lawful basis | Retention |
|---|---|---|---|---|
| Name, email, phone | users | account, auth | Contract | account life + 1 yr |
| National ID / KRA PIN | users.id_number / kra_pin | verification | Legal obligation (gov platform) | verification + 5 yr |
| Payment refs (no card PAN) | payment_intents, transaction_logs | payments | Contract/Legal | 7 yr (tax) |
| Location (county) | users.county_id | service | Contract | account life |
| Audit trail | audit_logs | security | Legitimate interest | ≥1 yr |
| Sync/media | media_assets | content | Contract | content life |

### Ready-to-file
- Data subject rights: access/erasure via account settings + admin request flow
- 72-hour breach notification plan: `docs/ops/dr-runbook.md` + NIS contact tree
- Privacy policy + cookie notice: publish at /privacy (Laravel content page)

## 2. PCI DSS v4.0 — SAQ A posture
We **never touch card data** (Stripe-hosted fields/Checkout only). That puts us in **SAQ A** — the lightest scope:
- All card entry on Stripe's hosted pages; we store only `provider_ref`
- No PAN stored, processed, or transmitted by our servers
- Action: complete SAQ A questionnaire once Stripe goes live; keep TLS 1.3 + the deployed CSP/security headers.

## 3. ISO 27001 / SOC 2 — ISMS skeleton
- Risk register: `docs/ops/gap-analysis-2026.md` (C1–C13 findings)
- Controls in place: MFA (TOTP, just deployed), audit hash-chain, at-rest AES (H2 SQLCipher/TiDB), TLS 1.3, least-privilege RBAC (4 tiers), off-host backups, zero-trust origin firewall (Cloudflare-only 80/443)
- External audit engagement is the external action.

## 4. External penetration test — scope doc
Targets: kicctest.org (edge worker, Laravel API), engine :8091 (auth, RBAC, sync HMAC, OTA signature path), payment webhooks (Stripe/courier HMAC), dispute flow, media pipeline.
Focus: auth bypass, IDOR across 47 county scopes, webhook replay, OTA signature forgery, injection on search/sync, rate-limit bypass.
Budget guidance: KES 100k–600k (Kenyan firm). This is the documented **go-live gate** (docs/ops/prod-go-live.md).
