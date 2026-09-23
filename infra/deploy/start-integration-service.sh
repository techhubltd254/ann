#!/bin/bash
# Start the KICC Integration Layer Node.js service.
# Called from deploy.sh or manually after deploy.
set -euo pipefail

if [ -n "${1:-}" ]; then
    APP_DIR="$(cd "$1" && pwd)"
else
    APP_DIR="$(cd "$(dirname "$0")/../.." && pwd)"
fi

LOG="$APP_DIR/storage/logs/integration-service.log"
PORT="${INTEGRATION_PORT:-8787}"
SERVICE_DIR="$APP_DIR/integrations-service"

if [ ! -d "$SERVICE_DIR" ]; then
    echo "ERROR: $SERVICE_DIR not found"
    exit 1
fi

mkdir -p "$APP_DIR/storage/logs" "$SERVICE_DIR/run/artifacts"

# Kill existing if running (exact invocation only)
pkill -f "node.*integrations-service/api/server\.js" 2>/dev/null || true
sleep 1

# Copy .env if exists
if [ -f "$APP_DIR/.env.integration" ]; then
    cp "$APP_DIR/.env.integration" "$SERVICE_DIR/.env"
fi

cd "$SERVICE_DIR"
setsid node api/server.js >> "$LOG" 2>&1 &
echo $! > "$APP_DIR/storage/kicc-integration.pid"
sleep 2

# Health check
for i in 1 2 3 4 5; do
    if curl -s -o /dev/null --max-time 2 "http://127.0.0.1:$PORT/health" 2>/dev/null; then
        echo "Integration service healthy on port $PORT (PID: $(cat "$APP_DIR/storage/kicc-integration.pid"))"
        exit 0
    fi
    sleep 1
done

echo "WARNING: Integration service started but health check unresolved on port $PORT"
exit 1