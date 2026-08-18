#!/usr/bin/env bash
set -euo pipefail

# KICC TiDB logical backup -> Cloudflare R2 (mydumper + rclone)
# Usage: backup-db.sh hourly|daily    (daily also promotes monthly on the 1st)
# Retention: hourly 48, daily 14, monthly 12

ENV=/opt/kicc-laravel/.env
WORK=/var/backups/kicc/work
OUT=/var/backups/kicc/out
MODE=${1:-hourly}
STAMP=$(date -u +%Y%m%dT%H%MZ)

get() { grep -E "^$1=" "$ENV" | cut -d= -f2-; }

DB_HOST=$(get DB_HOST); DB_PORT=$(get DB_PORT); DB_NAME=$(get DB_DATABASE)
DB_USER=$(get DB_USERNAME); DB_PASS=$(get DB_PASSWORD)
R2_AK=$(get CLOUDFLARE_R2_ACCESS_KEY); R2_SK=$(get CLOUDFLARE_R2_SECRET_KEY)
R2_EP=$(get CLOUDFLARE_R2_ENDPOINT); R2_BK=$(get CLOUDFLARE_R2_BUCKET)

export RCLONE_S3_PROVIDER=Cloudflare
export RCLONE_S3_ENDPOINT=$R2_EP
export RCLONE_S3_ACCESS_KEY_ID=$R2_AK
export RCLONE_S3_SECRET_ACCESS_KEY=$R2_SK
export RCLONE_S3_ACL=private

R2DIR=:s3:$R2_BK/db-backups
NICE="ionice -c3 nice -n10"

mkdir -p "$WORK" "$OUT"
rm -rf "$WORK"/*
cd "$WORK"

echo "[backup] dumping TiDB ($MODE) -> $STAMP"
$NICE mydumper --ssl --host="$DB_HOST" --port="$DB_PORT" --user="$DB_USER" \
  --password="$DB_PASS" --database="$DB_NAME" --outputdir="$WORK" \
  --compress --rows=50000 --threads=4 --long-query-guard=3600 2>/tmp/mydumper.err

FILES=$(ls "$WORK" | wc -l)
if [ "$FILES" -lt 5 ]; then
  echo "[backup] FAILED: only $FILES files dumped" >&2
  tail -5 /tmp/mydumper.err >&2
  exit 1
fi

ARCHIVE="$OUT/kicc-$STAMP.tar"
echo "[backup] archiving $FILES files"
tar -cf "$ARCHIVE" .
SIZE=$(du -h "$ARCHIVE" | cut -f1)

upload() {
  local prefix=$1
  rclone copyto "$ARCHIVE" "$R2DIR/$prefix/$(basename "$ARCHIVE")" --no-check-dest 2>/dev/null || rclone copyto "$ARCHIVE" "$R2DIR/$prefix/$(basename "$ARCHIVE")"
  echo "[backup] uploaded to db-backups/$prefix/$(basename "$ARCHIVE") ($SIZE)"
}

prune() {
  local prefix=$1 keep=$2
  rclone lsf "$R2DIR/$prefix/" --files-only 2>/dev/null | sort -r | tail -n +$((keep+1)) | while read -r f; do
    rclone deletefile "$R2DIR/$prefix/$f" && echo "[backup] pruned db-backups/$prefix/$f"
  done
}

case "$MODE" in
  hourly)
    upload hourly
    prune hourly 48
    ;;
  daily)
    upload daily
    prune daily 14
    if [ "$(date -u +%d)" = "01" ]; then
      upload monthly
      prune monthly 12
    fi
    ;;
esac

echo "[backup] local keep: $(ls "$OUT"/*.tar | wc -l) archives"
# keep last 3 local archives
ls -1t "$OUT"/*.tar 2>/dev/null | tail -n +4 | xargs -r rm -f
echo "[backup] DONE $MODE $STAMP ($SIZE)"
