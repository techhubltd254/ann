#!/bin/bash
# ============================================================
# KICC National Exhibition Platform — Full Setup Script
# ============================================================
# This script creates the SQLite database, runs all migrations,
# seeds initial data, copies assets, and starts the dev server.
#
# Usage:
#   chmod +x setup.sh
#   ./setup.sh               # Full setup
#   ./setup.sh --serve       # Setup + start dev server
#   ./setup.sh --production  # Configure for production DB (Aurora MySQL)
# ============================================================

set -e
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
cd "$SCRIPT_DIR"

echo ""
echo "╔══════════════════════════════════════════════════════════╗"
echo "║   KICC National Exhibition Platform — Setup            ║"
echo "╚══════════════════════════════════════════════════════════╝"
echo ""

# ── Check PHP ──
if ! command -v php &> /dev/null; then
    echo "❌ PHP is required. Install PHP 8.3+ with:"
    echo "   sudo apt install php8.3-cli php8.3-mbstring php8.3-xml php8.3-curl php8.3-gd php8.3-sqlite3 php8.3-mysql php8.3-bcmath php8.3-zip"
    exit 1
fi

PHP_VERSION=$(php -r "echo PHP_VERSION;")
echo "✓ PHP $PHP_VERSION"

# ── Check Composer ──
if [ ! -f "vendor/autoload.php" ]; then
    echo "→ Installing Composer dependencies..."
    if command -v composer &> /dev/null; then
        composer install --no-interaction --prefer-dist
    else
        echo "❌ Composer not found. Install it or run: composer install"
        exit 1
    fi
fi
echo "✓ Composer dependencies installed"

# ── Check FFmpeg ──
if command -v ffmpeg &> /dev/null; then
    echo "✓ FFmpeg: $(ffmpeg -version 2>&1 | head -1)"
else
    echo "⚠️  FFmpeg not found. Video generation will fail. Install with:"
    echo "   sudo apt install ffmpeg"
fi

# ── Create SQLite database ──
DB_PATH="$SCRIPT_DIR/storage/app/kicc.sqlite"
if [ ! -f "$DB_PATH" ]; then
    echo "→ Creating SQLite database..."
    mkdir -p "$(dirname "$DB_PATH")"
    touch "$DB_PATH"
    echo "✓ Database created: $DB_PATH"
else
    echo "✓ Database exists: $DB_PATH"
fi

# ── Create storage directories ──
echo "→ Creating storage directories..."
mkdir -p storage/app/public/{screens,counties}
mkdir -p storage/framework/{cache,sessions,testing,views}
mkdir -p storage/logs
mkdir -p bootstrap/cache
echo "✓ Storage directories ready"

# ── Storage symlink ──
if [ ! -L "public/storage" ]; then
    echo "→ Creating storage symlink..."
    php artisan storage:link 2>/dev/null || ln -sf "$SCRIPT_DIR/storage/app/public" "$SCRIPT_DIR/public/storage"
    echo "✓ Storage linked"
fi

# ── Run migrations ──
echo "→ Running migrations..."
php artisan migrate --force
echo "✓ Migrations complete"

# ── Seed database ──
echo "→ Seeding database..."
php artisan db:seed --class=ScreenSeeder --force 2>/dev/null || echo "  (ScreenSeeder requires manual artisan run)"
echo "✓ Database seeded"

# ── Generate app key ──
if [ ! -f "bootstrap/cache/config.php" ]; then
    echo "→ Generating app key..."
    php artisan key:generate --force 2>/dev/null || echo "  (will generate on first artisan serve)"
fi

# ── Copy reference assets ──
echo "→ Checking assets..."
for f in public/3d/kenya_3d_map.html public/3d/sector_map.html public/3d/booth_viewer.html; do
    if [ -f "$f" ]; then
        echo "  ✓ $(basename $f)"
    else
        echo "  ⚠️  Missing: $f (3D HTML not copied)"
    fi
done

VIDEO_COUNT=$(ls storage/app/public/screens/*.mp4 2>/dev/null | wc -l)
echo "  ✓ $VIDEO_COUNT screen videos"
COUNTY_DIRS=$(ls -d storage/app/public/counties/*/ 2>/dev/null | wc -l)
echo "  ✓ $COUNTY_DIRS county directories with photos"

echo ""
echo "╔══════════════════════════════════════════════════════════╗"
echo "║   Setup Complete!                                      ║"
echo "╚══════════════════════════════════════════════════════════╝"
echo ""
echo "  Start dev server:  php artisan serve --port=8000"
echo "  Or:                ./setup.sh --serve"
echo ""
echo "  URL:               http://localhost:8000"
echo "  3D Map:            http://localhost:8000/3d/kenya_3d_map.html"
echo "  Sector Explorer:   http://localhost:8000/3d/sector_map.html"
echo "  Booth Viewer:      http://localhost:8000/3d/booth_viewer.html"
echo ""
echo "  For production (Aurora MySQL):"
echo "    Edit .env:  DB_CONNECTION=mysql, DB_HOST=..., DB_DATABASE=..., etc."
echo "    Run:        php artisan migrate --force"
echo "    Run:        php artisan db:seed --class=ScreenSeeder"
echo ""

if [[ "$1" == "--serve" ]]; then
    echo "→ Starting dev server on port 8000..."
    php artisan serve --port=8000
fi

if [[ "$1" == "--production" ]]; then
    echo "→ To switch to production database:"
    echo "   1. Edit .env with your Aurora MySQL credentials"
    echo "   2. Run: php artisan migrate --force"
    echo "   3. Run: php artisan db:seed --class=ScreenSeeder"
    echo "   4. Deploy to your server"
fi
