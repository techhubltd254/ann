#!/usr/bin/env bash
# KICC County Server — start script.
# Loads county.env (+ county.env.local for generated secrets), then boots the engine.
set -euo pipefail
cd "$(dirname "$0")/.."

if [[ -f county.env ]]; then set -a; source county.env; set +a; fi
if [[ -f county.env.local ]]; then set -a; source county.env.local; set +a; fi

: "${COUNTY_SLUG:?set COUNTY_SLUG in county.env}"
: "${PORT:=8091}"
: "${DATA_DIR:=./data}"

# First run: generate per-install secrets once and persist them (never regenerate).
if [[ ! -f county.env.local ]]; then
  {
    echo "# generated $(date -u +%FT%TZ) — keep secret, do not commit"
    echo "KICC_DB_FILE_KEY=$(head -c 32 /dev/urandom | od -An -tx1 | tr -d ' \n')"
    echo "JWT_SECRET=$(head -c 48 /dev/urandom | od -An -tx1 | tr -d ' \n')"
  } > county.env.local
  chmod 600 county.env.local
  set -a; source county.env.local; set +a
fi

export COUNTY_SLUG PORT DATA_DIR
export KICC_SEED_MODE="${KICC_SEED_MODE:-county}"
export KICC_SEED_COUNTY_SLUG="$COUNTY_SLUG"
export KICC_BACKUP_DIR="${KICC_BACKUP_DIR:-$DATA_DIR/backups}"
export KICC_UPDATE_CHANNEL="${KICC_UPDATE_CHANNEL:-county}"

if [[ -x "${JAVA_HOME:-}/bin/java" ]]; then export PATH="$JAVA_HOME/bin:$PATH"; fi
command -v java > /dev/null || { echo "Java 21 required — set JAVA_HOME or install a JRE 21"; exit 1; }

mkdir -p "$DATA_DIR"
echo "==> KICC county server: county=$COUNTY_SLUG port=$PORT data=$DATA_DIR"
echo "==> OTA updates: ${KICC_UPDATE_ENABLED:-false} (channel $KICC_UPDATE_CHANNEL)"
echo "==> Backup sink: ${BACKUP_SINK_URL:-none}"
exec java -jar server/kicc-engine.jar \
  --server.port="$PORT" \
  --spring.datasource.url="jdbc:h2:file:$DATA_DIR/county;CIPHER=AES;MODE=MySQL" \
  --kicc.seed.county-slug="$COUNTY_SLUG" \
  --kicc.web-dir=./web
