# KICC PLATFORM — INTEGRATION ROADMAP
## APIs, Automations & SEO — What Remains to Be Connected

---

## 1. GOVERNMENT API INTEGRATIONS

Services with pipelines built but awaiting API keys to go live.

| # | Service | Objective | API Key Needed | Status |
|---|---------|-----------|---------------|--------|
| 1 | **KRA PIN Verification** | Auto-verify business registration & tax compliance for exhibitors during onboarding. Currently falls back to manual document upload. | `KRA_API_KEY` | Stub built |
| 2 | **Huduma Namba / eCitizen** | Verify national ID numbers during user registration. Removes manual identity checks. | `HUDUMA_API_KEY` | Stub built |
| 3 | **NTSA** | Verify vehicle registration & driving licenses for transport/logistics sector listings. | `NTSA_API_KEY` | Stub built |
| 4 | **Kenya Wildlife Service** | Pull real-time park data, entry fees, and conservation status for tourism attractions. | `KWS_API_KEY` | Stub built |
| 5 | **Ministry of Tourism** | Sync official star ratings for hotels and tourism classification data. | `TOURISM_API_KEY` | Stub built |
| 6 | **National Museums of Kenya** | Display heritage site data, entry fees, and cultural information per county. | `MUSEUMS_API_KEY` | Stub built |
| 7 | **Ministry of Health** | Verify health facility registrations and certifications for the healthcare sector. | `HEALTH_API_KEY` | Stub built |
| 8 | **Ministry of Agriculture** | Pull real-time crop prices, market data, and extension services information. | `AGRICULTURE_API_KEY` | Stub built |
| 9 | **Ministry of Education** | Verify institution registrations and accreditation for the education sector. | `EDUCATION_API_KEY` | Stub built |

### Missing Service Classes (no code written yet)

| # | Service | Objective | Notes |
|---|---------|-----------|-------|
| 10 | **TIMBR (Business Registry)** | Auto-verify business names and registration numbers | No class exists |
| 11 | **NEMIS (Education MIS)** | Pull school enrollment, performance data per county | No class exists |
| 12 | **iTax (KRA)** | Tax compliance status for exhibitors and marketplace sellers | No class exists |

---

## 2. PAYMENT INTEGRATIONS

Payment drivers fully coded but running in stub mode — no real transactions possible.

| # | Driver | Objective | Config Needed | Status |
|---|--------|-----------|---------------|--------|
| 13 | **M-Pesa STK Push** | Accept mobile money payments for marketplace orders, bookings, and gift cards. Full callback flow built. | `MPESA_CONSUMER_KEY`, `MPESA_CONSUMER_SECRET`, `MPESA_SHORTCODE`, `MPESA_PASSKEY`, `MPESA_CALLBACK_URL` | Stub mode |
| 14 | **Stripe** | Accept international credit/debit card payments for global buyers. | `STRIPE_KEY`, `STRIPE_SECRET`, `STRIPE_WEBHOOK_SECRET` | Stub mode |
| 15 | **Airtel Money** | Alternative mobile money for Airtel subscribers. | Part of M-Pesa config | Stub mode |
| 16 | **T-Kash** | Telkom Kenya mobile money. | Separate driver built | Stub mode |
| 17 | **Bank EFT** | Manual bank transfer payment tracking with pending verification. | No keys needed | Stub mode |
| 18 | **Crypto** | Accept cryptocurrency payments (onramp API). | `CRYPTO_API_KEY` | Stub mode |

---

## 3. MEDIA PIPELINE ENGINES

Generation engines built but disabled. These produce the 4D immersive videos, 3D models, and cinematic content.

| # | Engine | Objective | Config Needed | Status |
|---|--------|-----------|---------------|--------|
| 19 | **Wan2GP** | Video-to-video generation pipeline for cinematic transitions. | `PIPELINE_WAN2GP_ENABLED=true` + API key | Disabled |
| 20 | **Hailuo** | AI video generation from text/image prompts for 4D content. | `HAILUO_API_KEY`, `HAILUO_GROUP_ID` | Disabled |
| 21 | **Kling** | Alternative video generation engine with different style profiles. | `KLING_API_KEY` | Disabled |
| 22 | **Wan** | Base video generation model (predecessor to Wan2GP). | `WAN_API_KEY` | Disabled |
| 23 | **Tripo3D** | Generate 3D models from single images — feeds photogrammetry pipeline. | `TRIPO3D_API_KEY` | Disabled |
| 24 | **ROAD** | Neural rendering engine for 3D scene reconstruction. | `ROAD_BIN` path config | Disabled |

---

## 4. COMMUNICATIONS & MESSAGING

| # | Service | Objective | Config Needed | Status |
|---|---------|-----------|---------------|--------|
| 25 | **Africa's Talking SMS** | Send transactional SMS (order confirmations, verification codes, alerts). | `AFRICASTALKING_API_KEY` | Sandbox only |
| 26 | **Africa's Talking USSD** | Deploy USSD menu on telecom network for feature-phone users to browse counties, products, and bookings. | `AFRICASTALKING_USSD_CODE`, `AFRICASTALKING_USSD_CALLBACK_TOKEN` | Functional stub |
| 27 | **N8n Webhook Base URL** | Connect workflow automation to all platform events (26 event types built). Triggers content pipelines, trade promotion, notifications. | `N8N_BASE_URL`, `N8N_WEBHOOK_SECRET` | Unconnected |
| 28 | **SendGrid** | Transactional email (verification codes, order receipts, trade enquiries). | ✅ Active | Live |
| 29 | **Google OAuth** | Social login. | ✅ Active | Live |

