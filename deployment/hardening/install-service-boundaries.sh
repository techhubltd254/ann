#!/usr/bin/env bash
set -euo pipefail
ROOT=/opt/kicc-laravel
id kicc-services >/dev/null 2>&1 || useradd --system --user-group --no-create-home --home-dir /nonexistent --shell /usr/sbin/nologin kicc-services
mkdir -p "$ROOT/integrations-service/run"
chown -R kicc-services:www-data "$ROOT/integrations-service/run"
find "$ROOT/integrations-service/run" -type d -exec chmod 2750 {} +
find "$ROOT/integrations-service/run" -type f -exec chmod 0640 {} +
if test -f "$ROOT/integrations-service/.env"; then chown root:kicc-services "$ROOT/integrations-service/.env"; chmod 0640 "$ROOT/integrations-service/.env"; fi
for service in kicc-algorithms kicc-integration kicc-pipeline-bus kicc-consumers; do
 mkdir -p "/etc/systemd/system/$service.service.d"
 cat > "/etc/systemd/system/$service.service.d/30-service-boundaries.conf" <<'EOF'
[Service]
User=kicc-services
Group=kicc-services
SupplementaryGroups=www-data
UMask=0027
NoNewPrivileges=true
PrivateTmp=true
ProtectHome=true
ProtectSystem=strict
ReadWritePaths=/opt/kicc-laravel/storage /opt/kicc-laravel/bootstrap/cache /opt/kicc-laravel/integrations-service/run
RestrictSUIDSGID=true
CapabilityBoundingSet=
AmbientCapabilities=
EOF
 chmod 0644 "/etc/systemd/system/$service.service.d/30-service-boundaries.conf"
done
systemctl daemon-reload
systemctl restart kicc-algorithms kicc-integration kicc-pipeline-bus kicc-consumers
for n in $(seq 1 20); do
 if curl -fsS --max-time 2 http://127.0.0.1:8400/health >/dev/null && curl -fsS --max-time 2 http://127.0.0.1:8787/health >/dev/null && curl -fsS --max-time 2 http://127.0.0.1:8790/health >/dev/null && curl -fsS --max-time 2 http://127.0.0.1:8791/health >/dev/null; then echo FOUR_LEAST_PRIVILEGE_SERVICES_HEALTHY;exit 0;fi
 sleep 2
done
echo SERVICE_BOUNDARY_HEALTH_CHECK_FAILED >&2
exit 1
