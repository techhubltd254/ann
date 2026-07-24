# KICC Digital Economy Platform — Architecture Blueprint

## Platform Vision

A unified **Kenyan Digital Economy Super-App** connecting:

```
┌─────────────────────────────────────────────────────────────────────┐
│                    KICC DIGITAL ECONOMY PLATFORM                      │
├─────────────────────────────────────────────────────────────────────┤
│                                                                     │
│  🏛 Exhibition       🛒 Marketplace       ✈️ Travel & Tourism       │
│  • Booth booking     • Products (47 cnty)  • Flight booking         │
│  • Event mgmt        • Global shipping     • Hotel / restaurant     │
│  • Venue mgmt        • Real-time tracking  • Airport transfer       │
│  • Ticketing         • Seller dashboard    • Travel packages        │
│  • Schedules         • Escrow payments     • Itinerary planner       │
│                                                                     │
│  🤖 AI Pipeline             📢 Advertising         💳 Payments     │
│  • Match tourists→exp       • Campaign mgmt        • M-Pesa / cards │
│  • Product recommendations  • Programmatic bidding  • Escrow         │
│  • Dynamic pricing          • Geo-targeting        • Payouts         │
│  • SEO optimization         • Attribution          • Subscriptions   │
│  • Content generation       • Analytics            • Multi-currency  │
│                                                                     │
│  🔗 Integration Layer                                              │
│  • n8n workflows • Agentic loop • Cloudflare workers • REST APIs   │
│  • Webhooks      • WebSocket    • External partners   • OAuth2      │
│                                                                     │
└─────────────────────────────────────────────────────────────────────┘
```

---

## Architecture Layers

### Layer 1: Database (TiDB — MySQL Compatible Distributed)

```
┌─────────────────────────────────────────────────────────────────────┐
│                        TIDB CLUSTER                                  │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐               │
│  │   TiDB SQL   │  │   TiDB SQL   │  │   TiDB SQL   │  ← MySQL wire │
│  │   (stateless)│  │   (stateless)│  │   (stateless)│    protocol    │
│  └──────┬───────┘  └──────┬───────┘  └──────┬───────┘               │
│         └─────────────────┼──────────────────┘                       │
│                           ▼                                          │
│  ┌──────────────────────────────────────────────────────────────┐   │
│  │                Placement Driver (auto-shard)                  │   │
│  └──────────────────────────────────────────────────────────────┘   │
│                           │                                          │
│         ┌─────────────────┼──────────────────┐                       │
│         ▼                 ▼                  ▼                        │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐               │
│  │  TiKV Node 1 │  │  TiKV Node 2 │  │  TiKV Node N │  ← Distributed│
│  │  (100GB data)│  │  (100GB data)│  │  (100GB data)│    key-value   │
│  │  + Raft      │  │  + Raft      │  │  + Raft      │    storage     │
│  └──────────────┘  └──────────────┘  └──────────────┘               │
│                                                                     │
│  ┌──────────────────────────────────────────────────────────────┐   │
│  │  TiFlash (Columnar) — Real-time analytics without ETL         │   │
│  │  • Analytics queries auto-routed here                         │   │
│  │  • No separate data warehouse needed                          │   │
│  └──────────────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────────────┘
```

**Why TiDB over Aurora MySQL:**

| Capability | Aurora MySQL | TiDB |
|-----------|-------------|------|
| Max storage | 256 TiB (single node write bottleneck) | **Petabyte+** (auto-shards across nodes) |
| Write scaling | Single primary node | **Multi-node writes** — linear scaling |
| Multi-region | Read replicas only | **Active-active** across regions |
| HTAP | Separate Redshift/Snowflake needed | **Built-in columnar (TiFlash)** — analytics on live data |
| MySQL compat | ✅ Full | ✅ Wire-protocol compatible |
| Zero-downtime scaling | Vertical (resize instance) | **Horizontal** (add nodes, no downtime) |
| Cost at petabyte scale | Very expensive (large instances) | **Commodity hardware** — scales linearly |

### Layer 2: Application (Laravel)

