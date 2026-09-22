#!/bin/bash
# Restart the Python algorithms service (called from crontab or deploy.sh)
APP_DIR="/home/kicc/Desktop/kicc/kicc-platform"
PID_FILE="$APP_DIR/storage/kicc-algorithms.pid"
LOG="$APP_DIR/storage/logs/algorithms-service.log"

# Check if running
if curl -s -o /dev/null --max-time 2 http://127.0.0.1:8400/health 2>/dev/null; then
    exit 0  # already running
fi

echo "$(date) Algorithms service down — restarting..." >> "$LOG"

pkill -f "kicc_api/server.py" 2>/dev/null || true
sleep 1

export PYTHONPATH="$APP_DIR"
export KICC_API_PORT="8400"
nohup python3 "$APP_DIR/kicc_api/server.py" >> "$LOG" 2>&1 &
echo $! > "$PID_FILE"
echo "$(date) Restarted PID $(cat $PID_FILE)" >> "$LOG"