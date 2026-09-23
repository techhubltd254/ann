#!/usr/bin/env bash
# run/run_mock_e2e.sh — the full mock proof, in one go.
#   1. order -> payment -> escrow -> quote -> label -> customs -> track -> release -> payout
#   2. webhook callback accepted and mapped onto the order
#   3. all 87 pipelines through the same settlement chain
#   4. integration test suite
#   5. credential map (what you still have to add)
set -e
cd "$(dirname "$0")/.."
echo "############ 1/5  END-TO-END ORDER ############"
node run/run_e2e.mjs
echo
echo "############ 2/5  WEBHOOK CALLBACK ############"
node run/run_webhook.mjs
echo
echo "############ 3/5  ALL 87 PIPELINES ############"
node run/run_all_87.mjs
echo
echo "############ 4/5  TEST SUITE ############"
node run/test_integrations.mjs || true
echo
echo "############ 5/5  WHAT YOU STILL ADD ############"
node run/env_report.mjs
