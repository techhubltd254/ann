#!/usr/bin/env bash
set -euo pipefail

APP_DIR="/opt/kicc-laravel"
REPO="techhubltd254/ann"
BRANCH="main"
: "${GITHUB_DEPLOY_TOKEN:?GITHUB_DEPLOY_TOKEN is not set - export it in the droplet env, never commit it}"
TOKEN="$GITHUB_DEPLOY_TOKEN"
WORK="/tmp/ann-deploy"
LOG="/opt/deploy-webhook/deploy.log"

# Source .env so Cloudflare tokens are available for cache purge
if [ -f "$APP_DIR/.env" ]; then
    set -a; source "$APP_DIR/.env"; set +a
fi

CF_TOKEN="${CF_TOKEN:-${CLOUDFLARE_API_TOKEN:-}}"
export CF_ACCOUNT="${CF_ACCOUNT:-}"

echo "=== DEPLOY START $(date) ===" >> "$LOG"

rm -rf "$WORK"
mkdir -p "$WORK"
if ! curl -fsSL -H "Authorization: token $TOKEN" \
    "https://api.github.com/repos/$REPO/tarball/$BRANCH" -o "$WORK/ann.tar.gz"; then
    echo "FAIL: tarball download" >> "$LOG"
    exit 1
fi

# Extract tarball. --strip-components=1 removes the top-level repo dir.
# Ignore minor tar errors (symlinks, special chars) — the source files we
# need (app/, config/, routes/, resources/) always extract cleanly.
tar -xzf "$WORK/ann.tar.gz" -C "$WORK" --strip-components=1 2>> "$LOG" || echo "tarball extract warn (non-fatal)" >> "$LOG"
echo "tarball extracted" >> "$LOG"

# Remove generated/storage dirs that rsync already skips — these commonly
# cause tar rename conflicts and slowdowns for zero benefit.
rm -rf "$WORK/storage" "$WORK/public/storage" 2>/dev/null || true

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
    --exclude "public/3d/splats/" \
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
# Do not leave the public site in maintenance mode when a later deploy command fails.
trap 'cd "$APP_DIR" && php artisan up >> "$LOG" 2>&1 || true' EXIT

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
# Content seeders are destructive for live admin edits and researched catalogues.
# Only run them for an explicitly requested initial-data reset, never on normal deploys.
if [ "${KICC_RUN_CONTENT_SEEDS:-0}" = "1" ]; then
    php artisan db:seed --class=MurangaLiveInstitutionsSeeder --force 2>/dev/null || true
    php artisan db:seed --class=MurangaAllSectorsSeeder --force 2>/dev/null || true
    php artisan db:seed --class=CrossCountyInstitutionsSeeder --force 2>/dev/null || true
else
    echo "  content seeders skipped: preserving live institution edits and source-backed offerings" >> "$LOG"
fi

# Reset known admin password via web endpoint (uses full framework)
curl -s --connect-timeout 10 --max-time 30 "https://kicctest.org/kicc-admin/reset-pwd" >> "$LOG" 2>&1 || echo "admin password reset warn" >> "$LOG"

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

# Build frontend assets (Inertia/React/3D components — use absolute npm path for systemd env)
NPM=$(command -v npm 2>/dev/null)
[ -z "$NPM" ] && [ -x /usr/bin/npm ] && NPM=/usr/bin/npm
[ -z "$NPM" ] && echo "  frontend: npm not found" >> "$LOG"
if [ -f package.json ] && [ -n "$NPM" ]; then
    echo "  frontend: npm at $NPM" >> "$LOG"
    mkdir -p public/build 2>/dev/null
    if "$NPM" install --no-audit --no-fund >> "$LOG" 2>&1 && "$NPM" run build >> "$LOG" 2>&1; then
        echo "  frontend: build OK" >> "$LOG"
        ln -sf .vite/manifest.json public/build/manifest.json 2>/dev/null || true
        [ -f public/build/.vite/manifest.json ] && echo "  frontend: manifest symlinked" >> "$LOG"
    else
        echo "  frontend build FAILED" >> "$LOG"
    fi
fi

# View/route cache
php artisan view:clear >> "$LOG" 2>&1
php artisan route:clear >> "$LOG" 2>&1 || true
php artisan config:cache >> "$LOG" 2>&1
php artisan route:cache >> "$LOG" 2>&1
php artisan view:cache >> "$LOG" 2>&1

