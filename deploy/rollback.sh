#!/usr/bin/env bash
set -Eeuo pipefail
BASE=${1:-/var/www/kicc-experience}
TARGET=${2:-}
[[ $EUID -eq 0 ]] || { echo 'Run with sudo.' >&2; exit 1; }
if [[ -z "$TARGET" ]]; then [[ -f "$BASE/shared/previous-release" ]] || { echo 'First activation: restore the backed-up ORIGINAL Nginx vhost, then nginx -t && systemctl reload nginx. No prior new-app release exists.' >&2; exit 1; };TARGET=$(cat "$BASE/shared/previous-release");fi
TARGET=$(realpath "$TARGET")
[[ "$TARGET" == "$BASE/releases/"* && -f "$TARGET/artisan" ]] || { echo 'Refusing a target outside the release directory.' >&2; exit 1; }
ln -s "$TARGET" "$BASE/current.rollback";mv -Tf "$BASE/current.rollback" "$BASE/current"
nginx -t
systemctl reload php8.4-fpm
systemctl reload nginx
echo 'Release pointer restored. Database was NOT rolled back or erased.'
