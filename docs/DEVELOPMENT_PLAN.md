# DEVELOPMENT PLAN — KICC National Exhibition Platform

## Phased Build-Out Over 18 Months

---

## PHASE 0 — Foundation (Month 1-2)

**Goal:** Infrastructure, auth, DevOps — everything else depends on this.

### Deliverables

| Component | Tech | What to Build |
|-----------|------|---------------|
| Laravel backend scaffold | Laravel 11 + PHP 8.2 | Project structure, API routing, middleware, error handling |
| Database schema | MySQL 8.4 | All core tables: users, roles, permissions, counties, sectors |
| Auth system | Laravel Sanctum + OAuth2 | Registration (light/verified/business), login, MFA, password reset |
| Account types | Custom | 8 types: Individual, SME, School, County, Ministry, Admin, NIS |
| Gov ID integration | API | Huduma/eCitizen OTP verification stub + KRA PIN for business |
| Infrastructure | Laravel Forge + GitHub Actions | Dev/staging/production environments, CI/CD pipelines |
| Monitoring | Sentry + Laravel Pulse + Prometheus | Error tracking, server metrics, uptime |
| Documentation | GitHub Wiki + /docs | Architecture, API spec (OpenAPI), deployment guide |

### Key Dependencies
- GovCloud or VPS provisioned
- Domain + SSL
- GitHub Enterprise org created

**Estimated team:** 2 backend devs, 1 DevOps  
**Estimated cost:** $8,000-12,000

---

## PHASE 1 — Exhibition & Booking Engine (Month 2-4)

**Goal:** Working MVP where any Kenyan can list an event, sell booths, and livestream.

### Deliverables

| Component | Tech | What to Build |
|-----------|------|---------------|
| Venue management | Laravel + MySQL | CRUD for venues, floor plans, capacity, amenities |
| Event management | Laravel + MySQL | Event creation, dates, categories, sectors |
| Booth inventory | Laravel + MySQL | Booth grid, pricing, availability, virtual booths |
| Ticketing | Laravel + Redis | Ticket types, pricing, QR generation, check-in |
| Subscription tiers | Laravel + Laravel Cashier | Basic/Premium/Enterprise, M-Pesa recurring |
| Multi-camera livestream | WebRTC + HLS | Keycam, stagecam, floorcam, boothcam, aerial views |
| Admin dashboard | Bootstrap 5 + Laravel | User mgmt, content moderation, earnings reports |
| Public browse | Bootstrap 5 + Vue | Event discovery, venue search, booth preview |

### Key Dependencies
- Phase 0 complete (auth, infrastructure)
- M-Pesa API access (Daraja API)
- WebRTC/streaming server provisioned

**Estimated team:** 2 backend, 1 frontend, 1 QA  
**Estimated cost:** $15,000-25,000

---

## PHASE 2 — Immersive Content Pipeline (Month 3-6)

**Goal:** Automated 2D → 3D SBS conversion + interactive splatting, deployable on GPU cluster.

### Deliverables

| Component | Tech | What to Build |
|-----------|------|---------------|
| Depth worker | Python + PyTorch + DAv2 | GPU-accelerated depth estimation, frame-by-frame |
| Stereo worker | Python + OpenCV | SBS generation from depth, inpainting, temporal smoothing |
| Analyzer | Python + Kimi K3 API | Scene analysis, optimal strength/convergence per clip |
| Orchestrator | Python + Redis Queue | Master controller: analyze → depth → stereo → deliver |
| 4K upscaler | Python + OpenCV | INTER_CUBIC upscale to 3840×1080 or 3840×2160 |
| DaVinci Resolve API | Python + DaVinciResolveScript | Auto color grading, booth branding overlay |
| Refiner | Python + Google AI API | Nano Banana 2 keyframe enhance for texture quality |
| Promo adapter | Python + Google Flow API | Generate promotional clips from keyframes |
| GPU deploy | Vast.ai / RunPod API | Auto-provision GPU instances, deploy workers |
| SBS output | MP4 H.265 | Final 4K SBS video delivered to S3 |

### Key Dependencies
- Phase 0 infrastructure (job queue, storage)
- OpenRouter API key (Kimi K3)
- Google AI API key (Nano Banana 2, Flow)
- DaVinci Resolve Studio license ($295)

**Estimated team:** 2 ML/Python devs, 1 DevOps  
**Estimated compute:** ~$500-1,000/mo GPU (Vast.ai)  
**Estimated cost:** $10,000-18,000

---

## PHASE 2B — Interactive 3D / Gaussian Splatting (Month 4-7)

