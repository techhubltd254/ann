# KICC Integration Layer

Every one of the 87 pipelines wired to a settlement chain, and every payment
method and freight lane routed to a named provider. **You only add API keys.**

```bash
cd integrations
cp .env.example .env             # leave MOCK_MODE=true and everything runs today
bash run/run_mock_e2e.sh         # the whole proof: order, webhook, 87 lines, tests, env map
node run/check_all_wired.mjs     # wiring audit: 87 pipelines x payments x freight
node run/run_e2e.mjs             # one order, end to end, with the step trace
node run/run_webhook.mjs         # signed + forged webhook callbacks against the receiver
node run/run_all_87.mjs          # all 87 pipelines through the same settlement chain
node run/env_report.mjs          # prints the exact vars you still have to add
node run/verify_endpoints.mjs --probe   # with real keys: reachability of each base URL
node webhooks/server.js          # the webhook receiver on :8787
```

## Layout

| Path | What it is |
|---|---|
| `.env.example` | **The only file you fill in.** Every var, grouped by provider, with where to get it |
| `config/providers.yaml` | Toggles + endpoint facts. `confirmed: true` means read from the provider's own docs |
| `config/routes.yaml` | Which provider serves which lane, plus the fee basis for all 87 pipelines |
| `lib/core.js` | env loading, logging, HTTP with retry + idempotency, signing, event log, ledger |
| `providers/payments/*.js` | stripe, flutterwave, paystack, mpesa, pesapal, bank |
| `providers/freight/*.js` | dhl, fedex, aramex, ocean (sea/FCL/LCL), sendy, posta |
| `providers/customs.js` | KRA iCMS declaration, EPC certificate of origin, KEBS permit |
| `providers/fx.js` | static rates, or CBK / bank feed |
| `pipelines/order-to-cash.mjs` | order -> pay -> escrow -> quote -> label -> customs -> track -> release -> payout |
| `pipelines/settle-to-desk.mjs` | drives all 87 pipelines through ingest -> verify -> match -> settle |
| `webhooks/server.js` | one receiver, verifies each provider's own signature scheme |
| `run/artifacts/` | `events.jsonl` (every call) and `ledger.csv` (every settled line) |

## How a provider is chosen

`config/routes.yaml` gives an ordered priority list per lane. The first provider
with real credentials wins; the rest are failover. With no credentials at all,
the first in the list runs in mock mode so the chain still completes.

## Verified against the providers' own docs

| Provider | Base URL | Auth | Source |
|---|---|---|---|
| DHL Express MyDHL | `https://express.api.dhl.com/mydhlapi` (+ `/test`) | HTTP BasicAuth | developer.dhl.com |
| Stripe | `POST /v1/payment_intents` | Basic, secret key as username | docs.stripe.com |
| Stripe webhooks | `Stripe-Signature` header, payload + header + `whsec_` secret, 5-min tolerance | | docs.stripe.com |
| Flutterwave | `POST https://api.flutterwave.com/v3/payments`, `Authorization: Bearer <FLW_SECRET_KEY>` | | developer.flutterwave.com |
| Flutterwave webhooks | `verif-hash` header compared to the dashboard secret hash (no HMAC) | | developer.flutterwave.com |
| Paystack | `POST https://api.paystack.co/transaction/initialize`, `GET /transaction/verify/{reference}`, `Authorization: Bearer <SECRET_KEY>` | | paystack.com/docs/api/transaction |

**Not confirmed** — the provider's own reference could not be read this session, so
these are marked `confirmed: false` in `providers.yaml`. Run
`node run/verify_endpoints.mjs --probe` with real keys before trusting them:
Aramex (all paths), Sendy (all paths), Postal Corporation of Kenya, Safaricom
Daraja resource paths, FedEx resource paths beyond `/oauth/token`, KRA iCMS/KEBS/EPC,
bank escrow endpoints, CBK rate feed.
