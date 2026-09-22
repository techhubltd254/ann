#!/bin/bash
# Start the KICC Algorithms Python microservice.
# Called from deploy.sh or manually after deploy.
set -e

APP_DIR="${1:-$(dirname "$0")/..}"
LOG="$APP_DIR/storage/logs/algorithms-service.log"
PORT="${KICC_API_PORT:-8400}"

# Kill existing if running
pkill -f "kicc_api/server.py" 2>/dev/null || true
sleep 1

export PYTHONPATH="$APP_DIR"
export KICC_CONFIG_OVERRIDES='{"pool":{"alpha":0.7,"beta":0.3}}'
export KICC_API_PORT="$PORT"

nohup python3 "$APP_DIR/kicc_api/server.py" >> "$LOG" 2>&1 &
echo $! > "$APP_DIR/storage/kicc-algorithms.pid"
echo "Algorithms service started on port $PORT (PID: $(cat "$APP_DIR/storage/kicc-algorithms.pid"))"