**Goal:** WebGL-based PUBG-like interactive exhibition walkthroughs.

### Deliverables

| Component | Tech | What to Build |
|-----------|------|---------------|
| Splat worker | Python + 3D Gaussian Splatting | Reconstruct 3D scene from video walkthrough |
| Point cloud optimizer | Python | Compression + cleanup for web delivery |
| WebGL viewer | Three.js / PlayCanvas | Browser-based 3D scene with drag/zoom/gyroscope |
| Booth overlay | JavaScript | Interactive hotspots: tap booth → info card + SBS video |
| VR mode | WebXR API | Phone gyroscope look-around |
| Mobile controls | JavaScript | Touch drag, pinch zoom, WASD if gamepad |
| Splat storage | S3 + CDN | Deliver .ply/.splat files with viewer |

### Key Dependencies
- Phase 2 DAv2 pipeline (shared GPU infra)
- 3D Gaussian Splatting research implementation

**Estimated team:** 1 ML dev + 1 WebGL/frontend dev  
**Estimated compute:** ~$1,000-2,000/mo GPU (A100 for splatting)  
**Estimated cost:** $12,000-20,000

---

## PHASE 3 — Commerce: Escrow & Courier (Month 5-8)

**Goal:** Secure payments, escrow protection, nationwide courier fulfilment.

### Deliverables

| Component | Tech | What to Build |
|-----------|------|---------------|
| Payment rails | Laravel + Daraja + Stripe | M-Pesa, Airtel Money, T-Kash, Visa/MC, Bank EFT, Crypto |
| Escrow engine | Laravel + Bank API | 8-step flow: deposit → verify → deliver → release |
| Dispute resolution | Laravel + custom | Automated (< KES 5,000), human review (above) |
| Courier API | Laravel + third-party | Real-time tracking, pickup scheduling, delivery confirmation |
| Payout system | Laravel + Bank API | Automated vendor payouts after escrow release |
| Receipts/invoices | Laravel + PDF | Tax-compliant receipts, auto-email |

### Key Dependencies
- Bank partnership for trust account
- CBK regulatory approval
- PCI DSS compliance (from first payment code)
- Single exclusive courier partner agreement

**Estimated team:** 2 backend, 1 security/compliance  
**Estimated cost:** $20,000-35,000

---

## PHASE 4 — Discovery & Cross-Lingual Search (Month 6-9)

**Goal:** Unified search across 47 counties, 7 sectors, 3 languages.

### Deliverables

| Component | Tech | What to Build |
|-----------|------|---------------|
| Semantic vector search | Qdrant + embedding model | Swahili/English/Sheng in same vector space |
| Voice search | Whisper (on-device) | Speech-to-text → vector search |
| Image search | CLIP / ViT | Upload photo → find similar exhibitions/products |
| Elasticsearch fallback | Elasticsearch 8.x | BM25 keyword search for low-connectivity |
| Faceted filtering | Laravel + Elasticsearch | County, sector, price, date, rating filters |
| Search API | Laravel REST | Unified gateway, routes all query types |

### Key Dependencies
- Phase 1 data (venues, events, booths)
- Embedding model fine-tuned for Kenyan context

**Estimated team:** 1 ML/NLP dev, 1 backend  
**Estimated cost:** $10,000-15,000

---

## PHASE 5 — Intelligence / AI Engine (Month 7-10)

**Goal:** Personalised recommendations, vendor grading, predictive analytics.

### Deliverables

| Component | Tech | What to Build |
|-----------|------|---------------|
| Hybrid recommender | Python + LightGBM | Behavioral (50%) + Trust (30%) + Demographic (20%) |
| Two-score vendor model | Laravel + Python | Trust Score (0-100 public) + Visibility Score (purchasable) |
| Fairness floor | Laravel rules engine | Organic visibility minimum, cannot override trust |
| Predictive analytics | Python + Prophet | Foot traffic forecasts, tourism trends, demand sensing |
| Anomaly detection | Python + Isolation Forest | Fraud, account takeover, review manipulation |
| Advertising engine | Laravel + Python | 4 ad products: featured, co-marketing, referral, sponsored |
| Admin intelligence dashboard | React + charts | Real-time recommendations, grading, predictions |

### Key Dependencies
- Phase 1 + 3 transaction data for recommender training
- Phase 4 search data for personalisation signals

**Estimated team:** 1 ML/AI engineer, 1 backend, 1 frontend  
**Estimated cost:** $15,000-25,000

---

## PHASE 6 — Physical Experience & Mobile App (Month 8-14)

**Goal:** Unity mobile app + physical 4D booths at flagship locations.

