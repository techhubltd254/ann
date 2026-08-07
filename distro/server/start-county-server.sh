#!/usr/bin/env bash
# Start a KICC county server.
#
# Usage:
#   ./start-county-server.sh                  # kilifi, port 8091
#   COUNTY_SLUG=muranga ./start-county-server.sh
#   PORT=8092 DATA_DIR=./muranga-data ./start-county-server.sh
#
# First run seeds: county@kicc.go.ke (COUNTY tier, your slug) + exhibitor@kicc.go.ke.
# CHANGE THE SEED PASSWORD on first login and set a fresh DB key below.

set -euo pipefail
cd "$(dirname "$0")"

# Locate Java 21 (JAVA_HOME or PATH)
if [[ -x "$JAVA_HOME/bin/java" ]]; then
  export PATH="$JAVA_HOME/bin:$PATH"
fi
command -v java > /dev/null || { echo "Java 21 not found — set JAVA_HOME or install Java 21"; exit 1; }

export COUNTY_SLUG="${COUNTY_SLUG:-kilifi}"
export PORT="${PORT:-8091}"
export DATA_DIR="${DATA_DIR:-./data}"
export KICC_DB_FILE_KEY="${KICC_DB_FILE_KEY:-$(head -c 32 /dev/urandom | od -An -tx1 | tr -d ' \n')}"
export KICC_DB_USER_PASSWORD="${KICC_DB_USER_PASSWORD:-engine}"
export KICC_SEED_MODE="${KICC_SEED_MODE:-county}"
export KICC_SEED_COUNTY_SLUG="$COUNTY_SLUG"
export KICC_SEED_COUNTY_PASSWORD="${KICC_SEED_COUNTY_PASSWORD:-county@2026}"
export JWT_SECRET="${JWT_SECRET:-$(head -c 32 /dev/urandom | od -An -tx1 | tr -d ' \n')}"

export KICC_BACKUP_DIR="${KICC_BACKUP_DIR:-$DATA_DIR/backups}"

mkdir -p "$DATA_DIR"
echo "==> KICC county server: county=$COUNTY_SLUG port=$PORT data=$DATA_DIR"
exec java -jar kicc-engine.jar \
  --server.port="$PORT" \
  --spring.datasource.url="jdbc:h2:file:$DATA_DIR/county;CIPHER=AES;MODE=MySQL" \
  --kicc.seed.county-slug="$COUNTY_SLUG"
