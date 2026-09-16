#!/usr/bin/env bash
set -euo pipefail

APP_DIR="/opt/kicc-laravel"
REPO="techhubltd254/ann"
BRANCH="main"
TOKEN="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"
WORK="/tmp/ann-deploy"
LOG="/opt/deploy-webhook/deploy.log"
export CF_TOKEN="cfat_O5sm7cBn09iAcOrgkassBRO4yNElj1PhBdmh2r4K0f9e27ef"
export CF_ACCOUNT="c8416e05ed0a3554806be51aac862ec4"

echo "=== DEPLOY START $(date) ===" >> "$LOG"

rm -rf "$WORK"
mkdir -p "$WORK"
if ! curl -fsSL -H "Authorization: token $TOKEN" \
    "https://api.github.com/repos/$REPO/tarball/$BRANCH" -o "$WORK/ann.tar.gz"; then
    echo "FAIL: tarball download" >> "$LOG"
    exit 1
fi

tar -xzf "$WORK/ann.tar.gz" -C "$WORK" --strip-components=1 2>> "$LOG"
echo "tarball extracted" >> "$LOG"

rsync -az --delete \
    --exclude ".git/" \
    --exclude ".env" \
    --exclude ".env.*" \
    --exclude "storage/" \
    --exclude "bootstrap/cache/" \
    --exclude "vendor/" \
    --exclude "node_modules/" \
    --exclude "muranga video.mp4" \
    --exclude "muranga hero.mp4" \
    "$WORK/" "$APP_DIR/" >> "$LOG" 2>&1

# Fix storage symlink
STORAGE_LINK="$APP_DIR/public/storage"
STORAGE_TARGET="$APP_DIR/storage/app/public"
if [ -L "$STORAGE_LINK" ] && [ "$(readlink "$STORAGE_LINK")" != "$STORAGE_TARGET" ]; then
    rm -f "$STORAGE_LINK" && ln -sf "$STORAGE_TARGET" "$STORAGE_LINK"
    echo "  storage symlink fixed" >> "$LOG"
fi
if [ ! -L "$STORAGE_LINK" ]; then
    ln -sf "$STORAGE_TARGET" "$STORAGE_LINK"
    echo "  storage symlink created" >> "$LOG"
fi

cd "$APP_DIR"

# Maintenance mode
php artisan down --retry=30 2>/dev/null || true

# Dependencies
if command -v composer &>/dev/null; then
    COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --optimize-autoloader --no-interaction >> "$LOG" 2>&1 || true
fi

# .env
if [ ! -f .env ]; then cp .env.example .env; fi

# Generate key if missing
php artisan key:generate --force 2>/dev/null || true

# Migrate + seed
php artisan migrate --force >> "$LOG" 2>&1 || echo "migrate warn" >> "$LOG"
php artisan db:seed --class=RolePermissionSeeder --force 2>/dev/null || true

# Assign admin roles automatically
php -r '
$app = require "/opt/kicc-laravel/bootstrap/app.php";
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$admin = App\Models\User::first();
if ($admin && !$admin->hasRole("kicc_admin")) {
    $admin->assignRole("kicc_admin", "county_admin", "national_admin");
    echo "  Roles assigned\n";
}
' 2>/dev/null >> "$LOG" || echo "role assign warn" >> "$LOG"

# Fix logo_url columns
php -r '
$app = require "/opt/kicc-laravel/bootstrap/app.php";
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
try {
    DB::statement("ALTER TABLE county_institutions MODIFY logo_url TEXT NULL");
    DB::statement("ALTER TABLE county_institutions MODIFY cover_image_url TEXT NULL");
} catch (\Throwable $e) {}
' 2>/dev/null >> "$LOG" || true

# View/route cache
php artisan view:clear >> "$LOG" 2>&1
php artisan route:clear >> "$LOG" 2>&1 || true
php artisan config:cache >> "$LOG" 2>&1
php artisan route:cache >> "$LOG" 2>&1
php artisan view:cache >> "$LOG" 2>&1

# Opcache reset
php -r "opcache_reset();" 2>/dev/null || true

# Deploy CDN worker
EDGE_FILE="$APP_DIR/edge/kicctest-gateway.js"
if [ -f "$EDGE_FILE" ]; then
    CURRENT_V=$(grep -oP "CACHE_VERSION = \"\K[^\"]+" "$EDGE_FILE" 2>/dev/null || echo "v1")
    MAJOR=$(echo "$CURRENT_V" | grep -oP '\d+' || echo "1")
    NEW_V="v$((MAJOR + 1))"
    sed -i "s/const CACHE_VERSION = \"$CURRENT_V\"/const CACHE_VERSION = \"$NEW_V\"/" "$EDGE_FILE"
    echo "worker cache version: $CURRENT_V -> $NEW_V" >> "$LOG"
    python3 /opt/kicc-laravel/infra/deploy/deploy-worker.py >> "$LOG" 2>&1 || echo "worker deploy FAILED" >> "$LOG"
fi

# Deploy R2 media worker (kicc-r2-media) — always re-assert the KICC_MEDIA
# R2 binding; the media CDN breaks if this binding is missing.
R2_META="$APP_DIR/infra/deploy/worker-r2-meta.json"
if [ -f "$R2_META" ]; then
    python3 /opt/kicc-laravel/infra/deploy/deploy-worker-r2.py >> "$LOG" 2>&1 || echo "r2-media worker deploy FAILED" >> "$LOG"
fi

# Purge Cloudflare CDN cache for the entire site (so users see changes immediately)
# Requires CLOUDFLARE_API_TOKEN with zone-level cache purge permissions
ZONE_ID="abeda0e6440d9eabd5e2e54deced291e"
if [ -n "${CLOUDFLARE_API_TOKEN:-}" ]; then
    echo "purging Cloudflare cache..." >> "$LOG"
    curl -s -X POST "https://api.cloudflare.com/client/v4/zones/$ZONE_ID/purge_cache" \
        -H "Authorization: Bearer $CLOUDFLARE_API_TOKEN" \
        -H "Content-Type: application/json" \
        -d '{"purge_everything":true}' \
        -o /dev/null 2>&1 && echo "  ✓ Cloudflare cache purged" >> "$LOG" || echo "  ⚠ Cloudflare cache purge failed" >> "$LOG"
else
    echo "  ⚠ CLOUDFLARE_API_TOKEN not set — skipping cache purge" >> "$LOG"
fi

# Bring app back
php artisan up 2>/dev/null || true

# Health check
HEALTH_CODE=$(curl -s -o /dev/null -w "%{http_code}" --connect-timeout 10 "https://kicctest.org/" 2>/dev/null || echo "000")
echo "health check: HTTP $HEALTH_CODE" >> "$LOG"

echo "=== DEPLOY COMPLETE $(date) ===" >> "$LOG"