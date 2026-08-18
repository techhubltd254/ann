# KICC Platform — API Runbook

Operational reference for the public API (base URL `https://kicctest.org/api`).
Machine-readable contract: [`docs/openapi.yaml`](../openapi.yaml).

## Architecture

```
Client ──► Cloudflare edge (kicctest-gateway worker)
              │  auth JWT + edge caching (Cache-Public-Response)
              ▼
           origin.kicctest.org ──► nginx (php8.4-fpm) ──► Laravel ──► TiDB (TiDB Cloud, :4000)
```

- Most public `GET` endpoints are cached at the edge (`x-cache: MISS/HIT`).
- Admin routes and the `/pulse` dashboard require a session; API mutating routes require a Sanctum bearer token.
- The gateway worker does not forward unknown paths — API routes are `/api/*`.

## Auth

1. `POST /api/auth/register` — `{name, email, password}` → 201 `{user, token}`.
2. `POST /api/auth/login` — `{email, password}` → `{user, token}`.
3. Send `Authorization: Bearer <token>` on subsequent requests.

> Rate limits: register/login are `throttle:20,1`; checkout is `throttle:30,1`.

## Key flows

| Flow | Endpoints |
|---|---|
| Browse catalogue | `GET /counties`, `/counties/{slug}`, `/counties/{slug}/sectors`, `/exhibitions`, `/venues`, `/ticket-types`, `/booths` |
| Booking | `POST /bookings/ticket`, `/bookings/booth`; `GET /bookings`, `/bookings/{id}`; `POST /bookings/{id}/cancel` |
| Ticketing | `GET /tickets`, `/tickets/lookup/{code}`, `POST /tickets/{code}/check-in` |
| Media | `GET/POST /media`, `GET/DELETE /media/{asset}`, `POST /media/{asset}/pipeline`, `GET /media/jobs/{job}/status` |
| Payments | `POST /mpesa/callback` (Daraja STK), `POST /webhooks/stripe` (signature-verified) |
| Escrow/Disputes | `POST /escrow`, `/escrow/{id}/release|dispute|tracking`, `POST /disputes` |
| AI | `POST /match-destination`, `/image-search`, `/fraud-check`; `GET /recommendations`, `/forecast`, `/search/semantic` |
| USSD | `POST /ussd/callback` (Africa's Talking, token-protected) |
| MCP | `GET /mcp`, `/mcp/resources`, `GET /mcp/resources/{type}`, `POST /mcp/tools/{name}` |
| Verification | `POST /verification/kra-pin`, `/verification/national-id`, `GET /verification/status` |

## Webhooks (inbound, not bearer-authenticated)

- **Stripe** — verifies `Stripe-Signature` vs `services.stripe.webhook_secret`; replay-safe (idempotent on event id). Update `payment_intents` + `transaction_logs`.
- **Courier** — HMAC-signed, replay-safe.
- **n8n** — automation trigger.
- **M-Pesa** — Daraja C2B callback; idempotency handled app-side.

## USSD (Africa's Talking)

- Callback: `POST /api/ussd/callback` with form fields `sessionId`, `serviceCode`, `phoneNumber`, `text`.
- Reply is plain text: `CON <menu>` keeps the session, `END <msg>` terminates.
- Menu: `1` next exhibition, `2` my bookings, `3` county info, `4` trade inquiry, `0` exit.
- If `AFRICASTALKING_USSD_CALLBACK_TOKEN` is set, every request must include it as a form field; otherwise requests are 401.
- AT must be pointed at `https://kicctest.org/api/ussd/callback` in the Africa's Talking USSD sandbox/live console.

## Scheduled jobs (prod, via `kicc-scheduler.service` → `schedule:work`)

| Schedule | Command |
|---|---|
| hourly (min 0) | `travel:refresh-recommendations` |
| hourly (min 30) | `backup-db.sh hourly` → R2 `db-backups/hourly/` (keep 48) |
| 02:00 | `recommendations:build` |
| 02:30 | `embeddings:build` |
| 02:45 | `backup-db.sh daily` → R2 `db-backups/daily/` (keep 14; monthly on the 1st, keep 12) |
| 03:00 | `vendors:score` |
| 04:00 | `billing:run` |
| 04:30 | `analytics:trends` |
| every 15 min | `anomalies:detect` |
| Sunday 05:00 | `dba:index-audit` |

`pulse:check` runs as a **separate daemon** (`kicc-pulse.service`), never via the scheduler (it is a long-lived loop).

## Security hardening (2026-08-18)

- **APP_KEY rotated** on prod/local (the old key was committed in `.env.example`). Never put a real key in `.env.example` — the committed value is a dev-only placeholder; regenerate per env with `php artisan key:generate`.
- **`/opt/kicc-laravel/.env`** is `kicc:www-data` `640` (PHP-FPM runs as www-data; backup script runs as kicc). Keep it 640 — a tighter 600 breaks FPM.
- **USSD callback token**: `AFRICASTALKING_USSD_CALLBACK_TOKEN` is now set on prod. Requests without the `token` form field return 401. Rotate it by editing `/opt/kicc-laravel/.env` then `php artisan config:clear && systemctl restart php8.4-fpm`.
- **Trusted proxies** are restricted to Cloudflare published CIDRs in `bootstrap/app.php` (the UFW firewall also limits 80/443 to those ranges). If Cloudflare publishes new ranges, update both.
- **Content-Security-Policy** is set in `app/Http/Middleware/SecurityHeaders.php`. It is intentionally permissive (Tailwind CDN `cdn.tailwindcss.com`, hls.js `cdn.jsdelivr.net`, Google Fonts). When Tailwind/hls.js are bundled, tighten to `default-src 'self'`.
- Sessions use `SESSION_SECURE_COOKIE=true` on prod.

## Incident response

1. **Check health**: `curl -sI https://kicctest.org/` (expect 200), `curl -s -o /dev/null -w '%{http_code}' https://kicctest.org/api/counties`.
2. **Edge vs origin**: hit `https://origin.kicctest.org/` directly to bypass Cloudflare.
3. **Logs**: `/opt/kicc-laravel/storage/logs/laravel.log`, `/var/log/nginx/error.log`, `journalctl -u kicc-scheduler -u kicc-pulse`.
4. **Metrics**: Prometheus/Grafana via SSH tunnel (`ssh -L 3000:127.0.0.1:3000 root@167.172.62.234`).
5. **Scheduler**: `systemctl status kicc-scheduler kicc-pulse`. If a scheduled run hangs (e.g. a daemon-type command), kill it and clear its cache lock (see monitoring incident log).
6. **DB**: TiDB Cloud dashboard for slow queries; `dba:index-audit` runs weekly. Backup restore: see `dr-runbook.md`.

## Deploying API changes

Files are deployed to `/opt/kicc-laravel` via scp/rsync (no git on prod), then:

```bash
chown -R kicc:kicc /opt/kicc-laravel/app /opt/kicc-laravel/config /opt/kicc-laravel/routes
cd /opt/kicc-laravel && php artisan optimize:clear
systemctl restart php8.4-fpm
# pulse:check needs restart to pick up code changes
systemctl restart kicc-pulse
```
