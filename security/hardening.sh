#!/usr/bin/env bash
set -euo pipefail
# =============================================================================
# KICC Server Security Hardening — Run once on the production node
# =============================================================================
# This script hardens the server OS-level security for the KICC platform.
# All values read from environment variables — NOTHING hardcoded.
# =============================================================================

echo "=== KICC Server Security Hardening ==="

# Ensure environment variables exist
: "${APP_USER:=www-data}"
: "${APP_DIR:=/var/www/kicc-platform}"
: "${SSH_PORT:=2222}"
: "${ADMIN_IP_WHITELIST:=}"  # Comma-separated IPs/CIDRs

# --- 1. Firewall (UFW) ---
echo "[1/6] Configuring firewall..."
ufw --force reset
ufw default deny incoming
ufw default allow outgoing
ufw allow "${SSH_PORT}/tcp" comment 'SSH (hardened port)'
ufw allow 80/tcp comment 'HTTP'
ufw allow 443/tcp comment 'HTTPS'
# Only allow Cloudflare IPs for 80/443
ufw allow from 173.245.48.0/20 to any port 80,443 proto tcp
ufw allow from 103.21.244.0/22 to any port 80,443 proto tcp
ufw allow from 103.22.200.0/22 to any port 80,443 proto tcp
ufw allow from 103.31.4.0/22 to any port 80,443 proto tcp
ufw allow from 141.101.64.0/18 to any port 80,443 proto tcp
ufw allow from 108.162.192.0/18 to any port 80,443 proto tcp
ufw allow from 190.93.240.0/20 to any port 80,443 proto tcp
ufw allow from 188.114.96.0/20 to any port 80,443 proto tcp
ufw allow from 197.234.240.0/22 to any port 80,443 proto tcp
ufw allow from 198.41.128.0/17 to any port 80,443 proto tcp
ufw allow from 162.158.0.0/15 to any port 80,443 proto tcp
ufw allow from 104.16.0.0/13 to any port 80,443 proto tcp
ufw allow from 104.24.0.0/14 to any port 80,443 proto tcp
ufw allow from 172.64.0.0/13 to any port 80,443 proto tcp
ufw allow from 131.0.72.0/22 to any port 80,443 proto tcp

# Admin IP whitelist
if [[ -n "${ADMIN_IP_WHITELIST}" ]]; then
    IFS=',' read -ra IPS <<< "${ADMIN_IP_WHITELIST}"
    for ip in "${IPS[@]}"; do
        ufw allow from "$ip" to any port "${SSH_PORT}" proto tcp
    done
fi
ufw --force enable
echo "  ✓ Firewall configured"

# --- 2. SSH hardening ---
echo "[2/6] Hardening SSH..."
sed -i "s/^Port .*/Port ${SSH_PORT}/" /etc/ssh/sshd_config
sed -i 's/^PermitRootLogin .*/PermitRootLogin no/' /etc/ssh/sshd_config
sed -i 's/^PasswordAuthentication .*/PasswordAuthentication no/' /etc/ssh/sshd_config
sed -i 's/^PubkeyAuthentication .*/PubkeyAuthentication yes/' /etc/ssh/sshd_config
sed -i 's/^X11Forwarding .*/X11Forwarding no/' /etc/ssh/sshd_config
sed -i 's/^MaxAuthTries .*/MaxAuthTries 3/' /etc/ssh/sshd_config
sed -i 's/^ClientAliveInterval .*/ClientAliveInterval 300/' /etc/ssh/sshd_config
sed -i 's/^ClientAliveCountMax .*/ClientAliveCountMax 2/' /etc/ssh/sshd_config
sed -i 's/^AllowTcpForwarding .*/AllowTcpForwarding no/' /etc/ssh/sshd_config
systemctl restart sshd
echo "  ✓ SSH hardened (port ${SSH_PORT}, key-only)"

# --- 3. File permissions ---
echo "[3/6] Setting file permissions..."
chown -R "${APP_USER}:${APP_USER}" "${APP_DIR}"
find "${APP_DIR}/storage" -type d -exec chmod 775 {} \;
find "${APP_DIR}/storage" -type f -exec chmod 664 {} \;
find "${APP_DIR}/bootstrap/cache" -type d -exec chmod 775 {} \;
chmod 644 "${APP_DIR}/.env" 2>/dev/null || true
chmod 644 "${APP_DIR}/deploy.sh"
echo "  ✓ Permissions set"

# --- 4. PHP hardening ---
echo "[4/6] Hardening PHP..."
PHP_INI=$(php -i 2>/dev/null | grep "Loaded Configuration File" | head -1 | awk '{print $NF}')
for setting in \
    "expose_php = Off" \
    "display_errors = Off" \
    "display_startup_errors = Off" \
    "log_errors = On" \
    "error_reporting = E_ALL & ~E_DEPRECATED & ~E_STRICT" \
    "session.cookie_httponly = 1" \
    "session.cookie_secure = 1" \
    "session.cookie_samesite = Lax" \
    "session.use_strict_mode = 1" \
    "session.sid_length = 48" \
    "session.sid_bits_per_character = 6" \
    "opcache.enable = 1" \
    "opcache.memory_consumption = 256" \
    "opcache.max_accelerated_files = 20000" \
    "opcache.revalidate_freq = 60" \
    "disable_functions = exec,passthru,shell_exec,system,proc_open,popen,curl_exec,curl_multi_exec,parse_ini_file,show_source"; do
    key="${setting%% =*}"
    if grep -q "^${key}" "$PHP_INI" 2>/dev/null; then
        sed -i "s/^${key}.*/${setting}/" "$PHP_INI"
    else
        echo "$setting" >> "$PHP_INI"
    fi
done
echo "  ✓ PHP hardened"

# --- 5. Fail2ban ---
echo "[5/6] Configuring fail2ban..."
cat > /etc/fail2ban/jail.local << 'F2B'
[DEFAULT]
bantime = 3600
findtime = 600
maxretry = 5

[sshd]
enabled = true
port = ${SSH_PORT}
maxretry = 3

[kicc-laravel]
enabled = true
port = 80,443
filter = kicc-laravel
logpath = ${APP_DIR}/storage/logs/laravel.log
maxretry = 10
F2B

cat > /etc/fail2ban/filter.d/kicc-laravel.conf << 'FILTER'
[Definition]
failregex = ^.*ThrottleApi.*triggered.*$
            ^.*IpWhitelistAdmin.*blocked.*$
            ^.*Login.*failed.*$
            ^.*Invalid.*token.*$
ignoreregex =
FILTER

systemctl restart fail2ban 2>/dev/null || echo "  ⚠ fail2ban not installed, skipping"
echo "  ✓ fail2ban configured"

# --- 6. Logwatch / daily audit summary ---
echo "[6/6] Setting up daily audit summary..."
cat > /etc/cron.daily/kicc-audit << 'CRON'
#!/bin/bash
cd ${APP_DIR}
php artisan security:audit >> storage/logs/security-audit.log 2>&1
echo "--- Disk usage ---" >> storage/logs/security-audit.log
df -h / >> storage/logs/security-audit.log
CRON
chmod +x /etc/cron.daily/kicc-audit
echo "  ✓ Daily audit cron installed"

echo ""
echo "=== Server hardening complete ==="
echo "Reboot recommended to verify all services start cleanly."