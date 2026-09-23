// run/env_report.mjs — prints, per provider, exactly which .env vars are still blank.
// This is the "add your APIs here" list. Run:  node run/env_report.mjs
import fs from "fs";
import path from "path";
import { ROOT, env, isPlaceholder, flag } from "../lib/core.js";
import { ALL, PAYMENTS, FREIGHT, SERVICES } from "../registries.js";

const KIND_ORDER = { payment: 1, freight: 2, service: 3 };
const rows = [...ALL].sort((a, b) => (KIND_ORDER[a.kind] || 9) - (KIND_ORDER[b.kind] || 9));
const mode = env("MOCK_MODE", "true") === "true" ? "MOCK (no keys needed)" : "LIVE";

console.log("\n" + "=".repeat(112));
console.log("KICC — PROVIDER → ENVIRONMENT VARIABLE MAP");
console.log("=".repeat(112));
console.log(`MOCK_MODE = ${env("MOCK_MODE", "true")}  (${mode})`);
console.log(`providers = ${rows.length}   payments = ${Object.keys(PAYMENTS).length}   freight = ${Object.keys(FREIGHT).length}   services = ${Object.keys(SERVICES).length}\n`);
console.log(`  ${"PROVIDER".padEnd(16)} ${"KIND".padEnd(9)} ${"ENABLED VAR".padEnd(22)} ${"STILL BLANK"}`);
console.log("  " + "-".repeat(108));

const table = [];
for (const p of rows) {
  const missing = (p.envRequired || []).filter((k) => isPlaceholder(env(k)));
  console.log(`  ${p.id.padEnd(16)} ${String(p.kind).padEnd(9)} ${String(p.enabled_env).padEnd(22)} ${missing.length ? missing.join(", ") : "--- complete ---"}`);
  table.push({
    id: p.id, label: p.label, kind: p.kind, enabled_env: p.enabled_env,
    vars: p.envRequired || [], missing,
    endpoints: Object.entries(p.endpoints || {}).map(([k, v]) => `${k} ${v.method} ${v.path}${v.confirmed === false ? "  [PLACEHOLDER]" : v.confirmed === true ? "  [verified]" : ""}`),
  });
}
console.log("  " + "-".repeat(108));
const blank = table.reduce((a, t) => a + t.missing.length, 0);
console.log(`  ${blank} blank credential variables across ${table.filter((t) => t.missing.length).length} providers.`);
console.log(`  Fill them in integrations/.env (copy of .env.example), set MOCK_MODE=false, then: node run/verify_endpoints.mjs --probe\n`);

// machine-readable companion, reused by the docs build
const outDir = path.join(ROOT, "run", "artifacts");
fs.mkdirSync(outDir, { recursive: true });
fs.writeFileSync(path.join(outDir, "env-map.json"), JSON.stringify({ generated: new Date().toISOString(), mock_mode: env("MOCK_MODE", "true"), table }, null, 2));
console.log(`  env-map.json written to run/artifacts/\n`);
