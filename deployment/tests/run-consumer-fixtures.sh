#!/usr/bin/env bash
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
WORK="$(mktemp -d /tmp/kicc-consumer-fixtures.XXXXXXXX)"
trap 'rm -rf "$WORK"' EXIT
mkdir -p "$WORK/integrations-service"
cp -a "$ROOT/integrations-service/lib" "$ROOT/integrations-service/consumers" "$WORK/integrations-service/"
cp "$ROOT/package.json" "$WORK/package.json"
ln -s "$ROOT/integrations-service/node_modules" "$WORK/integrations-service/node_modules"
cp "$ROOT/deployment/tests/test-consumer-reliability.mjs" "$WORK/test_reliability.mjs"
cp "$ROOT/deployment/tests/test-consumer-sql-scores.mjs" "$WORK/test_sql_and_scores.mjs"
cd "$WORK"
MOCK_MODE=true node test_reliability.mjs
MOCK_MODE=true node test_sql_and_scores.mjs
if [ -n "${KICC_FIXTURE_REPORT_DIR:-}" ]; then
 mkdir -p "$KICC_FIXTURE_REPORT_DIR"
 cp reliability-fixture-verification.json sql-score-fixture-verification.json "$KICC_FIXTURE_REPORT_DIR/"
fi