```
┌─────────────────────────────────────────────────────────────────────┐
│                      LARAVEL APPLICATION                              │
├─────────────────────────────────────────────────────────────────────┤
│                                                                     │
│  Web Controllers    API Controllers     Filament Admin              │
│  ┌──────────────┐  ┌──────────────┐  ┌────────────────────────┐    │
│  │ Home         │  │ Auth/Users   │  │ Exhibition Resources   │    │
│  │ Counties     │  │ Products     │  │ Product Resources      │    │
│  │ Exhibitions  │  │ Orders       │  │ Travel Resources       │    │
│  │ Products     │  │ Bookings     │  │ User Management        │    │
│  │ Travel       │  │ Payments     │  │ Analytics Dashboard    │    │
│  │ Screens/Vid  │  │ Pipeline     │  │ SEO Management         │    │
│  └──────────────┘  └──────┬───────┘  └────────────────────────┘    │
│                           │                                          │
│                    ┌──────┴───────┐                                  │
│                    │   Services   │                                  │
│                    │ • VideoGen   │                                  │
│                    │ • Payment    │                                  │
│                    │ • Shipping   │                                  │
│                    │ • AI Client  │                                  │
│                    │ • SMS        │                                  │
│                    └──────────────┘                                  │
└─────────────────────────────────────────────────────────────────────┘
```

### Layer 3: AI Pipeline & Automation

```
┌─────────────────────────────────────────────────────────────────────┐
│                      AI PIPELINE & AUTOMATION                         │
├─────────────────────────────────────────────────────────────────────┤
│                                                                     │
│  ┌──────────────────────────────────────────────────────────────┐   │
│  │                   Agentic Loop (Observer-Decider-Actor)       │   │
│  │  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────────┐│   │
│  │  │ Observer │→ │ Decider  │→ │  Actor   │  │   Learner    ││   │
│  │  │• SEO     │  │• LLM     │  │• Execute  │  │• Reinforcement│   │
│  │  │• Traffic │  │  (Kimi)  │  │• n8n jobs │  │• Feedback     ││   │
│  │  │• Quality │  │• Rules   │  │• APIs     │  │• Weights      ││   │
│  │  │• Weather │  │• Prio    │  │• Cache    │  │               ││   │
│  │  └──────────┘  └──────────┘  └──────────┘  └──────────────┘│   │
│  └──────────────────────────────────────────────────────────────┘   │
│                                                                     │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐              │
│  │ n8n Workflows │  │ ML Models    │  │ Python Pipeline│             │
│  │• County       │  │• Recommend   │  │• Video gen    │             │
│  │• Trade promo  │  │• Match score │  │• Image→3D     │             │
│  │• Pipeline     │  │• Pricing     │  │• Analysis     │             │
│  │• SEO refresh  │  │• Embeddings  │  │• Data proc    │             │
│  └──────────────┘  └──────────────┘  └──────────────┘              │
│                                                                     │
│  ┌──────────────────────────────────────────────────────────────┐   │
│  │                       Matching Pipeline                        │   │
│  │                                                                   │
│  │  Tourist Profile ─→ ┌──────────────────────┐ ─→ Flight Booked    │
│  │  • Budget           │  AI Matching Engine   │ ─→ Hotel Selected   │
│  │  • Interests        │  • Collaborative fltr │ ─→ Restaurant Rec   │
│  │  • Dates            │  • Content-based      │ ─→ Transfer Arranged│
│  │  • Group size       │  • Real-time pricing  │ ─→ Experience Pkg   │
│  │  • Origin           │  • Geo-context        │ ─→ Product Suggest  │
│  │                     └──────────────────────┘                      │
│  └──────────────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────────────┘
```

### Layer 4: Frontend Delivery

```
┌─────────────────────────────────────────────────────────────────────┐
│                      FRONTEND DELIVERY                                │
├─────────────────────────────────────────────────────────────────────┤
│                                                                     │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────────────────┐  │
│  │ Laravel Blade│  │ Three.js 3D  │  │ Mobile (future)          │  │
│  │• SEO-optimized│  │• 47 County   │  │• React Native           │  │
│  │• Server-side  │  │  Map         │  │• PWA                    │  │
│  │• Tailwind CSS │  │• Sector Exp  │  │• Offline support        │  │
│  │• CDN via Vercel│  │• Booth Tour  │  │• Push notifications    │  │
│  └──────────────┘  └──────────────┘  └──────────────────────────┘  │
│                                                                     │
│  CDN & Edge (Cloudflare):                                           │
│  • Static assets • 3D HTML • Videos • Images • API caching         │
│  • DDoS protection • SSL • Workers for geo-routing                  │
└─────────────────────────────────────────────────────────────────────┘
```

