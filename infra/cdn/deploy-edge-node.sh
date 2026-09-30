#!/usr/bin/env bash
# KICC CDN — Edge PoP Node Bootstrap
# Run once on any VPS in a target region (US East, EU Frankfurt,
# Africa Cape Town, Asia Singapore) to bring up an edge cache node.
#
# Prerequisites:
#   - Ubuntu 22.04+ or Debian 12+
#   - Root or sudo access
#   - DNS record for this node's hostname
#   - Ports 80/443 open
#
# Usage: sudo bash deploy-edge-node.sh <hostname> [email for certbot]

set -euo pipefail

HOSTNAME="${1:?Usage: $0 <cdn-hostname> [email]}"
EMAIL="${2:-admin@kicctest.org}"
CONF_DIR="$(dirname "$0")"

echo "=== KICC CDN Edge Node Bootstrap ==="
echo "Hostname: $HOSTNAME"
echo "Email:    $EMAIL"
echo ""

# 1. Install dependencies
echo "[1/6] Installing packages..."
apt-get update -qq
apt-get install -y -qq nginx-full certbot python3-certbot-nginx > /dev/null
echo "  ✓ nginx-full + certbot installed"

# 2. Create cache directory on NVMe if available, else SSD
echo "[2/6] Setting up cache storage..."
if [ -d /mnt/nvme ]; then
    CACHE_DIR="/mnt/nvme/nginx-cache"
elif [ -b /dev/nvme0n1 ]; then
    mkdir -p /mnt/nvme
    mount /dev/nvme0n1 /mnt/nvme 2>/dev/null || true
    CACHE_DIR="/mnt/nvme/nginx-cache"
else
    CACHE_DIR="/var/cache/nginx/cdn"
fi
mkdir -p "$CACHE_DIR"
chown www-data:www-data "$CACHE_DIR"
echo "  ✓ cache dir: $CACHE_DIR"

# 3. Deploy Nginx config
echo "[3/6] Deploying edge config..."
cp "$CONF_DIR/nginx-edge.conf" /etc/nginx/conf.d/cdn_edge.conf
# Patch the cache path if non-default
if [ "$CACHE_DIR" != "/var/cache/nginx/cdn" ]; then
    sed -i "s|/var/cache/nginx/cdn|$CACHE_DIR|g" /etc/nginx/conf.d/cdn_edge.conf
fi
echo "  ✓ config deployed"

# 4. Validate and reload
echo "[4/6] Validating Nginx config..."
nginx -t 2>&1
systemctl reload nginx
echo "  ✓ nginx reloaded"

# 5. Obtain SSL certificate
echo "[5/6] Obtaining TLS certificate..."
certbot --nginx -d "$HOSTNAME" --non-interactive --agree-tos -m "$EMAIL" 2>&1 | tail -3
echo "  ✓ TLS active"

# 6. Configure firewall
echo "[6/6] Configuring firewall..."
ufw allow 80/tcp 2>/dev/null || true
ufw allow 443/tcp 2>/dev/null || true
ufw --force enable 2>/dev/null || true
echo "  ✓ firewall configured"

echo ""
echo "=== Edge Node Ready ==="
echo "  Hostname:  https://$HOSTNAME"
echo "  Cache:     $CACHE_DIR"
echo "  Health:    https://$HOSTNAME/healthz"
echo ""
echo "Next steps:"
echo "  1. Add $HOSTNAME as an A record at your DNS provider"
echo "  2. Add this node to your load balancer's origin pool"
echo "  3. Verify: curl -I https://$HOSTNAME/ → X-Cache-Status: MISS"
echo "             curl -I https://$HOSTNAME/ → X-Cache-Status: HIT"