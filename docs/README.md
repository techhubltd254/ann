# KICC Digital Economy Platform

**Kenyan Digital Economy Super-App** — Exhibition management, e-commerce marketplace, travel/tourism booking, AI recommendation pipeline, advertising engine, and subscription platform.

## Platform Domains

| Domain | Description | Tables |
|--------|-------------|--------|
| **🏛 Exhibition** | Booth booking, event mgmt, venues, ticketing, schedules | 56 (core) |
| **🛒 Marketplace** | Products (47 counties), global shipping, seller dashboard, escrow | 30 (commerce) |
| **✈️ Travel** | Flights, hotels, restaurants, airport transfers, packages, itineraries | 28 (travel) |
| **🤖 AI Pipeline** | ML models, embeddings, recommendations, match scoring, A/B testing | 20 (ai-pipeline) |
| **📢 Advertising** | Campaigns, programmatic bidding, geo-targeting, attribution | 15 (advertising) |
| **💳 Payments** | Gateways, payouts, settlements, refunds, disputes, billing | 16 (payments) |
| **🔍 SEO** | CMS pages, SEO metadata, redirects, sitemaps, analytics | 13 (seo-content) |
| **+ Core** | Users, auth, permissions, audit, notifications, counties, sectors | 56 (core) |

**Total: ~178 tables**

## Architecture

```
┌─────────────────────────────────────────────────────────────────────┐
│                    KICC DIGITAL ECONOMY PLATFORM                      │
├─────────────────────────────────────────────────────────────────────┤
│                                                                     │
│  🏛 Exhibition       🛒 Marketplace       ✈️ Travel & Tourism        │
│  • Booth booking     • Products (47 cnty)  • Flight booking          │
│  • Event mgmt        • Global shipping     • Hotel / restaurant      │
│  • Venue mgmt        • Real-time tracking   • Airport transfer       │
│  • Ticketing         • Seller dashboard    • Travel packages         │
│                                                                     │
│  🤖 AI Pipeline             📢 Advertising         💳 Payments      │
│  • Match tourists→exp       • Campaign mgmt        • M-Pesa / cards  │
│  • Product recommendations  • Programmatic bidding  • Escrow          │
│  • Dynamic pricing          • Geo-targeting        • Payouts          │
│  • SEO optimization         • Attribution          • Subscriptions    │
│                                                                     │
│  🔗 Integration: n8n workflows • Agentic loop • Cloudflare workers   │
└─────────────────────────────────────────────────────────────────────┘
```

| Layer | Technology | Notes |
|-------|-----------|-------|
| Backend | PHP 8.3+ / Laravel 13 | Full MVC, REST API, Filament admin |
| Database | **SQLite** (local) → **TiDB** (production) | ~178 tables, HTAP, auto-sharding |
| Storage | Local → Cloudflare R2 / S3 | 2.4 GB screen videos, product images |
| Video Gen | FFmpeg 8.0.1 + Pillow | Ken Burns showcase generation |
| 3D Frontend | Three.js (CDN) | 47-county map, sector explorer, booth tour |
| Edge | Cloudflare Workers | Geo-routing, API caching, DDoS |
| Automation | n8n + Agentic Loop | Content pipeline, SEO, recommendations |
| AI | Python + Kimi LLM | Matching engine, embeddings, scoring |

## Quick Start (Local — SQLite)

```bash
# Prerequisites
sudo apt install php8.3-cli php8.3-mbstring php8.3-xml php8.3-curl php8.3-gd php8.3-sqlite3 php8.3-mysql php8.3-bcmath php8.3-zip ffmpeg composer

# Setup
cd kicc-platform
chmod +x setup.sh
./setup.sh

# Or manually:
composer install
touch storage/app/kicc.sqlite
php artisan key:generate
php artisan migrate
php artisan db:seed --class=ScreenSeeder
php artisan storage:link

# Start dev server
php artisan serve --port=8000
```

Open **http://localhost:8000**

### Access the 3D Experiences
- **47 Counties 3D Map** — `http://localhost:8000/3d/kenya_3d_map.html`
- **Mombasa & Kilifi Sector Explorer** — `http://localhost:8000/3d/sector_map.html`
- **Exhibition Hall Booth Tour** — `http://localhost:8000/3d/booth_viewer.html`
- **Screen Video Directory** — `http://localhost:8000/screens`

### Generate Videos
```bash
php artisan screen:register-images --scan
php artisan screen:generate
php artisan screen:generate county_main_14   # Single screen
```

## Database Recommendation: **TiDB**

**TiDB is the optimal choice** for this platform:

