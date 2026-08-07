#!/usr/bin/env bash
# KICC County Server installer — run as root on the county's machine.
set -euo pipefail

SRC="$(cd "$(dirname "$0")" && pwd)"
source "$SRC/county.env"
: "${COUNTY_SLUG:?missing}"
DEST="/opt/kicc-county-$COUNTY_SLUG"

echo "==> Installing KICC county server ($COUNTY_SLUG) to $DEST"
id -u kicc-county >/dev/null 2>&1 || useradd --system --no-create-home --shell /usr/sbin/nologin kicc-county
mkdir -p "$DEST"
cp -r "$SRC"/{server,web,county.env,README.md} "$DEST"/
# Preserve an existing county.env.local (secrets) across reinstalls
if [[ -f "$SRC/county.env.local" ]]; then cp "$SRC/county.env.local" "$DEST"/; fi
chown -R kicc-county:kicc-county "$DEST"
chmod +x "$DEST"/server/*.sh

# systemd unit
cp "$SRC/server/kicc-engine.service" /etc/systemd/system/kicc-county.service
systemctl daemon-reload
systemctl enable kicc-county
echo "==> Installed. Start with:  systemctl start kicc-county"
echo "==> Console:  http://localhost:8091/admin  (county@kicc.go.ke / see county.env)"