---

## Domain Model — 20 Business Domains (~200 tables)

### Core Platform (56 existing tables — kept as-is)

### E-Commerce Marketplace (new)
| Table | Purpose |
|-------|---------|
| `products` | All Kenyan products across 47 counties |
| `product_categories` | Category tree (agri, crafts, textiles, etc.) |
| `product_variants` | Size, color, weight variants |
| `product_images` | Gallery photos |
| `product_reviews` | Buyer ratings |
| `suppliers` | County-based suppliers |
| `supplier_products` | Supplier→product mapping |
| `inventory` | Stock per warehouse/variant |
| `warehouses` | Fulfillment centers |
| `shopping_carts` | Active carts (per user) |
| `cart_items` | Items in cart |
| `orders` | Customer orders |
| `order_items` | Line items |
| `order_status_history` | State machine log |
| `shipments` | Fulfillment records |
| `shipment_tracking` | Courier tracking events |
| `return_requests` | RMA process |
| `wishlists` | Saved items |
| `coupon_codes` | Discount campaigns |
| `price_history` | Pricing analytics |

### Travel & Tourism (new)
| Table | Purpose |
|-------|---------|
| `airlines` | Partner airlines |
| `airports` | All Kenyan airports |
| `flights` | Flight schedules/routes |
| `flight_inventory` | Seat availability |
| `flight_bookings` | Booking records |
| `flight_passengers` | Passenger manifests |
| `hotels` | Partner hotels |
| `hotel_rooms` | Room types/inventory |
| `hotel_bookings` | Booking records |
| `hotel_room_bookings` | Room→booking mapping |
| `restaurants` | Partner restaurants |
| `restaurant_tables` | Table layouts |
| `restaurant_menu_items` | Menu catalog |
| `restaurant_bookings` | Table reservations |
| `airport_transfers` | Transfer services |
| `transfer_bookings` | Pickup/dropoff records |
| `travel_packages` | Bundled experiences |
| `travel_itineraries` | Day-by-day plans |
| `attractions` | Tourist sites |
| `attraction_tickets` | Entry tickets |

### AI Pipeline (new)
| Table | Purpose |
|-------|---------|
| `pipeline_jobs` | Job definitions |
| `pipeline_runs` | Execution history |
| `pipeline_logs` | Step-by-step logs |
| `ml_models` | Model registry |
| `model_versions` | Version tracking |
| `embeddings` | Vector embeddings (product, user, content) |
| `recommendations` | Generated recommendations |
| `recommendation_logs` | Impression/click/conv logging |
| `user_segments` | ML-generated segments |
| `match_scores` | User→item match scores |
| `a_b_tests` | Experiment tracking |
| `feature_flags` | Rollout management |

### Advertising (new)
| Table | Purpose |
|-------|---------|
| `ad_campaigns` | Campaign definitions |
| `ad_groups` | Ad group hierarchy |
| `ad_creatives` | Image/video/text ads |
| `ad_targeting` | Audience targeting rules |
| `ad_placements` | Slot definitions |
| `ad_impressions` | Impression log (high volume) |
| `ad_clicks` | Click log |
| `ad_conversions` | Conversion tracking |
| `ad_budgets` | Budget pacing |
| `ad_publisher_payouts` | Revenue share |

### SEO & Content (new)
| Table | Purpose |
|-------|---------|
| `seo_metadata` | Per-page SEO metadata |
| `content_pages` | CMS pages |
| `content_blocks` | Reusable content |
| `redirects` | URL redirect rules |
| `sitemap_urls` | Sitemap tracking |
| `search_queries` | Internal search log |
| `keyword_tracking` | Search ranking history |

### Logistics & Shipping (new)
| Table | Purpose |
|-------|---------|
| `courier_partners` | Shipping carriers |
| `shipping_zones` | Regional zones |
| `shipping_rates` | Rate cards |
| `shipping_labels` | Generated labels |
| `pickup_requests` | Supplier pickup |
| `delivery_attempts` | Failed delivery log |

