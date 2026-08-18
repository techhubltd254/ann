# Monitoring Stack (Prometheus + Grafana)

Monitoring runs **on the production droplet** (167.172.62.234), bound to localhost only.

## Access

All services are bound to `127.0.0.1` — there is **no public exposure**. Open an SSH tunnel:

```bash
ssh -L 3000:127.0.0.1:3000 root@167.172.62.234
# then browse http://localhost:3000
```

- Login: `admin`
- Password: stored in `~/.config/` local secret (laptop: `.grafana-admin-password.txt`, not committed)
- Signup is disabled; admin password was changed from default.

## Services

| Service | Port | Systemd unit |
|---|---|---|
| Prometheus | 9090 (loopback) | `prometheus` |
| Grafana | 3000 (loopback) | `grafana-server` |
| node_exporter | 9100 | `prometheus-node-exporter` |
| redis_exporter | 9121 | `redis_exporter` |
| nginx-prometheus-exporter | 9113 | `nginx-exporter` |
| php-fpm exporter (Lusitaniae) | 9253 | `phpfpm-exporter` |
| Laravel scheduler | — | `kicc-scheduler` (`artisan schedule:work`) |
| Laravel Pulse daemon | — | `kicc-pulse` (`artisan pulse:check`) |

Memory footprint: ~100 MB total.

## Config files (droplet)

- `/etc/prometheus/prometheus.yml` — scrape config, 4 jobs (node, redis, nginx, php-fpm)
- `/etc/prometheus/alerts.yml` — 6 alert rules
- `/etc/grafana/grafana.ini` — smtp (gmail) enabled, signup off, `http_addr = 127.0.0.1`
- `/etc/grafana/provisioning/datasources/prometheus.yml` — Prometheus datasource
- `/etc/grafana/provisioning/dashboards/kicc.yml` + `/var/lib/grafana/dashboards/kicc-overview.json` — "KICC Droplet Overview" dashboard (CPU, load, disk, memory, redis, nginx, PHP-FPM, network)
- `/etc/grafana/provisioning/alerting/kicc.yaml` — email contact point → techhubltd254@gmail.com
- `/etc/nginx/conf.d/monitoring.conf` — serves nginx stub_status + fpm status on `127.0.0.1:9099` (internal only)
- PHP-FPM `pm.status_path = /fpm_status` added to pool

## Alerts (Prometheus)

- HostDown (critical)
- HighCPU > 90% (10m)
- HighLoad > 4 (10m)
- DiskSpaceLow > 85%
- LowMemory < 10%
- HighPhpFpmBusy > 80% of max_children

Alerts evaluate in Prometheus; Grafana routes them to email via `kicc-email` contact point.

## Notes

- nginx `stub_status` is exposed only on `127.0.0.1:9099` (not on public vhosts).
- Grafana uses Gmail SMTP (`noreply@kicctest.org` from-address), app password from `.env` `MAIL_PASSWORD`.
- The Cloudflare API token has no DNS:Edit, so no public `monitor.*` subdomain was created; SSH tunnel is the access path.
- `pulse:check` is a **long-running daemon** (loops forever). It runs as systemd unit `kicc-pulse`, NOT via the scheduler. Restart it on deploy with `systemctl restart kicc-pulse` (or `php artisan pulse:restart` for graceful).
- Only ONE scheduler may run `schedule:work`: keep `/etc/cron.d/kicc` disabled and rely on `kicc-scheduler.service`.

## Incident log

- 2026-08-18: Scheduler storm on prod. `pulse:check` was wrongly scheduled via `schedule:run` every minute; being a daemon it never exited, stacking schedule:run processes until OOM (exit 137) and leaving a stale `laravel:pulse:check` cache lock that blocked subsequent runs. Load hit 53 on 2 vCPU. Fixed by running pulse:check as its own systemd daemon (`kicc-pulse`), removing it from `routes/console.php`, killing stuck processes, and clearing the stale Redis lock.