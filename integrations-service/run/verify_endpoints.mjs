// run/verify_endpoints.mjs — with real keys in .env, probes every endpoint and
// reports which provider paths answer. Run this BEFORE trusting any live call.
import { ALL, configured, missingEnv } from "../registries.js";
import { env, isPlaceholder, credentialReport, log } from "../lib/core.js";

console.log("\n" + "=".repeat(90));
console.log("CREDENTIAL AND ENDPOINT CHECK");
console.log("=".repeat(90));
console.log(`MOCK_MODE = ${env("MOCK_MODE", "true")}   (set false in .env to go live)\n`);

const report = credentialReport(ALL);
console.log(`  ${"PROVIDER".padEnd(28)} ${"ENABLED".padEnd(8)} ${"MISSING VARS".padEnd(16)} WILL RUN LIVE`);
console.log("-".repeat(90));
for (const r of report) {
  console.log(`  ${r.label.padEnd(28)} ${String(r.enabled).padEnd(8)} ${String(r.missing.length).padEnd(16)} ${r.willRunLive ? "yes" : "no (mock)"}`);
}
const ready = report.filter((r) => r.willRunLive);
console.log("-".repeat(90));
console.log(`  ${ready.length} of ${report.length} providers fully credentialled.`);
for (const r of report.filter((x) => x.enabled && x.missing.length)) {
  console.log(`\n  ${r.label} still needs:`);
  for (const k of r.missing) console.log(`      ${k}`);
}
console.log("\n" + "=".repeat(90) + "\n");

if (process.argv.includes("--probe")) {
  console.log("Probing live endpoints (only providers with full credentials)...");
  for (const p of ALL) {
    if (!configured(p)) { console.log(`  ${p.id.padEnd(14)} skipped (no credentials)`); continue; }
    try {
      const ctl = new AbortController();
      const to = setTimeout(() => ctl.abort(), 8000);
      const r = await fetch(p.base_url, { method: "HEAD", signal: ctl.signal }).catch((e) => ({ status: `error: ${e.message}` }));
      clearTimeout(to);
      console.log(`  ${p.id.padEnd(14)} base_url ${p.base_url}  ->  ${r.status ?? "no status"}`);
    } catch (e) {
      console.log(`  ${p.id.padEnd(14)} base_url unreachable: ${e.message}`);
    }
  }
} else {
  console.log("Add --probe to make live reachability calls to each base URL.\n");
}
