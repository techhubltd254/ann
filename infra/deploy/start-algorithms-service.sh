#!/bin/bash
# Start the KICC Algorithms Python microservice.
# Called from deploy.sh or manually after deploy.
set -euo pipefail

# Resolve APP_DIR to an absolute path
if [ -n "${1:-}" ]; then
    APP_DIR="$(cd "$1" && pwd)"
else
    APP_DIR="$(cd "$(dirname "$0")/../.." && pwd)"
fi
LOG="$APP_DIR/storage/logs/algorithms-service.log"
PORT="${KICC_API_PORT:-8400}"

mkdir -p "$APP_DIR/storage/logs"

# Kill existing if running (exact python invocation only, never the outer shell)
pkill -f "python3.*kicc_api/server\.py" 2>/dev/null || true
sleep 1

export PYTHONPATH="$APP_DIR"
export KICC_CONFIG_OVERRIDES="${KICC_CONFIG_OVERRIDES:-\{\"pool\":\{\"alpha\":0.7,\"beta\":0.3\}\}}"
export KICC_API_PORT="$PORT"

setsid nohup python3 "$APP_DIR/kicc_api/server.py" >> "$LOG" 2>&1 &
echo $! > "$APP_DIR/storage/kicc-algorithms.pid"
sleep 2

# Health check
for i in 1 2 3 4 5; do
    if curl -s -o /dev/null --max-time 2 "http://127.0.0.1:$PORT/health" 2>/dev/null; then
        echo "Algorithms service healthy on port $PORT (PID: $(cat "$APP_DIR/storage/kicc-algorithms.pid"))"
        exit 0
    fi
    sleep 1
done

echo "WARNING: Algorithms service started but health check unresolved on port $PORT"
exit 1