### Payments (expanded)
| Table | Purpose |
|-------|---------|
| `payment_gateways` | Gateway configs (M-Pesa, Stripe, etc.) |
| `payment_intents` | Unified payment record across all domains |
| `transaction_logs` | Raw gateway request/response payloads |
| `refunds` | Full/partial refund processing |
| `payment_disputes` | Buyer/seller dispute resolution |
| `seller_payouts` | Bulk payout to suppliers (weekly/monthly) |
| `settlement_batches` | Gateway settlement reconciliation |
| `settlement_transactions` | Individual txns in a settlement batch |
| `tax_rates` | VAT, withholding tax configs per product/county |
| `currency_rates` | KES→USD→EUR→GBP exchange rates |
| `advertiser_transactions` | Ad account top-ups and spend |

### Subscriptions (expanded)
| Table | Purpose |
|-------|---------|
| `subscription_features` | Feature catalog (max_booths, analytics, etc.) |
| `subscription_plan_features` | Plan→feature value mapping |
| `usage_logs` | Metered API/feature usage per subscriber |
| `billing_cycles` | Monthly/yearly billing periods |
| `invoices` | Generated invoice records |
| `invoice_items` | Individual line items per invoice |

---

## Payments & Subscriptions — Deep Dive

### Payment Flow Architecture

```
                    ┌──────────────────────────────────┐
                    │      User selects a service        │
                    │  (Booth booking, product, flight)  │
                    └────────────┬─────────────────────┘
                                 │
                    ┌────────────▼─────────────────────┐
                    │       Payment Intents              │
                    │  • Single unified record           │
                    │  • reference_type → points to      │
                    │    orders/bookings/escrows         │
                    │  • status: pending→processing→     │
                    │    confirmed→failed→refunded       │
                    └────────────┬─────────────────────┘
                                 │
              ┌──────────────────┼──────────────────┐
              ▼                  ▼                  ▼
    ┌─────────────────┐ ┌─────────────────┐ ┌─────────────────┐
    │    M-Pesa        │ │    Stripe        │ │   Escrow         │
    │  • Lipa Na M-Pesa│ │  • Card payment  │ │  • Buyer pays    │
    │  • M-Pesa Express│ │  • SEPA/methods  │ │  • Seller ships  │
    │  • Buy Goods Till │ │  • Subscription  │ │  • Buyer confirms│
    │  • Paybill        │ │    recurring    │ │  • Funds released │
    └────────┬────────┘ └────────┬────────┘ └────────┬────────┘
             │                   │                    │
             └───────────────────┼────────────────────┘
                                 ▼
                    ┌──────────────────────────────────┐
                    │        Transaction Logs           │
                    │  • Raw request/response payloads  │
                    │  • Audit trail for disputes       │
                    │  • Gateway reconciliation         │
                    └────────────┬─────────────────────┘
                                 │
                    ┌────────────▼─────────────────────┐
                    │        Settlement Batches         │
                    │  • Daily/weekly gateway payouts   │
                    │  • Fee calculation per gateway    │
                    │  • Net amount to platform bank    │
                    └────────────┬─────────────────────┘
                                 │
              ┌──────────────────┼──────────────────┐
              ▼                  ▼                  ▼
    ┌──────────────┐   ┌──────────────┐   ┌────────────────┐
    │  Platform     │   │  County 70%  │   │  Seller Payout  │
    │  Revenue 30%  │   │  Revenue     │   │  (less comm.)   │
    │  (Ops cost)   │   │  (Dev fund)  │   │                 │
    └──────────────┘   └──────────────┘   └────────────────┘
```

### 3-Tier Subscription Model

| Tier | Target | Price (KES) | Key Features | County Benefit |
|------|--------|-------------|-------------|----------------|
| **Free** | All users | 0 | 1 booth listing, basic profile | County gets free visibility |
| **County Premium** | County govt | 50,000/mo | 20 booths, analytics, SEO boost, priority support | County promotes all local businesses |
| **Exhibitor Pro** | Businesses | 5,000/mo | 5 booths, analytics, livestream, multi-event | County earns 30% commission |
| **Enterprise** | Corporates | 50,000/mo | Unlimited booths, API access, white-label, dedicated support | County earns 25% commission |

