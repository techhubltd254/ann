#!/usr/bin/env bash
# KICC Platform — Grafana + Prometheus bootstrap
# Run once on the droplet to enable full observability.
# Requires: Ubuntu 22.04+, root/sudo
set -euo pipefail

echo "=== KICC Observability Stack ==="

# 1. Install Grafana
echo "[1/3] Installing Grafana..."
apt-get install -y -qq apt-transport-https software-properties-common wget > /dev/null
mkdir -p /etc/apt/keyrings
wget -q -O - https://apt.grafana.com/gpg.key | gpg --dearmor > /etc/apt/keyrings/grafana.gpg
echo "deb [signed-by=/etc/apt/keyrings/grafana.gpg] https://apt.grafana.com stable main" > /etc/apt/sources.list.d/grafana.list
apt-get update -qq > /dev/null
apt-get install -y -qq grafana > /dev/null
echo "  ✓ Grafana installed"

# 2. Deploy provisioning configs
echo "[2/3] Deploying dashboards..."
cp infra/grafana/provisioning/dashboards.yml /etc/grafana/provisioning/dashboards/kicc.yml
cp infra/grafana/provisioning/datasources.yml /etc/grafana/provisioning/datasources/kicc-ds.yml
echo "  ✓ Configs deployed"

# 3. Start Grafana
echo "[3/3] Starting Grafana..."
systemctl daemon-reload
systemctl enable grafana-server
systemctl start grafana-server
echo "  ✓ Grafana running on localhost:3000"

echo ""
echo "=== Done ==="
echo "Grafana: http://localhost:3000 (default admin/admin)"
echo "Metrics: https://kicctest.org/api/metrics"
echo ""
echo "Next: add a cron job to scrape metrics every 60s:"
echo "  */1 * * * * curl -s https://kicctest.org/api/metrics > /dev/null"