# Opcache reset
php -r "opcache_reset();" 2>/dev/null || true

# Generate pipeline-bus registry (pipelines.json + integration-map.json) from live DB
php artisan kicc:bus-registry >> "$LOG" 2>&1 || echo "bus-registry generation warn" >> "$LOG"

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

# Start Python algorithms service (restart on every deploy to pick up code changes)
ALGO_SERVICE="$APP_DIR/kicc_api/server.py"
if [ -f "$ALGO_SERVICE" ]; then
    # Prefer systemd (kicc-algorithms) so the port is not double-owned; fall back to nohup.
    if systemctl list-unit-files kicc-algorithms.service >/dev/null 2>&1; then
        systemctl restart kicc-algorithms >> "$LOG" 2>&1 || echo "systemd algorithms restart warn" >> "$LOG"
    else
        pkill -f "kicc_api/server.py" 2>/dev/null || true
        sleep 1
        export PYTHONPATH="$APP_DIR"
        export KICC_API_PORT="8400"
        nohup python3 "$ALGO_SERVICE" >> "$APP_DIR/storage/logs/algorithms-service.log" 2>&1 &
        ALGO_PID=$!
        echo $ALGO_PID > "$APP_DIR/storage/kicc-algorithms.pid"
        echo "algorithms service started on port 8400 (PID $ALGO_PID)" >> "$LOG"
    fi

    # Health check — give the service 5 seconds to boot
    for i in 1 2 3 4 5; do
        if curl -s -o /dev/null --max-time 2 "http://127.0.0.1:8400/health" 2>/dev/null; then
            echo "  ✓ algorithms service health check PASSED" >> "$LOG"
            break
        fi
        sleep 1
    done
fi

# Start Node.js integration service (restart on every deploy)
INT_SERVICE="$APP_DIR/integrations-service/api/server.js"
if [ -f "$INT_SERVICE" ]; then
    # Ensure Node deps are present (node_modules is excluded from rsync)
    if [ ! -d "$APP_DIR/integrations-service/node_modules" ]; then
        cd "$APP_DIR/integrations-service" && npm install --no-audit --no-fund 2>>"$LOG" || true
        cd "$APP_DIR"
    fi
    cp "$APP_DIR/.env.integration" "$APP_DIR/integrations-service/.env" 2>/dev/null || true

    if systemctl list-unit-files kicc-integration.service >/dev/null 2>&1; then
        systemctl restart kicc-integration >> "$LOG" 2>&1 || echo "systemd integration restart warn" >> "$LOG"
    else
        pkill -f "integrations-service/api/server\.js" 2>/dev/null || true
        sleep 1
        cd "$APP_DIR/integrations-service" && nohup node api/server.js >> "$APP_DIR/storage/logs/integration-service.log" 2>&1 &
        INT_PID=$!
        echo $INT_PID > "$APP_DIR/storage/kicc-integration.pid"
        echo "integration service started on port 8787 (PID $INT_PID)" >> "$LOG"
        cd "$APP_DIR"
    fi

    for i in 1 2 3 4 5; do
        if curl -s -o /dev/null --max-time 2 "http://127.0.0.1:8787/health" 2>/dev/null; then
            echo "  ✓ integration service health check PASSED" >> "$LOG"
            break
        fi
        sleep 1
    done
fi

# Start inter-pipeline automation bus (port 8790)
BUS_SERVER="$APP_DIR/integrations-service/server.mjs"
if [ -f "$BUS_SERVER" ]; then
    if systemctl list-unit-files kicc-pipeline-bus.service >/dev/null 2>&1; then
        systemctl restart kicc-pipeline-bus >> "$LOG" 2>&1 || echo "systemd bus restart warn" >> "$LOG"
    else
        pkill -f "integrations-service/server\.mjs" 2>/dev/null || true
        sleep 1
        echo "bus server started on port 8790" >> "$LOG"
        cd "$APP_DIR/integrations-service" && nohup node server.mjs >> "$APP_DIR/storage/logs/pipeline-bus.log" 2>&1 &
        BUS_PID=$!
        echo $BUS_PID > "$APP_DIR/storage/kicc-bus.pid"
        cd "$APP_DIR"
    fi
    for i in 1 2 3 4 5; do
        if curl -s -o /dev/null --max-time 2 "http://127.0.0.1:8790/health" 2>/dev/null; then
            echo "  ✓ pipeline bus health check PASSED" >> "$LOG"
            break
        fi
        sleep 1
    done
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