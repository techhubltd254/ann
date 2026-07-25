#!/bin/bash
# KICC Platform Quick Restart
# Run this if the site goes down

echo "=== Killing old processes ==="
kill $(pgrep -f frankenphp) 2>/dev/null
kill $(pgrep -f cloudflared) 2>/dev/null
sleep 2

echo "=== Starting FrankenPHP ==="
rm -rf /home/kicc/.config/caddy/ /home/kicc/.local/share/caddy/
mkdir -p /home/kicc/.config/caddy /home/kicc/.local/share/caddy
/tmp/frankenphp run --config /home/kicc/Desktop/kicc/kenya-3d-platform/laravel-backend/Caddyfile > /tmp/php.log 2>&1 &
sleep 5

echo "=== Testing local ==="
curl -s -o /dev/null -w "Local: HTTP %{http_code}\n" http://localhost:8080/

echo "=== Starting tunnel ==="
/tmp/cloudflared tunnel --url http://localhost:8080 2>&1 | while read line; do
  echo "$line"
  if echo "$line" | grep -q "trycloudflare.com"; then
    TURL=$(echo "$line" | grep -oP 'https://[a-z]+-[a-z0-9]+-[a-z]+-[a-z]+-[a-z0-9]+\.trycloudflare\.com')
    echo ""
    echo "============================================"
    echo "  NEW TUNNEL: $TURL"
    echo "============================================"
    echo ""
    echo "Update Worker with:"
    echo "  cd /tmp/kicc-worker"
    echo "  sed -i 's|BACKEND_URL.*|BACKEND_URL\":\"${TURL}\"|' wrangler.jsonc"
    echo "  /tmp/node-v22.13.0-linux-x64/bin/npx wrangler deploy"
    break
  fi
done
