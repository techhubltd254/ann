#!/bin/bash
# KICC Offline County Bundle Builder
# Usage: bash scripts/build-county-bundle.sh <county_slug>
# Produces: dist/<county_slug>-offline-bundle.zip
set -euo pipefail

if [ $# -lt 1 ]; then
  echo "Usage: $0 <county_slug>"
  echo "Example: $0 kilifi"
  exit 1
fi

COUNTY_SLUG="$1"
BUNDLE_DIR="dist/${COUNTY_SLUG}-offline-bundle"
ARTIFACT="dist/${COUNTY_SLUG}-offline-bundle.zip"
APP_DIR="$(cd "$(dirname "$0")/.." && pwd)"

echo "=== KICC Offline County Bundle Builder ==="
echo "County:  $COUNTY_SLUG"
echo "Source:  $APP_DIR"
echo "Target:  $BUNDLE_DIR"
echo ""

# 1. Verify county exists in central DB
echo "[1/8] Verifying county..."
COUNTY_ID=$(/usr/bin/php artisan tinker --execute="echo App\Models\County::where('slug','$COUNTY_SLUG')->first()?->id ?? '0';" 2>/dev/null)
if [ "$COUNTY_ID" = "0" ] || [ -z "$COUNTY_ID" ]; then
  echo "❌ County '$COUNTY_SLUG' not found. Aborting."
  exit 1
fi
echo "   ✅ County ID: $COUNTY_ID"

# 2. Generate sync HMAC key for this county
echo "[2/8] Generating sync key..."
SYNC_KEY_ID=$(/usr/bin/php artisan tinker --execute="
echo (string) \Illuminate\Support\Str::uuid();
" 2>/dev/null)
SYNC_KEY_SECRET=$(openssl rand -hex 32)
/usr/bin/php artisan tinker --execute="
\Illuminate\Support\Facades\DB::table('sync_keys')->insert([
    'county_slug' => '$COUNTY_SLUG',
    'key_id' => '$SYNC_KEY_ID',
    'key_hmac' => '$SYNC_KEY_SECRET',
    'is_revoked' => false,
    'created_at' => now(),
]);
echo 'done';
" > /dev/null 2>&1
echo "   ✅ Sync key generated: ${SYNC_KEY_ID:0:8}..."

# 3. Create clean bundle directory
echo "[3/8] Creating bundle directory..."
rm -rf "$BUNDLE_DIR"
mkdir -p "$BUNDLE_DIR/storage/app/county/${COUNTY_ID}/media"
mkdir -p "$BUNDLE_DIR/storage/logs"
mkdir -p "$BUNDLE_DIR/bootstrap/cache"

# 4. Seed SQLite database for this county
echo "[4/8] Seeding SQLite database..."
/usr/bin/php artisan tinker --execute="
\$dbPath = '$BUNDLE_DIR/database/database.sqlite';
if (file_exists(\$dbPath)) unlink(\$dbPath);
touch(\$dbPath);

// Connect and create schema
\$pdo = new PDO('sqlite:'.\$dbPath);
\$schema = file_get_contents('$APP_DIR/storage/app/schema-syncable.sql');
\$pdo->exec(\$schema);

// Seed county data
\$county = App\Models\County::where('slug','$COUNTY_SLUG')->first();
\$tables = [
    'sectors' => \$county->sectors()->get(),
    'sector_entities' => App\Models\SectorEntity::where('county_id',\$county->id)->get(),
    'county_tourism_attractions' => App\Models\CountyTourismAttraction::where('county_id',\$county->id)->get(),
    'county_hotels' => App\Models\CountyHotel::where('county_id',\$county->id)->get(),
    'county_farms' => App\Models\CountyFarm::where('county_id',\$county->id)->get(),
    'county_health_facilities' => App\Models\CountyHealthFacility::where('county_id',\$county->id)->get(),
    'county_institutions' => App\Models\CountyInstitution::where('county_id',\$county->id)->get(),
    'county_transport' => App\Models\CountyTransport::where('county_id',\$county->id)->get(),
    'county_culture_sites' => App\Models\CountyCultureSite::where('county_id',\$county->id)->get(),
    'county_products' => App\Models\CountyProduct::where('county_id',\$county->id)->get(),
];
foreach (\$tables as \$table => \$rows) {
    foreach (\$rows as \$row) {
        \$arr = \$row->toArray();
        \$arr['local_id'] = (string) \Illuminate\Support\Str::uuid();
        \$arr['central_id'] = \$arr['id'];
        \$arr['sync_status'] = 'synced';
        \$arr['content_hash'] = md5(json_encode(\$arr));
        \$arr['synced_at'] = now();
        \$columns = implode(',', array_keys(\$arr));
        \$values = implode(',', array_fill(0, count(\$arr), '?'));
        \$pdo->prepare(\"INSERT INTO \$table (\$columns) VALUES (\$values)\")->execute(array_values(\$arr));
    }
}

// Create county admin user
\$adminEmail = 'admin@'.\$county->slug.'.kicc.local';
\$adminPassword = bin2hex(random_bytes(4));
\$pdo->prepare('INSERT INTO users (name, email, password, county_id, admin_type, created_at, updated_at) VALUES (?,?,?,?,?,?,?)')->execute([
    \$county->name.' Admin', \$adminEmail, password_hash(\$adminPassword, PASSWORD_BCRYPT),
    \$county->id, 'county', now(), now(),
]);

echo json_encode(['email'=>\$adminEmail,'password'=>\$adminPassword]);
" 2>/dev/null > /tmp/bundle_creds.json
echo "   ✅ SQLite database seeded"

# 5. Copy Laravel app (exclude unnecessary files)
echo "[5/8] Copying Laravel app..."
rsync -a --exclude='vendor' --exclude='node_modules' --exclude='.git' \
  --exclude='storage/logs/*' --exclude='storage/framework/cache/*' \
  --exclude='storage/framework/sessions/*' --exclude='storage/framework/views/*' \
  --exclude='public/build' --exclude='.env' \
  "$APP_DIR/" "$BUNDLE_DIR/" 2>/dev/null

# 6. Create .env.county
echo "[6/8] Creating environment config..."
cat > "$BUNDLE_DIR/.env.county" << EOF
APP_ENV=local
APP_DEBUG=false
APP_URL=http://127.0.0.1:8080
DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite
SYNC_MODE=offline_satellite
COUNTY_ID=${COUNTY_ID}
COUNTY_SLUG=${COUNTY_SLUG}
SYNC_KEY_ID=${SYNC_KEY_ID}
SYNC_KEY_SECRET=${SYNC_KEY_SECRET}
SYNC_CENTRAL_URL=https://kicctest.org
EOF

# 7. Create start script
echo "[7/8] Creating launcher..."
cat > "$BUNDLE_DIR/start-county.sh" << 'SCRIPT'
#!/bin/bash
# Start the KICC County Admin offline app
DIR="$(cd "$(dirname "$0")" && pwd)"
cd "$DIR"
PHP_BINARY="${PHP_BINARY:-php}"

echo "================================================="
echo "  KICC County Admin — Offline Mode"
echo "================================================="
echo ""
echo "Starting local server..."
echo "Open: http://127.0.0.1:8080"
echo ""
echo "Press Ctrl+C to stop."
echo ""

$PHP_BINARY -S 127.0.0.1:8080 -t public/ server.php
SCRIPT
chmod +x "$BUNDLE_DIR/start-county.sh"

cat > "$BUNDLE_DIR/start-county.bat" << 'SCRIPT'
@echo off
echo KICC County Admin — Offline Mode
echo Starting server...
echo Open: http://127.0.0.1:8080
php -S 127.0.0.1:8080 -t public/ server.php
pause
SCRIPT

# 8. Package into zip
echo "[8/8] Packaging bundle..."
cd "$(dirname "$BUNDLE_DIR")"
BUNDLE_NAME="${COUNTY_SLUG}-offline-bundle"
zip -r "${BUNDLE_NAME}.zip" "$BUNDLE_NAME" -x "*/vendor/*" "*/node_modules/*" "*.git*" > /dev/null 2>&1
rm -rf "$BUNDLE_DIR"

CREDS=$(cat /tmp/bundle_creds.json)
ADMIN_EMAIL=$(echo "$CREDS" | grep -oP '"email":"\K[^"]+')
ADMIN_PASS=$(echo "$CREDS" | grep -oP '"password":"\K[^"]+')

echo ""
echo "================================================="
echo "  ✅ Bundle Created"
echo "================================================="
echo "Artifact: $ARTIFACT"
echo "County:   $COUNTY_SLUG (ID: $COUNTY_ID)"
echo "Sync Key: ${SYNC_KEY_ID:0:8}..."
echo ""
echo "Admin Login:"
echo "  Email:    $ADMIN_EMAIL"
echo "  Password: $ADMIN_PASS"
echo ""
echo "Deploy: unzip, run ./start-county.sh, open http://127.0.0.1:8080"
echo "================================================="