### Deliverables

| Component | Tech | What to Build |
|-----------|------|---------------|
| Unity mobile app | Unity 6 LTS + C# | iOS + Android, 6 features (Explore, Live, Marketplace, My Journey, AR, Offline) |
| Unity web player | Unity WebGL | Desktop browser version |
| 4D booth integration | Unity + hardware SDK | D-BOX motion, Olorama scent, L-ACOUSTICS audio, ETC lighting |
| Touch table kiosk | Unity + touch | Ideum 65" multi-touch for county hubs |
| AR mode | Unity + AR Foundation | Geofence/QR-triggered AR overlay |
| Offline tours | Unity + local storage | Pre-download 360° tours for areas with no connectivity |
| Adaptive streaming | HLS + ABR | 144p to 4K based on connection |

### Key Dependencies
- Phase 2 immersive content (all 3 tiers)
- Physical booth hardware procured (Barco projectors, D-BOX, etc.)
- 5-10 county hub locations secured

**Estimated team:** 2 Unity devs, 1 hardware integration, 1 backend  
**Estimated hardware:** $50,000-150,000 per flagship booth  
**Estimated cost:** $40,000-80,000 (software) + hardware

---

## PHASE 7 — Government Integration & Scale (Month 10-18)

**Goal:** Live API connections to 10+ government databases, scale to all 47 counties.

### Deliverables

| Component | Tech | What to Build |
|-----------|------|---------------|
| eKRA/iTax integration | REST API | Business verification, tax compliance |
| NTSA integration | REST API | Transport licensing data |
| KWS integration | REST API | Parks and wildlife data |
| Ministry of Education | REST API | Schools, institutions, programs |
| Ministry of Tourism | REST API | Star ratings, classified establishments |
| Ministry of Agriculture | REST API | Crop data, market prices, co-ops |
| Ministry of Trade | REST API | Business registrations |
| Ministry of Health | REST API | Facility listings, accreditation |
| National Museums | REST API | Heritage sites, cultural data |
| KE-CIRT integration | Threat intel feed | Security incident coordination |
| County gov portals | Custom per county | Local content management dashboards |

### Key Dependencies
- Signed MOUs with each ministry
- Phase 4 data backbone ready for government feeds

**Estimated team:** 2 integration devs, 1 compliance officer  
**Estimated cost:** $25,000-50,000

---

## TOTAL PROJECT ESTIMATE

### By Phase

| Phase | Timeline | Software Cost | Hardware/Cloud | Team Size |
|-------|----------|--------------|----------------|-----------|
| 0 — Foundation | Month 1-2 | $8-12K | $500/mo | 3 |
| 1 — Exhibition Engine | Month 2-4 | $15-25K | $1,000/mo | 4 |
| 2 — 3D Pipeline | Month 3-6 | $10-18K | $500-1,000/mo GPU | 3 |
| 2B — Splatting | Month 4-7 | $12-20K | $1,000-2,000/mo GPU | 2 |
| 3 — Commerce | Month 5-8 | $20-35K | $500/mo | 3 |
| 4 — Search | Month 6-9 | $10-15K | $500/mo | 2 |
| 5 — AI Engine | Month 7-10 | $15-25K | $1,000/mo | 3 |
| 6 — Mobile + Physical | Month 8-14 | $40-80K | $50-150K hardware | 4 |
| 7 — Gov Integration | Month 10-18 | $25-50K | $500/mo | 3 |
| **TOTAL** | **18 months** | **$155-280K** | **$55-158K** | **3-4 avg** |

### Monthly Run Rate (post-launch)

| Category | Monthly |
|----------|---------|
| GPU compute (Vast.ai) | $500-2,000 |
| Cloud infrastructure | $500-1,000 |
| Laravel Forge | $15 |
| OpenRouter (Kimi K3) | $50-200 |
| Google AI Pro | $20 |
| S3/CDN | $50-200 |
| Monitoring (Sentry) | $50-100 |
| **Total** | **~$1,200-3,600** |

---

---

## SCOPE ADDITIONS V2 — County Tabs, Sector Economy, Context-Aware SEO

Added based on stakeholder requirements. These cut across ALL phases and add a new data layer.

---

### ADDITION A — County Tab Architecture (47 Microsites)

Each of Kenya's 47 counties gets its own dedicated tab/section.

#### Structure
```
/                           → National Hub (aggregate of all)
/county/nairobi            → Nairobi microsite
/county/mombasa            → Mombasa microsite
/county/kisumu             → Kisumu microsite
...47 total
/sector/tourism             → Cross-county sector view
/sector/agriculture
/sector/education
```

