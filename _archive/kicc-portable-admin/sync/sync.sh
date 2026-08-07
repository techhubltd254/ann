#!/usr/bin/env bash
# KICC Portable Admin — Sync Tool
# One-click sync between local SQLite and TiDB Cloud

set -e

PHP=$(which php 2>/dev/null || which php8.4 2>/dev/null || which php8.3 2>/dev/null || echo "php")
DIR="$(cd "$(dirname "$0")" && pwd)"

echo "╔══════════════════════════════════════════╗"
echo "║     KICC Data Sync Tool                 ║"
echo "╚══════════════════════════════════════════╝"
echo ""

cd "$DIR"

# Check if TiDB is reachable
echo "[TEST] Checking internet connection to TiDB..."
if php -r "try { new PDO('mysql:host=gateway01.eu-central-1.prod.aws.tidbcloud.com;port=4000', '28dbcDfwh5hEbSc.root', 'D8trCZaYhqZWo5Vq', [PDO::MYSQL_ATTR_SSL_CA => '/etc/ssl/certs/ca-certificates.crt', PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => true]); echo 'OK'; } catch(Exception \$e) { echo 'FAIL'; }" 2>/dev/null; then
    echo "[OK] TiDB is reachable!"
    echo ""
    
    echo "Select sync direction:"
    echo "  1) Pull from TiDB → Local (download latest data)"
    echo "  2) Push Local → TiDB (upload changes)"
    echo "  3) Both directions"
    echo ""
    read -p "Choice [1-3]: " choice
    
    case $choice in
        1) php artisan sync:from-tidb --force 2>&1 ;;
        2) php artisan sync:to-tidb --force 2>&1 ;;
        3) 
            echo "[SYNC] Pulling from TiDB..."
            php artisan sync:from-tidb --force 2>&1
            echo ""
            echo "[SYNC] Pushing to TiDB..."
            php artisan sync:to-tidb --force 2>&1
            ;;
        *) echo "Invalid choice" ;;
    esac
else
    echo "[FAIL] Cannot reach TiDB. Check internet connection."
    echo "        The app will work offline with local data."
fi

echo ""
echo "Press Enter to close..."
read