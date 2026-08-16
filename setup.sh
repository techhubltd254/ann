#!/usr/bin/env bash
# KICC Admin App — Setup Script
# Creates the Laravel skeleton + copies admin files from main platform

set -e

DIR="$(cd "$(dirname "$0")" && pwd)"
SOURCE="/home/kicc/Desktop/kicc/kicc-platform"

echo "Setting up KICC Admin App in $DIR"

# 1. Create Laravel skeleton
cd "$DIR"
composer create-project laravel/laravel:^13.8 tmp-skeleton --no-interaction --prefer-dist 2>/dev/null || {
    echo "Composer not available. Creating skeleton manually..."
    mkdir -p bootstrap/cache
    mkdir -p storage/framework/{views,cache,sessions}
    mkdir -p storage/logs
    mkdir -p public
}

# 2. Copy this setup script's companions
# (files are placed alongside this script)

# 3. Install admin packages
if [ -f composer.json ]; then
    composer require filament/filament:^5.7 spatie/laravel-permission:^8.3 --no-interaction 2>/dev/null || true
fi

# 4. Copy all models
echo "Copying models..."
cp -r "$SOURCE/app/Models/" "$DIR/app/"

# 5. Copy Filament resources
echo "Copying Filament resources..."
cp -r "$SOURCE/app/Filament/" "$DIR/app/"

# 6. Copy admin controllers
echo "Copying admin controllers..."
mkdir -p "$DIR/app/Http/Controllers/Web"
for f in "$SOURCE/app/Http/Controllers/Web/"*Admin*.php "$SOURCE/app/Http/Controllers/Web/"*Portal*.php; do
    cp "$f" "$DIR/app/Http/Controllers/Web/" 2>/dev/null || true
done

# 7. Copy admin views
echo "Copying admin views..."
cp -r "$SOURCE/resources/views/admin/" "$DIR/resources/views/"
cp -r "$SOURCE/resources/views/dashboards/" "$DIR/resources/views/"
cp -r "$SOURCE/resources/views/dashboard/" "$DIR/resources/views/"
cp -r "$SOURCE/resources/views/layouts/blank.blade.php" "$DIR/resources/views/layouts/"
cp -r "$SOURCE/resources/views/filament/" "$DIR/resources/views/"
cp -r "$SOURCE/resources/views/auth/login.blade.php" "$DIR/resources/views/auth/"

# 8. Copy migrations
echo "Copying migrations..."
cp "$SOURCE/database/migrations/"*.php "$DIR/database/migrations/"

# 9. Copy seeders (admin-only)
echo "Copying seeders..."
cp "$SOURCE/database/seeders/RolePermissionSeeder.php" "$DIR/database/seeders/" 2>/dev/null || true
cp "$SOURCE/database/seeders/MinistrySeeder.php" "$DIR/database/seeders/" 2>/dev/null || true

# 10. Copy config files
echo "Copying config..."
cp "$SOURCE/config/permission.php" "$DIR/config/" 2>/dev/null || true
cp "$SOURCE/config/filament.php" "$DIR/config/" 2>/dev/null || true

# 11. Copy providers
echo "Copying providers..."
cp "$SOURCE/app/Providers/Filament/AdminPanelProvider.php" "$DIR/app/Providers/Filament/" 2>/dev/null || true

# 12. Run setup steps
cd "$DIR"
if [ -f artisan ]; then
    php artisan filament:install --panels --no-interaction 2>/dev/null || true
    php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider" 2>/dev/null || true
    php artisan storage:link 2>/dev/null || true
fi

echo ""
echo "✅ KICC Admin App setup complete!"
echo "Next steps:"
echo "  1. cp .env.example .env  (configure DB to point to same TiDB)"
echo "  2. php artisan key:generate"
echo "  3. php artisan migrate --force"
echo "  4. php artisan serve --host=0.0.0.0 --port=8080"
echo "  5. Visit http://localhost:8080/admin"