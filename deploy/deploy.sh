#!/usr/bin/env bash
set -Eeuo pipefail
# Builds a NEW release; never runs migrate:fresh and never activates Nginx.
# Usage: sudo bash deploy/deploy.sh /path/to/ann /var/www/kicc-experience /secure/production.env admin@example.com
SOURCE=${1:?Repository checkout required}
BASE=${2:-/var/www/kicc-experience}
ENV_FILE=${3:?Path to completed production env required}
ADMIN_EMAIL=${4:?Administrator email required}
[[ $EUID -eq 0 ]] || { echo 'Run with sudo.' >&2; exit 1; }
SOURCE=$(realpath "$SOURCE");ENV_FILE=$(realpath "$ENV_FILE")
[[ -f "$SOURCE/artisan" && -f "$SOURCE/composer.lock" && -f "$ENV_FILE" ]] || { echo 'Missing project or environment file.' >&2; exit 1; }
for cmd in php composer rsync curl nginx; do command -v "$cmd" >/dev/null || { echo "Install required command: $cmd" >&2; exit 1; }; done
php -r 'exit(PHP_VERSION_ID >= 80401 ? 0 : 1);' || { echo 'PHP >=8.4.1 required by the deployment profile.' >&2; exit 1; }
php -r 'foreach(["pdo_mysql","mbstring","openssl","fileinfo","dom"] as $x)if(!extension_loaded($x))exit(1);' || { echo 'Required PHP extension missing.' >&2; exit 1; }
[[ -x /usr/sbin/php-fpm8.4 ]] || { echo 'Install php8.4-fpm, or adapt the supplied pool for your PHP >=8.4.1.' >&2; exit 1; }
[[ "$BASE" == /* && "$BASE" != / && "$BASE" != /var && "$BASE" != /var/www ]] || { echo 'Use a dedicated absolute base directory.' >&2; exit 1; }
mkdir -p "$BASE/releases" "$BASE/shared/storage/app/private" "$BASE/shared/storage/app/public" "$BASE/shared/storage/framework/cache/data" "$BASE/shared/storage/framework/sessions" "$BASE/shared/storage/framework/views" "$BASE/shared/storage/logs"
chmod 750 "$BASE/shared"
ID=$(date -u +%Y%m%d-%H%M%S)
RELEASE="$BASE/releases/$ID"
mkdir "$RELEASE"
rsync -a --exclude='.git/' --exclude='.env' --exclude='vendor/' --exclude='node_modules/' --exclude='storage/' --exclude='database/*.sqlite*' --exclude='bootstrap/cache/*.php' "$SOURCE/" "$RELEASE/"
rm -rf "$RELEASE/storage";ln -s "$BASE/shared/storage" "$RELEASE/storage"
mkdir -p "$RELEASE/bootstrap/cache"
cd "$RELEASE"
export COMPOSER_ALLOW_SUPERUSER=1
composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader
# Copy runtime configuration only after dependency installation. Preserve it and
# APP_KEY on subsequent releases; never copy the old ann environment implicitly.
if [[ ! -f "$BASE/shared/.env" ]]; then install -m 640 -o root -g www-data "$ENV_FILE" "$BASE/shared/.env"; fi
ln -s "$BASE/shared/.env" "$RELEASE/.env"
php artisan config:clear
# Generate only an initially blank APP_KEY; keep an existing key stable.
if grep -Eq '^APP_KEY=[[:space:]]*$' "$BASE/shared/.env"; then php artisan key:generate --force; fi
php artisan kicc:deployment-check
php artisan migrate --force
php artisan db:seed --class=ReferenceContentSeeder --force
php artisan kicc:admin "$ADMIN_EMAIL"
php artisan storage:link
chown -R root:www-data "$RELEASE"
chown -R www-data:www-data "$BASE/shared/storage" "$RELEASE/bootstrap/cache"
find "$BASE/shared/storage" "$RELEASE/bootstrap/cache" -type d -exec chmod 775 {} +
find "$BASE/shared/storage" "$RELEASE/bootstrap/cache" -type f -exec chmod 664 {} +
chmod 640 "$BASE/shared/.env"
php artisan config:cache
php artisan route:cache
php artisan view:cache
# Smoke check the new release BEFORE any symlink/domain change.
php artisan serve --host=127.0.0.1 --port=8181 > "$BASE/shared/storage/logs/release-smoke-$ID.log" 2>&1 &
SMOKE_PID=$!
trap 'kill "$SMOKE_PID" 2>/dev/null || true' EXIT
OK=0
for n in $(seq 1 15); do
 if curl -fsS http://127.0.0.1:8181/release-health > "$BASE/shared/release-health.json"; then OK=1;break; fi
 sleep 1
done
[[ "$OK" == 1 ]] || { echo 'Release health failed. Existing site was not changed.' >&2; exit 1; }
CURRENT=$(readlink -f "$BASE/current" 2>/dev/null || true)
if [[ -n "$CURRENT" && "$CURRENT" != "$BASE/current" ]]; then printf '%s\n' "$CURRENT" > "$BASE/shared/previous-release"; fi
# Atomic pointer swap, separate from activating your existing production vhost.
ln -s "$RELEASE" "$BASE/current.next";mv -Tf "$BASE/current.next" "$BASE/current"
kill "$SMOKE_PID" 2>/dev/null || true;trap - EXIT
printf '\nRelease prepared: %s\n' "$RELEASE"
echo 'Next: install the dedicated FPM pool and UPDATE the existing domain vhost as DEPLOY.md specifies.'
echo 'Nginx and the current live domain have NOT been changed by this script.'