#### Each County Tab Contains
| Section | Content |
|---------|---------|
| County profile | Demographics, economic zones, key industries |
| Exhibitions | Active and upcoming exhibitions IN THAT COUNTY |
| Vendors | Registered businesses and SMEs by county |
| Tourism | Hotels, parks, beaches, cultural sites |
| Education | Schools, universities, vocational centres |
| Health | Hospitals, clinics, accreditation |
| Agriculture | Crop data, markets, co-ops |
| Energy | Grid status, renewable projects |
| Infrastructure | Roads, internet coverage, transport hubs |
| County officials | Governor, CEC members, contact |
| Weather | Live + forecast data for the county |
| Gallery | County photos, 360° tours, SBS videos |

#### Technical
- Dynamic routing with county slug
- Content managed per-county (county admin role)
- Cross-county tagging for comparative views

---

### ADDITION B — Sector Economy Tagging System

Every entity (booth, vendor, event, tourism spot) gets multi-axis tagging.

#### Tag Axes
| Axis | Example Values |
|------|---------------|
| **Primary Sector** | Agriculture, Tourism, Technology, Health, Education, Energy, Manufacturing, Finance, Transport, Creative |
| **Sub-Sector** | (Agri) Dairy, Tea, Coffee, Horticulture — (Tourism) Beach, Safari, Cultural, Adventure — etc. |
| **Economic Zone** | Lake Region, Coastal Strip, Central Highlands, Rift Valley, Northern Arid, Nairobi Metro |
| **Value Chain** | Producer → Processor → Distributor → Retailer → Exporter |
| **County** | 1 of 47 counties |
| **Season Tags** | Dry season, Long rains, Short rains, Peak tourism, Off-peak |
| **Weather Match** | Hot, Warm, Cool, Rainy, Windy — can recommend opposite-weather destinations |

#### Implementation
- `sectors` table (hierarchical: sector → sub-sector)
- `economic_zones` table
- `seasonal_calendars` table (per county, per month: weather, tourism level, crop cycles)
- Polymorphic `taggables` table for all entity types
- Admin tagging interface with batch operations

---

### ADDITION C — National Hub

A single unified section that aggregates:
- All 47 counties in a map + grid view
- All 7+ sectors with cross-county filtering
- All vendors, events, exhibitions — searchable across the entire nation
- Featured content (editorial picks from each county)
- Live data dashboards (weather overlay, active exhibitions count, trending sectors)

The National Hub is the default landing page. County tabs are secondary navigation.

---

### ADDITION D — Context-Aware SEO Engine

The breakthrough feature. SEO that adapts in REAL TIME to who is searching, where they are, what the weather is, and what season it is.

#### Data Sources
| Source | What It Provides |
|--------|-----------------|
| **IP Geolocation** (MaxMind/ipapi) | User country, city, latitude, longitude, timezone |
| **OpenWeatherMap / WeatherAPI** | Current weather at user location + destination weather for all 47 counties |
| **Google Trends API** | Trending search terms by region, season, category |
| **Google Search Console API** | Keyword performance, click-through rates, ranking data |
| **Seasonal Calendar** | Pre-tagged data per county: peak season, harvest season, wildebeest migration, rainy vs dry |
| **Search History** (via Google) | Implicit intent — what user has been searching for |
| **Query Classification** | ML classifier: "summer vacation" → intent=escape_cold, preference=warm_beach |

#### Core Algorithm

```
User searches "summer vacation" (from USA, January, winter)

1. Detect: Location=New York, Local temp=2°C, Local season=Winter
2. Classify query: "summer vacation" → intent=warm_weather_escape
3. Query Kenya county weather data:
   - Malindi: 31°C ☀️ ← match!
   - Mombasa: 30°C ☀️ ← match!
   - Maasai Mara: 22°C 🌤️ ← okay
   - Nairobi: 18°C 🌧️ ← cold, skip
   - Kisumu: 24°C ⛅ ← moderate
4. Surface: Malindi beaches, Mombasa resorts, Watamu marine park
5. Dynamic meta tag: "Escape winter in Kenya's warm coastal paradise — 31°C in Malindi today"
6. Structured data: JSON-LD with weather, location, seasonal context
7. Cache result for 1 hour (weather changes)
```

#### SEO Components

