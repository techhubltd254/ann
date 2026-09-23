# KICC — What you fill in, and where to get it

Every provider is wired. The **only** thing between this repo and live money and
live cargo is the credentials below.

1. `cd integrations && cp .env.example .env`
2. Fill the block you want (payments first, then freight).
3. Set the provider's `*_ENABLED` to `true`. Set `MOCK_MODE=false` when you want live calls.
4. `node run/verify_endpoints.mjs --probe` — confirms reachability and lists anything still blank.

Until a provider is both `*_ENABLED=true` **and** fully credentialled, its driver
keeps running in mock mode, so the chain never breaks.

## Payments

| Provider | Lane | Toggle | Env vars you must add | Where to get them |
|---|---|---|---|---|
| Safaricom M-Pesa (Daraja) | mobile money (KE) | `MPESA_ENABLED` | `MPESA_CONSUMER_KEY`, `MPESA_CONSUMER_SECRET`, `MPESA_SHORTCODE`, `MPESA_PASSKEY`, `MPESA_CALLBACK_URL`, `MPESA_ENVIRONMENT` | developer.safaricom.co.ke → your app |
| Flutterwave | card, mobile money, bank (Africa) | `FLUTTERWAVE_ENABLED` | `FLUTTERWAVE_SECRET_KEY`, `FLUTTERWAVE_PUBLIC_KEY`, `FLUTTERWAVE_SECRET_HASH` | dashboard.flutterwave.com → Settings → API Keys, Webhooks |
| Paystack | card, bank, mobile money | `PAYSTACK_ENABLED` | `PAYSTACK_SECRET_KEY`, `PAYSTACK_PUBLIC_KEY` | dashboard.paystack.com → Settings → API Keys |
| Stripe | card, wallet (global) | `STRIPE_ENABLED` | `STRIPE_SECRET_KEY`, `STRIPE_PUBLISHABLE_KEY`, `STRIPE_WEBHOOK_SECRET` | dashboard.stripe.com/apikeys, /webhooks |
| Pesapal | card, mobile money (EA) | `PESAPAL_ENABLED` | `PESAPAL_CONSUMER_KEY`, `PESAPAL_CONSUMER_SECRET`, `PESAPAL_IPN_URL`, `PESAPAL_ENVIRONMENT` | pay.pesapal.com → API credentials |
| Bank / escrow (pipelines 58, 59, 62) | bank transfer, escrow, payouts | `BANK_ENABLED` | `BANK_NAME`, `BANK_API_BASE`, `BANK_CLIENT_ID`, `BANK_CLIENT_SECRET`, `BANK_ACCOUNT_NUMBER`, `BANK_ESCROW_ACCOUNT_REF`, `BANK_COLLECTION_ACCOUNT`, `BANK_WEBHOOK_SECRET` | your corporate bank's API onboarding desk |

## Freight

| Provider | Mode | Toggle | Env vars you must add | Where to get them |
|---|---|---|---|---|
| DHL Express (MyDHL) | air, courier, global | `DHL_ENABLED` | `DHL_API_KEY`, `DHL_API_SECRET`, `DHL_ACCOUNT_NUMBER`, `DHL_ENVIRONMENT` | developer.dhl.com → MyDHL API → get access |
| FedEx | air, courier | `FEDEX_ENABLED` | `FEDEX_CLIENT_ID`, `FEDEX_CLIENT_SECRET`, `FEDEX_ACCOUNT_NUMBER`, `FEDEX_ENVIRONMENT` | developer.fedex.com → My Projects |
| Aramex | air, courier, road | `ARAMEX_ENABLED` | `ARAMEX_USERNAME`, `ARAMEX_PASSWORD`, `ARAMEX_ACCOUNT_NUMBER`, `ARAMEX_ACCOUNT_PIN`, `ARAMEX_ACCOUNT_ENTITY`, `ARAMEX_ACCOUNT_COUNTRY_CODE` | aramex.com → developer solution centre |
| Ocean (Maersk / Hapag-Lloyd), FCL + LCL | sea | `OCEAN_ENABLED` | `OCEAN_CARRIER`, `OCEAN_API_BASE`, `OCEAN_TOKEN_PATH`, `OCEAN_CLIENT_ID`, `OCEAN_CLIENT_SECRET`, `OCEAN_CONSUMER_KEY`, `OCEAN_CONTRACT_REF`, `OCEAN_WEBHOOK_SECRET` | developer.maersk.com/catalogue · api-portal.hlag.com |
| Sendy | road (KE domestic) | `SENDY_ENABLED` | `SENDY_API_KEY`, `SENDY_API_BASE` | sendyit.com → developer / API keys |
| Postal Corporation of Kenya | road (KE remote/ASAL) | `POSTA_ENABLED` | `POSTA_API_KEY`, `POSTA_API_BASE` | Posta Kenya API onboarding |

## Services behind the goods

| Service | Toggle | Env vars you must add | Where |
|---|---|---|---|
| KRA iCMS / KEBS / EPC customs pack | `CUSTOMS_ENABLED` | `KRA_ICMS_CLIENT_ID`, `KRA_ICMS_CLIENT_SECRET`, `KRA_ICMS_ESERVICE`, `KEBS_CLIENT_ID`, `KEBS_CLIENT_SECRET`, `EPC_CLIENT_ID`, `EPC_CLIENT_SECRET` | KRA iCMS onboarding, KEBS, Export Promotion Council |
| FX rates | `FX_PROVIDER` | `FX_CBK_RATES_URL` or `FX_BANK_RATE_URL`, `FX_BANK_CLIENT_ID`, `FX_BANK_CLIENT_SECRET` | CBK published rates, or your bank's rate feed |
| Identity / tax / notifications | `ARDHISASA_ENABLED`, `ETIMS_ENABLED`, `AT_ENABLED` | `ARDHISASA_CLIENT_ID`, `ARDHISASA_CLIENT_SECRET`, `ETIMS_CLIENT_ID`, `ETIMS_CLIENT_SECRET`, `ETIMS_PIN`, `AT_API_KEY`, `AT_USERNAME`, `WHATSAPP_TOKEN`, `WHATSAPP_PHONE_ID` | Ardhisasa, KRA eTIMS, Africa's Talking, WhatsApp Cloud API |
| Pipeline desk systems | per desk | `DESK_*_TOKEN` (weighbridge, miller ERP, LIMS, WMS, cold store, assay lab, GDS, partner CRM, …) | the partner system each desk already runs |

## What "verified" means here

`config/providers.yaml` marks each endpoint `confirmed: true` only when the path
and auth were read from the provider's own documentation. Currently confirmed:
Stripe (`POST /v1/payment_intents`, Basic auth via `-u <secret_key>:`, `Stripe-Signature`
HMAC-SHA256 with 5-min tolerance), Flutterwave (`POST https://api.flutterwave.com/v3/payments`,
`Authorization: Bearer <FLW_SECRET_KEY>`, `verif-hash` header), Paystack
(`POST https://api.paystack.co/transaction/initialize`, `GET /transaction/verify/{reference}`,
Bearer auth), DHL Express MyDHL (test/production base URLs, pre-emptive HTTP BasicAuth).

Everything else — Aramex resource paths, Sendy, Posta, Daraja resource paths, FedEx
resource paths, KRA/KEBS/EPC, bank escrow, CBK feed, and the sea-carrier paths — is
marked `confirmed: false` and the driver says so in its header comment. Confirm those
with `--probe` before they carry real money or real cargo.
