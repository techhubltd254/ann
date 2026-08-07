#!/bin/bash
# KICC Server Launcher — with auto-recovery
DIR="/home/kicc/Desktop/kicc/kicc-portable-admin"
PORT=8090
PIDFILE="/tmp/kicc.pid"
LOGFILE="/tmp/kicc-output.log"

stop() {
    [ -f "$PIDFILE" ] && kill $(cat "$PIDFILE") 2>/dev/null
    fuser -k "${PORT}/tcp" 2>/dev/null
    sleep 1
}

start() {
    stop
    cd "$DIR"
    nohup /usr/bin/php artisan serve --host=127.0.0.1 --port=$PORT > "$LOGFILE" 2>&1 &
    echo $! > "$PIDFILE"
    disown
    # Wait for server
    for i in $(seq 1 10); do
        /usr/bin/curl -s --max-time 2 -o /dev/null http://127.0.0.1:$PORT/login 2>/dev/null && break
        sleep 1
    done
}

# === Main ===
case "${1:-start}" in
    start)
        start
        if /usr/bin/curl -s --max-time 3 -o /dev/null http://127.0.0.1:$PORT/login 2>/dev/null; then
            echo "✅ Server running at http://localhost:$PORT"
            echo "   PID: $(cat $PIDFILE)"
            echo "   Admin: admin@kicc.go.ke / Admin@2026"
            echo "   Kilifi: county@kicc.go.ke / county@2026"
            echo ""
            echo "Access these pages:"
            echo "   http://localhost:$PORT/kicc-admin"
            echo "   http://localhost:$PORT/ceo-dashboard"
            echo "   http://localhost:$PORT/admin/sections"
            echo "   http://localhost:$PORT/county-admin/kilifi/pro"
        else
            echo "❌ Failed to start. Check $LOGFILE"
            tail -5 "$LOGFILE"
            exit 1
        fi
        ;;
    stop)
        stop
        echo "Stopped"
        ;;
    status)
        if /usr/bin/curl -s --max-time 3 -o /dev/null http://127.0.0.1:$PORT/login 2>/dev/null; then
            echo "✅ Running (PID: $(cat $PIDFILE 2>/dev/null))"
        else
            echo "❌ Not running"
        fi
        ;;
    monitor)
        start
        echo "✅ Server started. Monitoring..."
        echo "   (This process stays alive and auto-restarts)"
        while true; do
            sleep 15
            if ! /usr/bin/curl -s --max-time 3 -o /dev/null http://127.0.0.1:$PORT/login 2>/dev/null; then
                echo "$(date): Server down — restarting..."
                start
            fi
        done
        ;;
    *)
        echo "Usage: $0 {start|stop|status|monitor}"
        exit 1
        ;;
esac