### County Revenue Model

Each county has its own financial configuration in TiDB:

```
county_financial_config:
  ├── revenue_share_pct: 70        # 70% to county, 30% to platform
  ├── mpesa_paybill: "123456"       # County-specific M-Pesa till
  ├── settlement_period: "monthly"  # Monthly/weekly payouts
  ├── county_wallet_balance: 0      # Running balance in KES
  └── subscription_discount: 0.20   # 20% discount for county residents
```

**How money flows to counties:**

```
Product sold (1,000 KES)
  ├── Platform fee (10%):    100 KES
  ├── Payment gateway (3%):   30 KES
  ├── County revenue share:   609 KES (70% of remaining 870)
  └── Seller nets:           261 KES (30% of remaining 870)
```

**Revenue sources per county:**

| Source | County Share | Platform Share |
|--------|-------------|----------------|
| Booth bookings | 70% | 30% |
| Marketplace sales | 70% | 30% |
| Travel commissions | 50% | 50% |
| Ad revenue (local) | 80% | 20% |
| Subscription fees | 30% | 70% |
| Escrow fees | 50% | 50% |

### Reconciliation & Settlement

```
Daily cutoff (23:59 EAT)
  │
  ├── Aggregate all intents by county
  │
  ├── Deduct gateway fees + platform commission
  │
  ├── Generate settlement_batch per county
  │
  ├── Auto-transfer to county M-Pesa paybill
  │     (or accumulate for monthly lump sum)
  │
  └── Log in county_wallet_transactions table
```

### Subscription + Usage Metering

```
User signs up for "County Premium" (50,000 KES/mo)
  │
  ├── billing_cycles created (monthly periods)
  ├── invoice generated
  ├── payment_intent via M-Pesa/Stripe
  │
  After payment:
  ├── subscription_plan_features grants:
  │   • max_booths = 20
  │   • has_analytics = true
  │   • has_livestream = true
  │
  usage_logs tracks:
  ├── Booth slots used (counted against max)
  ├── Analytics queries this month
  ├── API calls

---

## Database Recommendation: **TiDB**

**TiDB is the optimal choice** for this platform because:

1. **MySQL wire-protocol compatible** — Your existing Laravel + 56 tables work with **zero code changes**. Same Eloquent queries, same migrations, same everything.

2. **Auto-sharding (no sharding logic)** — TiDB automatically distributes data across nodes. When you grow from 10 GB to 10 TB to 1 PB, you just add nodes. No manual partitioning, no Vitess complexity.

3. **Petabyte-scale HTAP** — TiFlash columnar replicas let you run real-time analytics directly on production data. No ETL to Snowflake/Redshift. Analytics queries auto-route to TiFlash.

4. **Multi-region active-active** — Deploy in US-East, EU-West, Asia-Pacific simultaneously. Users get local read/write with automatic conflict resolution.

5. **Linear write scaling** — Unlike Aurora's single-primary bottleneck, TiDB writes scale linearly with nodes. 100M transactions/day? Add nodes.

6. **Zero-downtime operations** — Add indexes, change schema, scale up/down without locking. Critical for a 24/7 marketplace.

7. **Cost at scale** — Commodity hardware. 10 nodes of 8 CPU/32GB RAM can handle 1 TB+ for ~$500/month on bare metal, much less than Aurora at that scale.

### Migration Path

```
SQLite (local dev) → TiDB Serverless (free tier, 5GB) → TiDB Dedicated (prod)
```

1. **Local:** SQLite (already configured) — for development
2. **Staging:** TiDB Serverless (free up to 5GB, no server to manage)
3. **Production:** TiDB Dedicated cluster (auto-scale, multi-region)

Migration is a single `.env` change:
```
DB_CONNECTION=mysql
DB_HOST=gateway01.us-west-2.prod.aws.tidbcloud.com
DB_PORT=4000
DB_DATABASE=kicc
DB_USERNAME=xxxxx.root
DB_PASSWORD=xxxxx
```

**Zero code changes.** Same Laravel migrations. Same Eloquent.

---

## Integration Architecture

```
                    ┌──────────────────────────────┐
                    │      Cloudflare (CDN/Edge)    │
                    │  • Static assets              │
                    │  • API caching                │
                    │  • Workers (geo-routing)       │
                    │  • DDoS protection             │
                    └──────────┬───────────────────┘
                               │
                    ┌──────────▼───────────────────┐
                    │     Vercel (Laravel)         │
                    │  • Blade SSR                 │
                    │  • Filament admin            │
                    └──────────┬───────────────────┘
                               │
                    ┌──────────▼───────────────────┐
                    │     Laravel Backend           │
                    │  • REST API                   │
                    │  • Queue workers              │
                    │  • WebSockets (Laravel Reverb) │
                    └──────────┬───────────────────┘
                               │
          ┌────────────────────┼────────────────────┐
          ▼                    ▼                    ▼
  ┌──────────────┐   ┌──────────────┐   ┌──────────────────┐
  │   TiDB (DB)  │   │  Cloudflare  │   │  External APIs   │
  │              │   │  R2 (Assets) │   │  • M-Pesa        │
  │  • 200+ tbls │   │  • Videos    │   │  • Stripe        │
  │  • Auto-shard│   │  • Images    │   │  • Africa Talking│
  │  • HTAP      │   │  • 3D HTML   │   │  • Airlines GDS  │
  │  • Multi-reg │   │  • Backups   │   │  • Shipping APIs │
  └──────────────┘   └──────────────┘   │  • Google APIs   │
                                        └──────────────────┘
