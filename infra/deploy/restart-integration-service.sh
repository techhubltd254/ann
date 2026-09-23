#!/bin/bash
# Cron health check for the KICC Integration Layer (Node.js).
# Run every minute from crontab:
#   * * * * * /opt/kicc-laravel/infra/deploy/restart-integration-service.sh
set -euo pipefail

APP_DIR="/opt/kicc-laravel"
LOG="$APP_DIR/storage/logs/integration-service.log"
PORT="${INTEGRATION_PORT:-8787}"
PID_FILE="$APP_DIR/storage/kicc-integration.pid"

if curl -s -o /dev/null --max-time 3 "http://127.0.0.1:$PORT/health" 2>/dev/null; then
    exit 0
fi

echo "[$(date)] integration service DOWN — restarting" >> "$LOG"
bash "$APP_DIR/infra/deploy/start-integration-service.sh" "$APP_DIR" >> "$LOG" 2>&1