---

## 5. INFRASTRUCTURE & DATA

| # | Service | Objective | Config Needed | Status |
|---|---------|-----------|---------------|--------|
| 30 | **OpenWeather API** | Display live weather data per county (temperature, rainfall, forecasts) on county pages and tourism attractions. | `OPENWEATHER_API_KEY` | Inactive |
| 31 | **Elasticsearch** | Full-text search across products, attractions, counties with typo tolerance, faceted filters, and relevance scoring. Currently falls back to MySQL LIKE queries. | `ELASTICSEARCH_HOST`, `ELASTICSEARCH_PORT` | Fallback only |
| 32 | **Redis** | Production-grade caching, session storage, and job queue. Currently using database-driven queue. | `REDIS_HOST`, `REDIS_PORT`, `REDIS_PASSWORD` | Disabled |
| 33 | **Cloudflare R2** | Media CDN. | ✅ Active | Live |
| 34 | **Sentry** | Error tracking. | ✅ Active | Live |
| 35 | **Laravel Pulse** | Performance monitoring dashboard. | ✅ Active | Live |

---

## 6. AUTOMATIONS (N8N WORKFLOWS)

Three workflows built in `pipeline/n8n-workflows/` but not deployed — require N8N server with base URL configured.

| # | Workflow | Trigger | Objective |
|---|----------|---------|-----------|
| W1 | **County Content Pipeline** | Webhook (on county content update) | Auto-validate sector images, process with AI, store in R2, push to international feed, notify admin |
| W2 | **International Trade Promotion** | Scheduled (weekly) | Fetch export-ready county products, generate promotional content via OpenAI, publish to international trade channels, log results |
| W3 | **SBS 3D Pipeline** | Webhook (on new 3D asset upload) | Scene analysis via OpenRouter, queue depth mapping, generate side-by-side 3D video, upload to S3, email notification |

### Platform Events Already Wired (26 events built in N8nService, just need base URL)

- `user_registered`, `order_created`, `order_paid`, `escrow_released`
- `product_listed`, `product_sold`, `exhibition_created`
- `trade_enquiry_created`, `export_eligibility_checked`
- `agent_onboarded`, `kyc_submitted`, `kyc_approved`, `kyc_rejected`
- `review_submitted`, `review_approved`, `review_rejected`
- `4d_video_requested`, `4d_video_completed`, `4d_video_failed`
- `provider_service_created`, `provider_service_approved`
- `notification_created`, `coupon_applied`, `pipeline_job_completed`
- `county_content_updated`, `subscription_renewed`

---

## 7. SEO OPTIMIZATIONS

### Done So Far
| # | Item | Status |
|---|------|--------|
| 1 | Meta titles & descriptions per page | ✅ Basic |
| 2 | Open Graph tags (og:title, og:description, og:image) | ✅ Partial |
| 3 | Canonical URLs | ✅ Basic |
| 4 | Robots meta tags | ✅ Present |
| 5 | Sitemap.xml generation | ✅ Route exists |
| 6 | Social media preview cards | ✅ Basic |

### Not Yet Done
| # | Item | Priority | Objective |
|---|------|----------|-----------|
| S1 | **Dynamic sitemap per county** | HIGH | Generate individual sitemaps per county with sector pages, products, attractions for Google indexing |
| S2 | **Schema.org structured data** | HIGH | Add JSON-LD for Organization (KICC), LocalBusiness (per county), Product, TouristAttraction, Event, BreadcrumbList |
| S3 | **SEO-friendly URLs** | MEDIUM | Ensure all county pages use clean slugs, add trailing slashes, avoid query params on public pages |
| S4 | **Hreflang tags** | MEDIUM | For international trade content — en-KE primary, en-US/en-GB fallback |
| S5 | **Page speed optimization** | HIGH | Lazy-load offscreen images, defer non-critical JS, inline critical CSS, optimize Largest Contentful Paint (hero video poster) |
| S6 | **Image alt tags** | MEDIUM | Auto-generate alt text for county images, sector tiles, product photos using AI captioning |
| S7 | **Breadcrumb navigation** | MEDIUM | Structured breadcrumbs on all public pages (County > Sector > Product) |
| S8 | **Preconnect / prefetch** | LOW | DNS-prefetch and preconnect for R2 CDN, fonts.googleapis, OpenRouter |
| S9 | **Contentful first paint** | HIGH | Ensure hero content (headline, CTA) renders before hero video loads — `poster` attribute already set |
| S10 | **Core Web Vitals monitoring** | MEDIUM | Set up Lighthouse CI or similar to track CLS, LCP, FID per deployment |
| S11 | **404 page optimization** | LOW | Custom 404 with search, popular links, county navigation |
| S12 | **News sitemap** | LOW | For KICC news/blog section if/when added |
| S13 | **Google Business Profile sync** | MEDIUM | Sync county data with Google Business Profiles for local SEO |
| S14 | **Robots.txt per environment** | LOW | Block staging/dev from search engines |

---

## 8. PENDING PIPELINE JOBS (4D Video Queue)

12 media pipeline jobs in `pipeline_jobs.json` waiting for processing once engines are active:

| # | Job | Type |
|---|-----|------|
| 1 | Elipa — tourism attractions | 4D video |
| 2 | Drone aerials — county overview | 4D video |
| 3 | Gorges — scenic | 4D video |
| 4 | Guks Coffee — agriculture | 4D video |
| 5 | Kanunga Falls — tourism | 4D video |
| 6-12 | Cultural sites, schools, hotels, transport hubs | 4D video |