```

---

## Data Flow Examples

### Tourist Books Kenyan Experience
```
1. User browses counties/tourism (Blade SSR via Vercel)
2. Agentic loop observes -> triggers recommendation refresh
3. AI pipeline scores user against: flights, hotels, restaurants, products
4. User sees personalized recommendations
5. Books: flight (GDS API) + hotel (TiDB) + restaurant (TiDB table booking)
6. Pipeline auto-books airport transfer (matching driver)
7. Payment: M-Pesa/Stripe -> TiDB transaction log
8. Order: Kenyan product recommended -> added to cart -> shipped
9. Agentic loop learns from booking -> updates match weights
10. Notification: SMS (Africa Talking) + email
```

### Seller Lists Product
```
1. Seller from e.g., Kiambu lists avocado oil
2. Product written to TiDB (auto-sharded by product_id)
3. n8n workflow triggers: SEO metadata generation (agentic loop)
4. AI pipeline generates embedding vector -> stored in TiDB
5. Product visible in marketplace with global shipping calculation
6. TiFlash columnar replica enables real-time seller analytics
7. When ordered: inventory decremented, shipment created
8. Courier partner API called for pickup
9. Tracking events flow back through webhooks
```

---

## Deployment Strategy

```
Phase 1 (Now)     Phase 2 (Next)         Phase 3 (Scale)
─────────────     ──────────────         ──────────────
• SQLite local    • TiDB Serverless      • TiDB Dedicated cluster
• Laravel serve   • Vercel deployment    • Multi-region deployment
• Basic features  • E-commerce launch    • Global CDN + edge
• 56 tables       • Travel booking       • 200+ tables
• 3D experiences  • AI pipeline v1       • Full ML pipeline
                  • n8n automation       • Petabyte-scale
                  • M-Pesa integration   • 24/7 SRE
```

---

## Project Structure

```
/home/kicc/Desktop/kicc/
├── kicc-platform/           ← NEW: Full self-hosted Laravel project
│   ├── app/                 PHP backend (32 models + 20 controllers + services)
│   ├── database/
│   │   ├── schema.sql       56 current tables
│   │   ├── schema-commerce.sql  E-commerce + travel + ads + pipeline tables
│   │   └── schema-full.sql  Complete ~200 table schema
│   ├── public/3d/           Three.js experiences
│   └── setup.sh             One-command setup
│
├── kenya-3d-platform/       ← EXISTING: Vercel deployment (unchanged)
│   ├── laravel-backend/     Original Laravel for Vercel
│   ├── pipeline/            Python pipeline (video gen, image analysis)
│   ├── agentic_loop/        AI agent system
│   ├── n8n-workflows/       Automation workflows
│   ├── seo-engine/          SEO optimization
│   ├── cloudflare-worker/   Edge functions
│   └── scripts/             Utility scripts
│
└── start-preview.sh         Static preview server (port 8081)
```
