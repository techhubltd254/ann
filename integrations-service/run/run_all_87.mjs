// run/run_all_87.mjs — drives all 87 pipelines through the same settlement chain.
import { resetArtifacts, readEvents, readLedger, LEDGER_PATH } from "../lib/core.js";
import { runAll, PIPELINES } from "../pipelines/settle-to-desk.mjs";

resetArtifacts();
console.log("\n" + "=".repeat(96));
console.log("ALL 87 PIPELINES — INGEST -> VERIFY -> MATCH -> SETTLE -> COMMISSION");
console.log("=".repeat(96));

const t0 = Date.now();
const rows = await runAll();
const elapsed = Date.now() - t0;

const byCat = {};
for (const r of rows) {
  byCat[r.category] = byCat[r.category] || { n: 0, value: 0, commission: 0, statuses: {} };
  const b = byCat[r.category];
  b.n++; b.value += r.value_kes; b.commission += r.commission_kes;
  b.statuses[r.launch_status] = (b.statuses[r.launch_status] || 0) + 1;
}

console.log(`\nBY CATEGORY`);
console.log("-".repeat(96));
console.log(`  ${"CATEGORY".padEnd(40)} ${"LINES".padStart(5)} ${"VALUE KES".padStart(14)} ${"COMMISSION KES".padStart(15)}  STATUS`);
for (const [cat, b] of Object.entries(byCat).sort((a, c) => c[1].commission - a[1].commission)) {
  const st = Object.entries(b.statuses).map(([k, v]) => `${k}:${v}`).join(" ");
  console.log(`  ${cat.padEnd(40)} ${String(b.n).padStart(5)} ${b.value.toLocaleString().padStart(14)} ${b.commission.toLocaleString().padStart(15)}  ${st}`);
}

const totals = rows.reduce((a, r) => ({ value: a.value + r.value_kes, commission: a.commission + r.commission_kes }), { value: 0, commission: 0 });
console.log("-".repeat(96));
console.log(`  ${"TOTAL".padEnd(40)} ${String(rows.length).padStart(5)} ${totals.value.toLocaleString().padStart(14)} ${totals.commission.toLocaleString().padStart(15)}`);

const byMech = {};
for (const r of rows) { byMech[r.mechanism] = byMech[r.mechanism] || { n: 0, commission: 0 }; byMech[r.mechanism].n++; byMech[r.mechanism].commission += r.commission_kes; }
console.log(`\nBY MECHANISM`);
console.log("-".repeat(96));
for (const [m, b] of Object.entries(byMech).sort((a, c) => c[1].commission - a[1].commission)) {
  console.log(`  ${m.padEnd(14)} ${String(b.n).padStart(3)} lines   commission KES ${b.commission.toLocaleString()}`);
}

console.log(`\nCHECKS`);
console.log("-".repeat(96));
console.log(`  pipelines driven            : ${rows.length} / ${PIPELINES.length}   ${rows.length === PIPELINES.length ? "OK" : "MISMATCH"}`);
console.log(`  ledger rows written         : ${readLedger().length}`);
console.log(`  events emitted              : ${readEvents().length}`);
console.log(`  every pipeline settled      : ${rows.every((r) => r.value_kes > 0) ? "yes" : "NO"}`);
console.log(`  distinct owner desks used   : ${new Set(rows.map((r) => r.owner_desk)).size}`);
console.log(`  elapsed                     : ${elapsed} ms`);
console.log(`  ledger                      : ${LEDGER_PATH}`);
console.log("=".repeat(96) + "\n");
