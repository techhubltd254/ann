#!/bin/sh
# ota-restart.sh — external OTA restart + health-gate + rollback watchdog.
#
# Why this exists: the engine cannot health-check ITSELF after it exits.
# UpdateService writes releases/pending.json, invokes this script, and dies.
# This script restarts the engine, waits for /api/health, and rolls the
# symlink back to the previous release if the new one never becomes healthy.
#
# Usage: ota-restart.sh <kicc-home> [port]   (default port 8091)
# Env:   RESTART_MODE=systemd|direct (default: systemd, falls back to direct)

set -u
HOME_DIR="${1:-.}"
PORT="${2:-8091}"
PENDING="$HOME_DIR/releases/pending.json"
CURRENT="$HOME_DIR/releases/current"
HEALTH_URL="http://127.0.0.1:$PORT/api/health"
MODE="${RESTART_MODE:-systemd}"

log() { echo "$(date -u +%FT%TZ) ota-restart: $*"; }

restart_engine() {
  if [ "$MODE" = "systemd" ] && command -v systemctl >/dev/null 2>&1 && systemctl list-unit-files kicc-engine.service >/dev/null 2>&1; then
    systemctl restart kicc-engine
  else
    # direct mode (desktop installs / demo): no systemd available
    pkill -f "release[s]/current" 2>/dev/null || true
    sleep 2
    cd "$HOME_DIR" || exit 1
    nohup ${JAVA_BIN:-java} -jar "$CURRENT" >> "$HOME_DIR/engine.log" 2>&1 &
  fi
}

[ -f "$PENDING" ] || { log "no pending update — plain restart"; restart_engine; exit 0; }

NEW_VERSION=$(sed -n 's/.*"version" *: *"\([^"]*\)".*/\1/p' "$PENDING" | head -1)
PREV=$(sed -n 's/.*"previous" *: *"\([^"]*\)".*/\1/p' "$PENDING" | head -1)
log "applying update -> $NEW_VERSION (rollback target: ${PREV:-none})"

restart_engine

i=0
while [ $i -lt 24 ]; do
  sleep 5
  if curl -fsS -m 3 "$HEALTH_URL" >/dev/null 2>&1; then
    log "healthy on $NEW_VERSION — update confirmed"
    rm -f "$PENDING"
    exit 0
  fi
  i=$((i + 1))
done

# Health gate failed after 120 s — roll back.
log "UNHEALTHY after update to $NEW_VERSION — rolling back"
if [ -n "$PREV" ] && [ -e "$PREV" ]; then
  # quarantine the bad release dir so this version is never retried
  NEW_DIR="$HOME_DIR/releases/$NEW_VERSION"
  [ -d "$NEW_DIR" ] && mv "$NEW_DIR" "$NEW_DIR.failed" && log "quarantined $NEW_DIR.failed"
  ln -sfn "$PREV" "$CURRENT"
  restart_engine
  log "rolled back to $PREV"
else
  log "no previous release to roll back to!"
fi
exit 1
