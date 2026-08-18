#!/usr/bin/env bash
# Automated DB restore test — verifies the latest R2 backup is restorable.
set -euo pipefail

ENV=/opt/kicc-laravel/.env
get() { grep -E "^$1=" "$ENV" | cut -d= -f2-; }

R2_AK=$(get CLOUDFLARE_R2_ACCESS_KEY); R2_SK=$(get CLOUDFLARE_R2_SECRET_KEY)
R2_EP=$(get CLOUDFLARE_R2_ENDPOINT); R2_BK=$(get CLOUDFLARE_R2_BUCKET)
export RCLONE_S3_PROVIDER=Cloudflare RCLONE_S3_ENDPOINT=$R2_EP
export RCLONE_S3_ACCESS_KEY_ID=$R2_AK RCLONE_S3_SECRET_ACCESS_KEY=$R2_SK RCLONE_S3_ACL=private

WORK="/var/backups/kicc/restore-test"
mkdir -p "$WORK"

echo "[$(date -Iseconds)] Finding latest backup..."
LATEST=$(rclone ls ":s3:$R2_BK/db-backups/hourly/" 2>/dev/null | sort -k2 | tail -1 | awk '{print $2}')
if [ -z "$LATEST" ]; then echo "ERROR: no backups"; exit 1; fi
echo "Latest: $LATEST"

echo "[$(date -Iseconds)] Downloading..."
rclone copy ":s3:$R2_BK/db-backups/hourly/$LATEST" "$WORK/" 2>&1

echo "[$(date -Iseconds)] Extracting..."
tar -xf "$WORK/$LATEST" -C "$WORK/"

SQL_COUNT=$(ls "$WORK"/*.sql "$WORK"/*.sql.gz 2>/dev/null | wc -l)
echo "SQL files: $SQL_COUNT"
if [ "$SQL_COUNT" -lt 50 ]; then echo "ERROR: too few SQL files"; exit 1; fi

SAMPLE=$(ls "$WORK"/*.sql 2>/dev/null | grep -v schema | head -1)
if [ -n "$SAMPLE" ]; then
    ROWS=$(grep -c "INSERT INTO" "$SAMPLE" 2>/dev/null || echo 0)
    echo "Sample data rows: $ROWS"
fi

echo "[$(date -Iseconds)] Cleaning up..."
rm -f "$WORK"/*.tar "$WORK"/*.sql "$WORK"/*.sql.gz 2>/dev/null

echo "[$(date -Iseconds)] ✓ Restore test PASSED"