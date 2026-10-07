#!/bin/bash
# R2 presigned batch upload — bypasses Cloudflare Worker 100MB body limit
# All hero videos use this flow since the admin hero-video endpoint is broken (500)
set -e
VIDEOS_DIR="/home/kicc/Videos"
ADMIN_URL="https://kicctest.org"
ADMIN_EMAIL="admin@kicc.go.ke"
ADMIN_PASS="KICC@Admin2026"
COOKIE="/tmp/kicc_r2_cookies.txt"
rm -f "$COOKIE"

echo "=== Logging in ==="
LOGIN_PAGE=$(curl -s -c "$COOKIE" -b "$COOKIE" "$ADMIN_URL/kicc-admin/login")
CSRF=$(echo "$LOGIN_PAGE" | grep -oP 'name="_token" value="\K[^"]+' | head -1)
curl -s -c "$COOKIE" -b "$COOKIE" -L -X POST "$ADMIN_URL/kicc-admin/login" \
  -d "_token=$CSRF&login=$ADMIN_EMAIL&password=$ADMIN_PASS" -o /dev/null
echo "  ✓ Logged in"
sleep 1

get_csrf() { curl -s -b "$COOKIE" "$ADMIN_URL/kicc-admin" | grep -oP 'name="_token" value="\K[^"]+' | head -1; }

# ── R2 Presigned Upload ──
r2upload() {
  local slug="$1" file="$2" owner_id="$3" label="$4"
  local fsize=$(stat -c%s "$file" 2>/dev/null || echo 0)
  local mime="video/mp4"
  local orig_name=$(basename "$file")
  local r2path="institutions/${slug}/hero/${slug}-hero.mp4"
  local owner_type="App\\Models\\CountyInstitution"
  
  echo ""
  echo "  ▶ [R2] $label  ($((fsize/1048576))MB)"
  echo "    R2 path: $r2path"

  # Step 1: Get presigned URL
  CSRF=$(get_csrf)
  PRESIGNED=$(curl -s -b "$COOKIE" -X POST "$ADMIN_URL/api/r2/presigned-upload" \
    -H "X-CSRF-TOKEN: $CSRF" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -d "{\"path\":\"$r2path\",\"mime\":\"$mime\"}" 2>&1)
  
  PRESIGNED_URL=$(echo "$PRESIGNED" | python3 -c "import sys,json; print(json.load(sys.stdin).get('url',''))" 2>/dev/null || echo "")
  if [ -z "$PRESIGNED_URL" ]; then
    echo "    ✗ Presigned URL failed: $PRESIGNED"
    return 1
  fi

  # Step 2: PUT file directly to R2 (bypasses Cloudflare)
  echo -n "    Uploading to R2..."
  R2_HTTP=$(curl -s -o /dev/null -w "%{http_code}" -X PUT "$PRESIGNED_URL" \
    -H "Content-Type: $mime" \
    -H "x-amz-acl: public-read" \
    --data-binary @"$file" \
    --max-time 900 2>&1)
  echo " HTTP:$R2_HTTP"
  if [ "$R2_HTTP" != "200" ]; then
    echo "    ✗ R2 PUT failed: HTTP $R2_HTTP"
    return 1
  fi

  # Step 3: Confirm — create MediaAsset record
  CSRF=$(get_csrf)
  CONFIRM=$(curl -s -b "$COOKIE" -X POST "$ADMIN_URL/api/r2/confirm-upload" \
    -H "X-CSRF-TOKEN: $CSRF" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -d "{\"path\":\"$r2path\",\"owner_type\":\"$owner_type\",\"owner_id\":$owner_id,\"slot\":\"hero_video\",\"original_name\":\"$orig_name\",\"mime\":\"$mime\",\"size_bytes\":$fsize}" 2>&1)
  
  if echo "$CONFIRM" | python3 -c "import sys,json; d=json.load(sys.stdin); print(d.get('id',''))" 2>/dev/null | grep -q .; then
    echo "    ✓ MediaAsset created"
  else
    echo "    ✗ Confirm failed: $(echo $CONFIRM | head -c 200)"
    return 1
  fi
  sleep 2
}

echo ""
echo "═══ MOMBASA — Institution Hero Videos ═══"

r2upload "tamarind-mombasa"    "$VIDEOS_DIR/Tamarid.mp4"             60011 "Tamarind Mombasa"
r2upload "akamba-handicraft"   "$VIDEOS_DIR/AKAMBA ARTCRAFT.mp4"    60018 "Akamba Handicraft"
r2upload "bombolulu-workshop"  "$VIDEOS_DIR/bombolulu.mp4"           60013 "Bombolulu Workshops"

echo ""
echo "═══ MURANGA — Institution Hero Videos ═══"

r2upload "kakuzi-plc"          "$VIDEOS_DIR/kakuzi.mp4"              11     "Kakuzi PLC"
r2upload "gatura-greens"       "$VIDEOS_DIR/gatura greens .mp4"      8      "Gatura Greens"
r2upload "eliper-hotel"        "$VIDEOS_DIR/eliper.mp4"              7      "Eliper Hotel"
r2upload "guka-cucu-coffee-farm" "$VIDEOS_DIR/gukas farm.mp4"        60030  "Guka & Cucu Coffee Farm"
r2upload "muranga-university-of-science-and-technology" "$VIDEOS_DIR/muranga university.mp4" 13 "Muranga University"

echo ""
echo "═══ DONE — All Hero Videos ═══"
echo ""
echo "Product/facility videos were uploaded earlier (16/16 — see batch-upload.sh output)."
echo "County sector video: run manually via /county-admin/muranga hero tab."
echo ""
echo "Verification URLs:"
echo "  $ADMIN_URL/counties/mombasa"
echo "  $ADMIN_URL/counties/muranga"
echo "  $ADMIN_URL/institution-admin/tamarind-mombasa"
echo "  $ADMIN_URL/institution-admin/kakuzi-plc"
echo "  $ADMIN_URL/institution-admin/gatura-greens"