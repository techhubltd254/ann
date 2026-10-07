#!/bin/bash
# Single-flow R2 upload: login → presigned → PUT → confirm in one session
set -e
COOKIE="/tmp/kicc_r2_flow.txt"
rm -f "$COOKIE"
ADMIN_URL="https://kicctest.org"
FILE="$1"
SLUG="$2"
OWNER_ID="$3"
LABEL="${4:-$SLUG}"

[ -z "$FILE" ] && echo "Usage: $0 <file> <slug> <owner_id> [label]" && exit 1
[ ! -f "$FILE" ] && echo "File not found: $FILE" && exit 1

FSIZE=$(stat -c%s "$FILE")
MIME="video/mp4"
ORIG_NAME=$(basename "$FILE")
R2PATH="institutions/${SLUG}/hero/${SLUG}-hero.mp4"
OWNER_TYPE="App\\Models\\CountyInstitution"

echo "=== $LABEL ($((FSIZE/1048576))MB) ==="

# 1. Login
curl -s -c "$COOKIE" -b "$COOKIE" "$ADMIN_URL/kicc-admin/login" -o /dev/null
T=$(curl -s -b "$COOKIE" "$ADMIN_URL/kicc-admin/login" | grep -oP 'name="_token" value="\K[^"]+' | head -1)
curl -s -c "$COOKIE" -b "$COOKIE" -L -X POST "$ADMIN_URL/kicc-admin/login" \
  -d "_token=$T&login=admin@kicc.go.ke&password=KICC@Admin2026" -o /dev/null
echo "  ✓ Logged in"

# 2. Get CSRF from admin page
CT=$(curl -s -b "$COOKIE" "$ADMIN_URL/kicc-admin" | grep -oP 'name="_token" value="\K[^"]+' | head -1)
echo "  CSRF: ${CT:0:10}..."

# 3. Get presigned URL
PRESIGNED=$(curl -s -b "$COOKIE" -X POST "$ADMIN_URL/api/r2/presigned-upload" \
  -H "X-CSRF-TOKEN: $CT" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d "{\"path\":\"$R2PATH\",\"mime\":\"$MIME\"}" 2>&1)
URL=$(echo "$PRESIGNED" | python3 -c "import sys,json; print(json.load(sys.stdin).get('url',''))" 2>/dev/null)
[ -z "$URL" ] && echo "  ✗ Presigned failed: $PRESIGNED" && exit 1
echo "  ✓ Presigned URL obtained"

# 4. PUT to R2
echo -n "  Uploading to R2..."
R2_HTTP=$(curl -s -o /dev/null -w "%{http_code}" -X PUT "$URL" \
  -H "Content-Type: $MIME" \
  -H "x-amz-acl: public-read" \
  --data-binary @"$FILE" \
  --max-time 900 2>&1)
echo " HTTP:$R2_HTTP"
[ "$R2_HTTP" != "200" ] && echo "  ✗ R2 PUT failed" && exit 1

# 5. Get fresh CSRF (session might have rotated during PUT)
CT2=$(curl -s -b "$COOKIE" "$ADMIN_URL/kicc-admin" | grep -oP 'name="_token" value="\K[^"]+' | head -1)

# 6. Confirm upload
CONFIRM=$(curl -s -b "$COOKIE" -X POST "$ADMIN_URL/api/r2/confirm-upload" \
  -H "X-CSRF-TOKEN: $CT2" \
  -H "Accept: application/json" \
  -F "path=$R2PATH" \
  -F "owner_type=$OWNER_TYPE" \
  -F "owner_id=$OWNER_ID" \
  -F "slot=hero_video" \
  -F "original_name=$ORIG_NAME" \
  -F "mime=$MIME" \
  -F "size_bytes=$FSIZE" 2>&1)

ASSET_ID=$(echo "$CONFIRM" | python3 -c "import sys,json; print(json.load(sys.stdin).get('id',''))" 2>/dev/null)
if [ -n "$ASSET_ID" ]; then
  echo "  ✓ MediaAsset #$ASSET_ID created"
else
  echo "  ✗ Confirm: $CONFIRM" | head -c 500
fi

echo ""
echo "Done: $LABEL ✓"