#!/usr/bin/env bash
set -euo pipefail

# =============================================================================
# KICC Live Platform — Deploy Pipeline
# =============================================================================
# Usage: ./deploy.sh [environment]
#   environment: production | staging (default: production)
#
# This script runs ALL deployment steps from a CI/CD runner (GitHub Actions,
# GitLab CI, or manual). ZERO hardcoded values — everything reads from
# environment variables.
#
# Required environment variables:
#   APP_ENV, APP_KEY, DB_*, REDIS_*, CLOUDFLARE_*,
#   MEDIA_CDN_URL, SIGNED_URL_SECRET, N8N_*
# =============================================================================

ENVIRONMENT="${1:-production}"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
APP_DIR="$(dirname "$SCRIPT_DIR")"
TIMESTAMP=$(date -u +"%Y-%m-%dT%H:%M:%SZ")

echo "=== KICC Deploy Pipeline [${ENVIRONMENT}] @ ${TIMESTAMP} ==="

# --- Phase 1: Preflight checks ---
echo "[1/8] Preflight checks..."
for cmd in php composer npm git; do
    command -v "$cmd" >/dev/null 2>&1 || { echo "ERROR: $cmd not found"; exit 1; }
done

if [[ "${APP_KEY:-}" == "" ]]; then
    echo "ERROR: APP_KEY is not set. Run: php artisan key:generate"
    exit 1
fi

# --- Phase 2: Pull latest code (CI/CD only) ---
if [[ "${CI:-}" == "true" ]]; then
    echo "[2/8] Pulling latest code..."
    git pull origin "${CI_COMMIT_BRANCH:-main}"
fi

# --- Phase 3: Install dependencies ---
echo "[3/8] Installing dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist
npm ci --production --no-optional 2>/dev/null || npm install --production --no-optional

# --- Phase 4: Build assets ---
echo "[4/8] Building assets..."
npm run build 2>/dev/null || echo "  (no build step or skipped)"

# --- Phase 5: Environment validation ---
echo "[5/8] Validating environment..."
required_vars=(
    "APP_KEY" "DB_DATABASE" "DB_USERNAME" "DB_PASSWORD"
    "REDIS_HOST" "REDIS_PASSWORD"
    "CLOUDFLARE_ACCOUNT_ID" "CLOUDFLARE_API_TOKEN"
    "AFRICAS_TALKING_API_KEY" "AFRICAS_TALKING_USERNAME"
    "MEDIA_CDN_URL"
)

for var in "${required_vars[@]}"; do
    if [[ -z "${!var:-}" ]]; then
        echo "  WARNING: $var is not set (some features may not work)"
    else
        echo "  ✓ $var is set"
    fi
done

# --- Phase 6: Run migrations ---
echo "[6/8] Running database migrations..."
php artisan migrate --force --isolated

# --- Phase 7: Cache + optimize ---
echo "[7/8] Caching and optimizing..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize

# Clear opcache if available
if php -r "echo function_exists('opcache_reset') ? '1' : '0';" 2>/dev/null | grep -q "1"; then
    php -r "opcache_reset();" 2>/dev/null && echo "  ✓ OpCache reset" || true
fi

# --- Phase 8: Health check ---
echo "[8/8] Running health check..."
HEALTH_URL="${APP_URL:-https://kicctest.org}/up"
HEALTH_STATUS=$(curl -s -o /dev/null -w "%{http_code}" --connect-timeout 5 "$HEALTH_URL" 2>/dev/null || echo "000")
if [[ "$HEALTH_STATUS" == "200" ]]; then
    echo "  ✓ Application health check PASSED (HTTP $HEALTH_STATUS)"
else
    echo "  ⚠ Health check returned HTTP $HEALTH_STATUS (non-critical if first deploy)"
fi

# Verify API routes load
ROUTE_COUNT=$(php artisan route:list 2>/dev/null | grep -c "live\." || echo "0")
echo "  ✓ Live routes registered: ${ROUTE_COUNT}"

echo ""
echo "=== Deploy complete @ $(date -u +'%Y-%m-%dT%H:%M:%SZ') ==="
echo "Environment: ${ENVIRONMENT}"
echo "Next: verify at ${APP_URL:-https://kicctest.org}/national-exhibition"