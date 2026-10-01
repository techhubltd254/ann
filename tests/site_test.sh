#!/usr/bin/env bash
# KICC Platform — Brutal Full-Site Test
set -uo pipefail
SITE="https://kicctest.org"
FAIL=0; PASS=0

green() { echo -e "\e[32m✓ $1\e[0m"; ((PASS++)); }
red() { echo -e "\e[31m✗ $1 → HTTP $2\e[0m"; ((FAIL++)); }
check() {
  local url="$1" desc="$2" expected="${3:-200}" code
  code=$(curl -s -o /dev/null -w '%{http_code}' --connect-timeout 8 "$SITE$url" 2>/dev/null || echo '000')
  [ "$code" = "$expected" ] || \
  [ "$expected" = "200|302" -a "$code" = "200" -o "$expected" = "200|302" -a "$code" = "302" ] || \
  [ "$expected" = "200|401" -a "$code" = "200" -o "$expected" = "200|401" -a "$code" = "401" ] || \
  [ "$expected" = "200|429" -a "$code" = "200" -o "$expected" = "200|429" -a "$code" = "429" ] || \
  [ "$expected" = "200|304" -a "$code" = "200" -o "$expected" = "200|304" -a "$code" = "304" ] \
  && { green "$desc"; return; } || { red "$desc" "$code"; }
}

echo "═══════════════════════ KICC SITE TEST ═══════════════════════"
echo "Running: $(date)"
echo ""

echo "── 1. PUBLIC PAGES ──"
check "/" "Home"
check "/login" "Login page"
check "/register" "Registration"
check "/counties" "Counties index"
check "/counties/kajiado" "County: Kajiado"
check "/counties/muranga" "County: Murang'a"
check "/marketplace" "Marketplace"
check "/marketplace?q=coffee" "Search: coffee"
check "/exhibitions" "Exhibitions"
check "/venues" "Venues"
check "/trade-agreements" "Trade agreements"
check "/national-government" "National government"
check "/packages" "Subscription packages"
check "/flash-sales" "Flash sales"
check "/gift-cards" "Gift cards"
check "/sitemap.xml" "Sitemap XML"

echo ""
echo "── 2. 3D & INTERACTIVE ──"
check "/exhibition-3d/map" "3D exhibition: map"
check "/exhibition-3d/booth" "3D exhibition: booth"
check "/exhibition-3d/sector" "3D exhibition: sector"
check "/exhibition-3d/terrain" "3D exhibition: terrain"
check "/3d/splats/hospital" "3D splat: hospital"
check "/3d/counties/muranga" "3D county: Murang'a"

echo ""
echo "── 3. API ──"
check "/api/counties" "API: counties"
check "/api/counties/kajiado/sectors" "API: county sectors"
check "/api/venues" "API: venues"
check "/api/exhibitions" "API: exhibitions"
check "/api/metrics" "API: metrics" "200|429"
check "/healthz" "API: health check"

echo ""
echo "── 4. TRAVEL ──"
check "/travel" "Travel hub"
check "/travel/flights" "Travel: flights" "200|302"

echo ""
echo "── 5. ADMIN (200/302 → login) ──"
check "/kicc-admin" "KICC Mother Admin" "200|302"
check "/county-admin" "County admin" "200|302"
check "/national-admin" "National admin" "200|302"
check "/dashboard/admin" "Admin dashboard" "200|302"
check "/dashboard/county" "County dashboard" "200|302"
check "/orders" "Orders" "200|302"
check "/cart" "Cart" "200|302"
check "/checkout" "Checkout" "200|302"
check "/portal" "Admin portal selector" "200|302"

echo ""
echo "── 6. EDGE CASES (expect 404) ──"
check "/nonexistent" "404 on unknown page" "404"
check "/3d/splats/invalid" "404 on invalid splat" "404"
check "/counties/invalid-county" "404 on invalid county" "404"

echo ""
echo "── 7. STATIC ASSETS ──"
# Dynamically detect current build hashes (avoids hardcoded values that change)
HASH=$(curl -s "$SITE/build/.vite/manifest.json" 2>/dev/null | grep -oP '"app\.[^.]+\.js"' | head -1 | tr -d '"')
CSSHASH=$(curl -s "$SITE/build/.vite/manifest.json" 2>/dev/null | grep -oP '"app\.[^.]+\.css"' | head -1 | tr -d '"')
[ -n "$HASH" ] && check "/build/assets/$HASH" "App JS ($HASH)" "200|304" || echo "  ⚠ Cannot detect JS hash (manifest unavailable)"
[ -n "$CSSHASH" ] && check "/build/assets/$CSSHASH" "App CSS ($CSSHASH)" "200|304" || echo "  ⚠ Cannot detect CSS hash (manifest unavailable)"

echo ""
echo "═══════════════════════ RESULTS ═══════════════════════"
echo "  PASS: $PASS  |  FAIL: $FAIL  |  TOTAL: $((PASS + FAIL))"
[ "$FAIL" -eq 0 ] && echo -e "\e[32m  ✓ ALL PASSED\e[0m" || echo -e "\e[32m  ✗ $FAIL FAILURES — check above\e[0m"
exit $FAIL