| Component | What It Does |
|-----------|-------------|
| **Geo-Detector** | User IP → location + weather at request time |
| **Intent Classifier** | Query text → intent (warm_escape, business, cultural, adventure, shopping, education) |
| **Destination Matcher** | Cross-reference user intent + location weather + Kenya county data |
| **Dynamic Meta Generator** | Per-request title, description, og:tags, JSON-LD |
| **Sitemap Engine** | Generate sitemaps per: county, sector, season, intent-type |
| **Trend Injector** | Google Trends keywords → dynamic H1s, breadcrumbs, content slots |
| **Cache Layer** | 1-hour TTL for weather-dependent content, 24h for static |
| **Performance Reporter** | Google Search Console → auto-optimise underperforming pages |

#### Matching Matrix (User Weather × Destination Weather)

| User Has | User Wants | Kenya Should Show |
|----------|-----------|-------------------|
| ❄️ Winter/Cold | Warm escape | Coastal beaches (Malindi, Mombasa, Watamu, Diani) |
| ☀️ Summer/Hot | Cool retreat | Central highlands (Nairobi, Nyeri, Limuru), Lake Naivasha |
| 🌧️ Rainy | Dry destination | Rift Valley (dry side), Northern Kenya |
| 🌤️ Mild/Variable | Adventure | Maasai Mara, Amboseli, Mount Kenya, Hell's Gate |
| ❄️ Extreme Cold | Heat therapy | Lodwar, Turkana (hottest region) |
| ☀️ Heatwave | Water activities | Lake Victoria (Kisumu), Indian Ocean coast |

#### Technology Stack for SEO Engine

| Layer | Tech |
|-------|------|
| Geolocation | MaxMind GeoLite2 + ip-api.com fallback |
| Weather | OpenWeatherMap OneCall API |
| Trends | Google Trends API (pytrends) |
| Search Console | google-api-php-client |
| Intent Classifier | Kimi K3 (OpenRouter) or lightweight BERT |
| Dynamic meta | Laravel middleware + view composers |
| Caching | Redis with weather-based TTL |
| Sitemaps | Laravel sitemap generator (spatie/laravel-sitemap) |
| CDN | Cloudflare with country-level edge rules |

---

### Updated File Tree

```
kenya-3d-platform/
+-- data/                          # NEW — Static data assets
│   +-- counties.json              # All 47 counties with metadata
│   +-- sectors.json               # Sector taxonomy tree
│   +-- economic_zones.json        # Economic zone definitions
│   +-- seasonal_calendar.csv      # Per-county per-month weather/season
│   +-- weather_stations.json      # County HQ weather station IDs
+-- seo-engine/                    # NEW — Context-aware SEO
│   +── Detector.php               # Geo + weather + trends detection
│   +── IntentClassifier.php       # Query intent analysis
│   +── DestinationMatcher.php     # Match user intent → Kenya destinations
│   +── MetaGenerator.php          # Dynamic title/desc/JSON-LD
│   +── SitemapGenerator.php       # Adaptive sitemaps per county/sector
│   +── TrendInjector.php          # Google Trends → content injection
│   +── CacheManager.php           # Weather-aware TTL caching
+-- pipeline/                      # Existing
+-- laravel-backend/               # Existing + county/sector modules
│   +── app/Http/Controllers/Api/CountyController.php
│   +── app/Http/Controllers/Api/SectorController.php
│   +── app/Models/County.php
│   +── app/Models/Sector.php
│   +── database/migrations/…_create_counties_table.php
│   +── database/migrations/…_create_sectors_table.php
│   +── database/migrations/…_create_seasonal_calendars_table.php
+-- deploy/                        # Existing
+-- opencode/                      # Existing
```

---

## IMMEDIATE NEXT STEPS (What to build right now)

### Week 1: Data Foundation
- Create `data/counties.json` with all 47 Kenyan counties (coordinates, population, economic zones, weather stations)
- Create `data/sectors.json` with sector taxonomy tree
- Create county + sector Laravel migrations and models
- Seed database with county and sector data

### Week 2: County Tab System
- Dynamic routing: `/county/{slug}`
- CountyController with exhibition, vendor, tourism sub-views
- County admin role + content management panel
- National Hub aggregated view

### Week 3: Context-Aware SEO Engine
- Geo-detection middleware (IP → location → weather)
- Intent classifier (Kimi K3 via OpenRouter)
- Destination matcher + dynamic meta generation
- Weather-aware caching layer

### Week 4: Sector Economy Tagging
- Tagging interface for all entity types
- Cross-county sector browsing
- Sector landing pages with aggregated content
- Economic zone visualisation (map + data)

### Week 5+: Optimization
- Google Trends injection
- Search Console performance monitoring
- Cache tuning based on weather patterns
- A/B test different SEO strategies per county
- Seasonal content refresh automation

---

**Which section do you want to build first?**
