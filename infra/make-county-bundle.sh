#!/usr/bin/env bash
# Generate a per-county install bundle.
# Usage: ./make-county-bundle.sh <slug> "<County Name>"
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
TPL="$ROOT/infra/county-bundle"
OUT="$ROOT/dist/county-bundles"

SLUG="${1:?usage: make-county-bundle.sh <slug> <Name>}"
NAME="${2:-$1}"

OTA_KEY=$(cat "$ROOT/infra/ota/release_ed25519.public.hex")
SINK_KEY=$(cat "$ROOT/infra/ota/backup_ingest_key.hex")

PKG="$OUT/kicc-county-$SLUG"
rm -rf "$PKG" "$PKG.tar.gz"
mkdir -p "$PKG/server" "$PKG/web"

# Engine + web console (current builds)
cp "$ROOT/engine/build/libs/engine-0.1.0.jar" "$PKG/server/kicc-engine.jar"
cp -r "$ROOT/admin-web/dist/"* "$PKG/web/"

# Scripts + config
cp "$TPL/start-county-server.sh" "$PKG/server/"
cp "$ROOT/distro/server/ota-restart.sh" "$PKG/server/"
sed "s/{{COUNTY_SLUG}}/$SLUG/g" "$TPL/kicc-engine.service" > "$PKG/server/kicc-engine.service"
cp "$TPL/install.sh" "$PKG/"
sed -e "s/{{COUNTY_SLUG}}/$SLUG/g" \
    -e "s/{{COUNTY_NAME}}/$NAME/g" \
    -e "s/{{OTA_PUBLIC_KEY}}/$OTA_KEY/g" \
    -e "s/{{BACKUP_SINK_KEY}}/$SINK_KEY/g" \
    "$TPL/county.env.template" > "$PKG/county.env"
chmod 600 "$PKG/county.env"
chmod +x "$PKG/install.sh" "$PKG/server/"*.sh

# Per-county README
cat > "$PKG/README.md" <<EOF
# KICC County Server — $NAME ($SLUG)

Self-contained county administration server for the KICC Digital Economy Platform.

## Install (on the county machine, as root)
\`\`\`bash
tar -xzf kicc-county-$SLUG.tar.gz
cd kicc-county-$SLUG
sudo ./install.sh
sudo systemctl start kicc-county
\`\`\`

Requires: Java 21+ (\`apt install openjdk-21-jre-headless\`), 2 GB RAM, 10 GB disk.

## Use
- Admin console: **http://localhost:8091/admin**
- County admin: \`county@kicc.go.ke\` / \`county@2026\` (scoped to **$SLUG** — change the password immediately)
- Exhibitor: \`exhibitor@kicc.go.ke\` / \`exhibitor@2026\`

## What it does
- All $NAME county data edits (tourism, hotels, farms, health, institutions, transport, culture, products)
  sync into the platform database and appear on the public site.
- **OTA updates**: checks kicctest.org daily (signed releases, auto-rollback on failure).
- **Backups**: nightly encrypted backup, pushed off-host to the mother engine.
- County scope is enforced server-side — this server cannot read or write other counties' data.

## Config
\`county.env\` (ports, passwords, OTA channel) · \`county.env.local\` (generated secrets — never share).
EOF

tar -C "$OUT" -czf "$PKG.tar.gz" "kicc-county-$SLUG"
rm -rf "$PKG"
echo "✓ $PKG.tar.gz ($(du -h "$PKG.tar.gz" | cut -f1))"
