# KICC ONE

One folder. Best of all four codebases. Read **[ARCHITECTURE.md](ARCHITECTURE.md)** first.

```
kicc-one/
├── ARCHITECTURE.md     ← master combo architecture (READ THIS)
├── platform/           ← Laravel 13 public website → kicctest.org (Cloudflare)
├── engine/             ← Kotlin/Spring Boot admin API (4 tiers, fat jar :8091)
├── admin-web/          ← React 18 admin console (Vite :5173 → engine)
├── admin-mobile/       ← Expo RN offline-first field admin (SQLCipher, biometric)
├── pipeline/           ← media/AI workers: ffmpeg ladder, Wan2GP, Hailuo, Tripo3D,
│                         ROAD, agentic_loop, n8n-workflows, seo-engine, scrapers
├── edge/               ← Cloudflare Workers (gateway + R2 proxy)
├── distro/             ← manual-install packaging + OTA update channel (jar/apk
│                         artifacts are built by CI, not committed)
├── data/               ← scraped_counties (41/47) + reference JSON + legacy seeds
├── brand/              ← BRAND.md — kicc.co.ke verified palette/venues/contacts
├── infra/              ← Dockerfiles, docker-compose, CI/CD, cron/systemd, scripts
├── docs/               ← BLUEPRINT (md/pdf/html), roadmap, ops runbooks, .docx specs
└── _archive/           ← superseded code, read-only: kicc-admin, kicc-portable-admin,
                          kenya-3d-laravel, figma-prototype
```

## The system in one paragraph
Admins edit everything through the **Kotlin admin app** (KICC mother / National / 47 County /
large-corp Exhibitor tiers), manually installed per org with **signed OTA updates**. Writes land
in **TiDB** (single source of truth). The **Laravel** website on **kicctest.org** (behind
**Cloudflare**) renders county/sector/national content. Media uploads (4K/8K video, 3D) go
through a **transcode pipeline** (HLS ladder + WebM + WebP posters + Draco .glb) into **R2**,
published via HMAC webhook with cache-tag purge — so the site stays fast no matter what
admins upload.

## Quick start (dev)
```bash
cd infra && docker compose up -d          # mysql8 (TiDB-compat), redis, minio, mailpit
cd ../platform && composer install && php artisan migrate --seed && php artisan serve
cd ../engine && ./gradlew bootRun          # :8091  (swagger /swagger-ui.html)
cd ../admin-web && npm ci && npm run dev   # :5173  (proxies /api → :8091)
```

## Hard rules
1. **No secrets in this repo.** Credentials live in your secrets manager. See `docs/ops/prod-go-live.md`.
2. Admins never touch the Laravel DB directly — all edits via the engine API (audited, hash-chained).
3. Originals never served to browsers — only pipeline derivatives (HLS/WebM/WebP/.glb).
4. Brand per `brand/BRAND.md`. UX/a11y standards per ARCHITECTURE.md §6 — enforced by review + lint.
