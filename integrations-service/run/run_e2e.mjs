// run/run_e2e.mjs — executes the whole order-to-cash chain and prints the trace.
import fs from "fs";
import path from "path";
import { ROOT, resetArtifacts, readEvents, readLedger, LEDGER_PATH, EVENTS_PATH, env } from "../lib/core.js";
import { runOrder } from "../pipelines/order-to-cash.mjs";

const order = JSON.parse(fs.readFileSync(path.join(ROOT, "..", "samples", "order-example.json"), "utf8"));
resetArtifacts();

console.log("\n" + "=".repeat(78));
console.log("KICC INTEGRATION LAYER — END-TO-END ORDER-TO-CASH RUN");
console.log("=".repeat(78));
console.log(`MOCK_MODE           : ${env("MOCK_MODE", "true")}`);
console.log(`order               : ${order.order_ref}   lines: ${order.lines.length}   total: KES ${order.totals_kes.grand.toLocaleString()}`);
console.log(`buyer               : ${order.buyer.first} ${order.buyer.last}, ${order.buyer.town}, ${order.buyer.country}`);
console.log(`payment method      : ${order.buyer.payment}`);
console.log("-".repeat(78));

const result = await runOrder(order);

const label = (n) => n.padEnd(20);
console.log(`\nSTEP TRACE`);
console.log("-".repeat(78));
for (const e of readEvents().filter((e) => e.kind === "step")) {
  const d = { ...e }; delete d.kind; delete d.ts; delete d.step;
  console.log(`  ${label(e.step)} ${JSON.stringify(d)}`);
}

console.log(`\nRESULT`);
console.log("-".repeat(78));
console.log(`  payment        : ${result.payment.provider}  (${result.payment.lane})  ref ${result.payment.ref}  status ${result.payment.status}`);
console.log(`  escrow         : ${result.escrow.ref}  released on ${result.escrow.condition}`);
console.log(`  freight        : ${result.freight.carrier}  AWB ${result.freight.awb}  ${result.freight.transit_days} days  status ${result.freight.tracking}`);
console.log(`  carriers quoted: ${result.freight.quoted.map((q) => `${q.carrier} KES ${q.amountKes.toLocaleString()}/${q.transitDays}d`).join("   ")}`);
console.log(`  customs        : ${result.customs ? `${result.customs.declaration}  COO ${result.customs.coo}  KEBS ${result.customs.kebs}` : "not applicable on this lane"}`);
console.log(`  handling       : ${result.handling.notes.length ? result.handling.notes.join("; ") : "none"}`);
console.log(`  elapsed        : ${result.elapsed_ms} ms`);
console.log(`\n  DESK SETTLEMENTS`);
for (const s of result.settlements) {
  console.log(`    pipeline ${String(s.pipeline_id).padStart(2)}  ${s.pipeline_name.padEnd(42)} value KES ${String(s.value_kes.toLocaleString()).padStart(9)}  commission KES ${String(s.commission_kes.toLocaleString()).padStart(7)}  (${s.basis} ${s.rate})  payout ${s.payout_ref}`);
}
console.log(`    ${" ".repeat(11)}${"TOTAL COMMISSION".padEnd(42)} ${" ".repeat(16)} KES ${result.total_commission_kes.toLocaleString()}`);

console.log(`\nARTIFACTS`);
console.log("-".repeat(78));
console.log(`  events : ${readEvents().length} rows -> ${EVENTS_PATH}`);
console.log(`  ledger : ${readLedger().length} rows -> ${LEDGER_PATH}`);
console.log("=".repeat(78) + "\n");

fs.writeFileSync(path.join(ROOT, "run", "artifacts", "e2e-result.json"), JSON.stringify(result, null, 2));