| Requirement | TiDB | Aurora MySQL | CockroachDB |
|------------|------|-------------|-------------|
| MySQL compatible | ✅ Wire protocol | ✅ Full | ⚠️ PostgreSQL syntax |
| Auto-sharding | ✅ Built-in | ❌ Single primary | ✅ Built-in |
| Write scaling | ✅ Linear (add nodes) | ❌ Vertical only | ✅ Linear |
| HTAP (analytics on live data) | ✅ TiFlash built-in | ❌ Requires Redshift/Snowflake | ❌ No columnar |
| Multi-region active-active | ✅ Yes | ❌ Read replicas only | ✅ Yes |
| Petabyte scale | ✅ Yes | ❌ 256 TiB limit | ✅ Yes |
| Zero-downtime schema changes | ✅ Yes | ⚠️ Limited | ✅ Yes |
| Cost efficiency at scale | ✅ Commodity hardware | ⚠️ Expensive large instances | ✅ Commodity hardware |

### Migration Path

```
SQLite (local dev) → TiDB Serverless (free tier, 5GB) → TiDB Dedicated (prod)
```

Migration is a single `.env` change:
```
DB_CONNECTION=mysql
DB_HOST=gateway01.us-west-2.prod.aws.tidbcloud.com
DB_PORT=4000
DB_DATABASE=kicc
DB_USERNAME=xxxxx.root
DB_PASSWORD=xxxxx
```

**Zero code changes.** Same Laravel migrations. Same Eloquent queries.

## Project Structure

```
kicc-platform/
├── app/
│   ├── Console/Commands/       # Artisan commands (video gen, image reg)
│   ├── Http/
│   │   ├── Controllers/Api/    # REST API endpoints
│   │   ├── Controllers/Web/    # Web controllers
│   │   └── Middleware/         # AgenticSEO middleware
│   ├── Filament/Resources/     # Admin panel (19 resources)
│   ├── Models/                 # 32 Eloquent models
│   └── Services/               # VideoService, SMSService, PaymentService
├── config/
│   └── screens.php             # 18 screen presets + 47 county 3D positions
├── database/
│   ├── schema.sql              # Core 56 tables
│   ├── schema-commerce.sql     # E-commerce 30 tables
│   ├── schema-travel.sql       # Travel 28 tables
│   ├── schema-ai-pipeline.sql  # AI Pipeline 20 tables
│   ├── schema-advertising.sql  # Advertising 15 tables
│   ├── schema-payments.sql     # Payments 16 tables
│   ├── schema-seo-content.sql  # SEO/Content 13 tables
│   ├── schema-full.sql         # Complete ~178 tables (loads all above)
│   ├── migrations/             # 28 Laravel migration files
│   ├── seeders/                # 6 seeders
│   └── data/                   # JSON/CSV reference data
├── public/
│   ├── 3d/                     # Three.js experiences (static HTML)
│   │   ├── kenya_3d_map.html   # 47-county interactive 3D map (7.1 MB)
│   │   ├── sector_map.html     # Mombasa/Kilifi sector explorer
│   │   └── booth_viewer.html   # Exhibition hall 3D tour + video overlay
│   └── storage/ → storage/app/public/
├── resources/views/            # Blade templates (20+)
├── routes/
│   ├── web.php                 # 15 web routes
│   ├── api.php                 # 30+ API endpoints
│   └── console.php             # Artisan command registration
├── storage/app/public/
│   ├── screens/                # 18 auto-generated MP4 videos
│   ├── counties/               # 47 county dirs + photos
│   └── kicc.sqlite             # SQLite database (local dev)
├── .env                        # Local config (SQLite)
├── BLUEPRINT.md                # Full architecture blueprint
├── setup.sh                    # Setup script
└── README.md                   # This file
```

## Screen Videos

18 auto-generated showcase videos (2.4 GB total) stored in `storage/app/public/screens/`:

| Screen | Duration | Images | Description |
|--------|----------|--------|-------------|
| county_main_* (15) | 60s each | ~12-25 | Per-county tourism/culture showcases |
| coastal_overview | 60s | 25 | Coast region montage |
| sector_mombasa | 60s | 18 | Mombasa economic sectors |
| sector_kilifi | 60s | 18 | Kilifi economic sectors |

View at **http://localhost:8000/screens** or click any exhibition booth → **▶ Play Showcase**

## Key Features

- **3D Kenya Map**: Interactive Three.js visualization of all 47 counties with terrain meshes
- **Sector Explorer**: Per-county economic sector breakdown (Mombasa & Kilifi)
- **Booth Viewer**: 3D exhibition hall with click-to-play video showcases
- **AI Agentic Loop**: Self-optimizing system (SEO, content, recommendations, pipeline tuning)
- **n8n Automation**: County content pipeline, international trade promotion, SBS pipeline
- **SEO Engine**: Agentic SEO middleware, keyword tracking, content generation queue
- **Escrow Trade**: Buyer/seller escrow with courier tracking and dispute resolution
- **Multi-tenant**: 47 county portals, user roles, OAuth2 API, Filament admin
