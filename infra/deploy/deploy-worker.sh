#!/usr/bin/env bash
# Deploy the edge gateway worker to Cloudflare (kicctest.org).
# Usage: CF_TOKEN=cfat_xxx ./deploy-worker.sh
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
META="$ROOT/infra/deploy/worker-meta.json"
CODE="$ROOT/edge/kicctest-gateway.js"
TOKEN="${CF_TOKEN:?set CF_TOKEN env}"
ACCOUNT="c8416e05ed0a3554806be51aac862ec4"

BOUNDARY="----kicc$(head -c 8 /dev/urandom | od -An -tx1 | tr -d ' \n')"
PAYLOAD=$(mktemp)
{
  printf -- "--%s\r\nContent-Disposition: form-data; name=\"metadata\"\r\nContent-Type: application/json\r\n\r\n" "$BOUNDARY"
  cat "$META"
  printf "\r\n--%s\r\nContent-Disposition: form-data; name=\"kicctest-gateway.js\"; filename=\"kicctest-gateway.js\"\r\nContent-Type: application/javascript+module\r\n\r\n" "$BOUNDARY"
  cat "$CODE"
  printf "\r\n--%s--\r\n" "$BOUNDARY"
} > "$PAYLOAD"

RESP=$(curl -s -X PUT "https://api.cloudflare.com/client/v4/accounts/$ACCOUNT/workers/scripts/kicctest-gateway" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: multipart/form-data; boundary=$BOUNDARY" \
  --data-binary @"$PAYLOAD")
rm -f "$PAYLOAD"
echo "$RESP" | grep -qE '"success"\s*:\s*true' && echo "✓ worker deployed" || { echo "✗ deploy failed:"; echo "$RESP" | head -c 400; exit 1; }
