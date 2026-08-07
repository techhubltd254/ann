#!/bin/bash
# KICC Mother Engine — Production launcher
# Usage: ./start-mother-server.sh [data-dir] [port]
# Defaults: ./data as data dir, port 8091

DATA_DIR="${1:-./data}"
PORT="${2:-8091}"

echo "=== KICC Mother Engine ==="
echo "Data dir: $DATA_DIR"
echo "Port:     $PORT"

export KICC_DB_FILE_KEY="${KICC_DB_FILE_KEY:-kicc-mother-engine-db-key-2026}"
export JWT_SECRET="${JWT_SECRET:-change-this-secret-in-production-$(date +%s)}"
export KICC_BACKUP_DIR="$DATA_DIR/backups"
export KICC_SOURCE_DB="${KICC_SOURCE_DB:-}"

mkdir -p "$DATA_DIR" "$DATA_DIR/backups"

java -jar kicc-engine.jar \
  --server.port="$PORT" \
  --spring.datasource.url="jdbc:h2:file:$DATA_DIR/engine;CIPHER=AES;MODE=MySQL;AUTO_SERVER=TRUE" \
  2>&1 | tee "$DATA_DIR/engine.log"
