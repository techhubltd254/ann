#!/bin/bash
DIR="$(cd "$(dirname "$0")" && pwd)"
DATA="$DIR/data"
WEB="$(cd "$DIR/.." && pwd)/web"
ELECTRON_DIR="$(cd "$DIR/.." && pwd)/electron"
PORT=8091

mkdir -p "$DATA" "$DATA/backups"
JAVA=$(command -v java || echo "/home/kicc/.tools/jdk21/bin/java")
export KICC_BACKUP_DIR="$DATA/backups"

# Start backend server
"$JAVA" -jar "$DIR/kicc-engine.jar" \
  --server.port="$PORT" \
  --kicc.web-dir="$WEB" \
  --spring.datasource.url="jdbc:h2:file:$DATA/engine;CIPHER=AES;MODE=MySQL;AUTO_SERVER=TRUE" \
  > "$DATA/engine.log" 2>&1 &

# Wait for engine
for i in $(seq 1 30); do
  if curl -s -o /dev/null "http://localhost:$PORT/api/health" 2>/dev/null; then break; fi
  sleep 1
done

# Launch Electron desktop app
cd "$ELECTRON_DIR"
electron main.js --no-sandbox &
echo "KICC Platform launched as native desktop app"
echo "Admin: admin@kicc.go.ke / Admin@2026"
wait
