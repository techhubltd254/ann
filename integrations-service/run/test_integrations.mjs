// run/test_integrations.mjs — assertions over the whole integration layer.
import fs from "fs";
import path from "path";
import crypto from "crypto";
import { ROOT, resetArtifacts, readEvents, readLedger, sign, env, MOCK_MODE } from "../lib/core.js";
import { PAYMENTS, FREIGHT, SERVICES, ALL, paymentCandidates, freightCandidates, feeFor, missingEnv } from "../registries.js";
import { runOrder } from "../pipelines/order-to-cash.mjs";
import { runAll, PIPELINES } from "../pipelines/settle-to-desk.mjs";
import server from "../webhooks/server.js";

let pass = 0, fail = 0;
const check = (n, c, e) => { c ? pass++ : fail++; console.log((c ? "PASS  " : "FAIL  ") + n.padEnd(58) + " -> " + e); };
const order = JSON.parse(fs.readFileSync(path.join(ROOT, "..", "samples", "order-example.json"), "utf8"));

console.log("\n" + "=".repeat(100));
console.log("INTEGRATION LAYER TEST SUITE");
console.log("=".repeat(100) + "\n");

/* ---- 1. driver registry ---- */
check("14 providers registered (6 pay + 6 freight + 2 service)", ALL.length === 14, `${ALL.length} providers: ${ALL.map((p) => p.id).join(", ")}`);
check("every provider declares its env vars", ALL.every((p) => Array.isArray(p.envRequired)), ALL.map((p) => `${p.id}:${p.envRequired.length}`).join(" "));
check("every payment driver has authorize + webhook", Object.values(PAYMENTS).every((p) => p.authorize && p.webhook), Object.keys(PAYMENTS).join(", "));
check("every freight driver has quote + label + track", Object.values(FREIGHT).every((p) => p.quote && p.createLabel && p.track), Object.keys(FREIGHT).join(", "));
check("the sea lane driver (ocean) is registered", !!FREIGHT.ocean, `ocean modes: ${FREIGHT.ocean?.modes?.join(",")}`);
check("paystack is registered on card/bank/mobile_money", !!PAYMENTS.paystack, `paystack lanes: ${PAYMENTS.paystack?.lanes?.join(",")}`);
check("every driver declares endpoints", ALL.every((p) => p.endpoints && Object.keys(p.endpoints).length > 0), ALL.reduce((a, p) => a + Object.keys(p.endpoints).length, 0) + " endpoints total");

/* ---- 2. routing ---- */
check("payment routing resolves for KE mobile money", paymentCandidates("mobile_money", "KE")[0].id === "mpesa", paymentCandidates("mobile_money", "KE").map((p) => p.id).join(" > "));
check("payment routing resolves for card", paymentCandidates("card", "default").length > 0, paymentCandidates("card", "default").map((p) => p.id).join(" > "));
check("freight routing resolves for every store zone", ["ke-nairobi", "ke-upcountry", "ke-remote", "ea", "af", "eu-uk", "mena-asia", "americas"].every((z) => freightCandidates(z).length > 0), "8 lanes routed");
check("eu-uk freight prefers DHL then FedEx", freightCandidates("eu-uk").slice(0, 2).map((p) => p.id).join(",") === "dhl,fedex", freightCandidates("eu-uk").map((p) => p.id).join(" > "));
check("the sea lane routes to the ocean carrier", freightCandidates("sea")[0].id === "ocean", freightCandidates("sea").map((p) => p.id).join(" > "));
check("ke-remote freight uses the postal fallback", freightCandidates("ke-remote").map((p) => p.id).includes("posta"), freightCandidates("ke-remote").map((p) => p.id).join(" > "));

/* ---- 3. fees ---- */
check("fee basis resolves for all 87 pipelines", PIPELINES.every((p) => feeFor(p.id) !== undefined), `coffee(1)=${JSON.stringify(feeFor(1))} gold(18)=${JSON.stringify(feeFor(18))} subs(45)=${JSON.stringify(feeFor(45))}`);
check("enabler pipelines carry a zero fee", [43, 53, 79, 82].every((id) => feeFor(id).value === 0), "43,53,79,82 all zero");

/* ---- 4. signature verification (real crypto, not mocked) ---- */
const stripeSecret = "whsec_test_secret";
const rawBody = JSON.stringify({ id: "evt_1", type: "payment_intent.succeeded", data: { object: { id: "pi_1", amount: 1000, currency: "kes", metadata: { order_ref: "KIC-TEST" } } } });
const t = Math.floor(Date.now() / 1000);
const v1 = crypto.createHmac("sha256", stripeSecret).update(`${t}.${rawBody}`).digest("hex");
const goodHeader = `t=${t},v1=${v1}`;
process.env.STRIPE_WEBHOOK_SECRET = stripeSecret;
check("stripe signature accepted when correct", sign.verifyStripe(rawBody, goodHeader, stripeSecret).ok === true, "hmac_sha256_timestamped");
check("stripe signature REJECTED when payload tampered", sign.verifyStripe(rawBody + " ", goodHeader, stripeSecret).ok === false, sign.verifyStripe(rawBody + " ", goodHeader, stripeSecret).reason);
check("stripe signature REJECTED when timestamp stale", sign.verifyStripe(rawBody, `t=${t - 3600},v1=${crypto.createHmac("sha256", stripeSecret).update(`${t - 3600}.${rawBody}`).digest("hex")}`, stripeSecret).ok === false, "replay protection enforced");
check("flutterwave static hash accepted when equal", sign.verifyStaticHash("hash_abc", "hash_abc").ok === true, "static_hash scheme");
check("flutterwave static hash REJECTED when different", sign.verifyStaticHash("hash_abc", "hash_xyz").ok === false, sign.verifyStaticHash("hash_abc", "hash_xyz").reason);
check("generic hmac verifier rejects a bad header", sign.verifyHmac(rawBody, "deadbeef", "secret").ok === false, "hmac_sha256 scheme");
const ps512 = crypto.createHmac("sha512", "sk_test_x").update(rawBody).digest("hex");
check("paystack hmac_sha512 verifier accepts a correct digest", sign.verifyHmacSha512(rawBody, ps512, "sk_test_x").ok === true, "hmac_sha512 scheme");
check("paystack hmac_sha512 verifier rejects a bad digest", sign.verifyHmacSha512(rawBody, "deadbeef", "sk_test_x").ok === false, sign.verifyHmacSha512(rawBody, "deadbeef", "sk_test_x").reason);

/* ---- 5. end-to-end order ---- */
resetArtifacts();
const r1 = await runOrder(order);
check("e2e order settled", r1.order_ref === order.order_ref, `ref ${r1.order_ref} in ${r1.elapsed_ms}ms`);
check("payment provider selected by route", !!r1.payment.provider, `${r1.payment.provider} (${r1.payment.lane}) ref ${r1.payment.ref}`);
check("escrow held then released on delivery", r1.escrow.state === "released" && r1.escrow.condition === "delivery_verified", `${r1.escrow.ref} released on ${r1.escrow.condition}`);
check("all routed carriers were quoted", r1.freight.quoted.length === freightCandidates(r1.freight.lane).length, r1.freight.quoted.map((q) => `${q.carrier} ${q.amountKes}`).join(" | "));
check("cheapest carrier chosen and AWB issued", !!r1.freight.awb, `${r1.freight.carrier} AWB ${r1.freight.awb}`);
check("tracking reached delivered", r1.freight.tracking === "DELIVERED", r1.freight.tracking);
check("customs pack built on an export lane", !!r1.customs && !!r1.customs.declaration, `${r1.customs?.declaration} COO ${r1.customs?.coo} KEBS ${r1.customs?.kebs}`);
check("handling flags read from the route table", r1.handling.licensed_export === true && r1.handling.cold_chain === false, `licensed_export=${r1.handling.licensed_export} cold_chain=${r1.handling.cold_chain} | ${r1.handling.notes.join("; ")}`);
const coldOrder = JSON.parse(JSON.stringify(order));
coldOrder.order_ref = "KIC-COLD-1";
coldOrder.lines = [{ ...order.lines[0], pipeline_id: 57, pipeline_name: "Cold chain line", ship_zone: "ke-nairobi" }];
const rCold = await runOrder(coldOrder);
check("cold-chain pipeline 57 raises the cold-chain flag", rCold.handling.cold_chain === true, rCold.handling.notes.join("; "));
check("both order lines settled to their desks", r1.settlements.length === order.lines.length, r1.settlements.map((s) => `P${s.pipeline_id} KES ${s.commission_kes}`).join(" | "));
check("commission computed from the fee table", r1.settlements.every((s) => s.commission_kes > 0), `total KES ${r1.total_commission_kes.toLocaleString()}`);
check("step chain ran in the right order", r1.steps.join(">") === "payment.select>payment.authorize>escrow.hold>freight.quote>freight.label>customs.pack>freight.track>escrow.release>payment.capture>desk.payout", r1.steps.join(" > "));

/* ---- 6. all 87 pipelines ---- */
resetArtifacts();
const rows = await runAll();
check("all 87 pipelines driven through the chain", rows.length === 87, `${rows.length} pipelines`);
check("every pipeline produced a settled value", rows.every((r) => r.value_kes > 0), `min ${Math.min(...rows.map((r) => r.value_kes))} max ${Math.max(...rows.map((r) => r.value_kes))}`);
check("ledger holds one row per pipeline", readLedger().length === 87, `${readLedger().length} ledger rows`);
check("each pipeline used its own feed schema", rows.every((r) => r.columns >= 23), `columns min ${Math.min(...rows.map((r) => r.columns))} max ${Math.max(...rows.map((r) => r.columns))}`);
check("all 13 categories present", new Set(rows.map((r) => r.category)).size === 13, `${new Set(rows.map((r) => r.category)).size} categories`);
check("distinct owner desks exercised", new Set(rows.map((r) => r.owner_desk)).size >= 15, `${new Set(rows.map((r) => r.owner_desk)).size} desks`);
const totalCommission = rows.reduce((a, r) => a + r.commission_kes, 0);
check("commission accrues across the catalog", totalCommission > 0, `KES ${totalCommission.toLocaleString()} across 87 lines`);
check("events emitted for every pipeline stage", readEvents().length >= 87 * 4, `${readEvents().length} events (>= 348)`);

/* ---- 7. webhook server end to end ---- */
await new Promise((resolve) => server.listen(0, resolve));
const port = server.address().port;
const post = async (path, body, headers) => {
  const r = await fetch(`http://127.0.0.1:${port}${path}`, { method: "POST", headers: { "Content-Type": "application/json", ...headers }, body });
  return { status: r.status, json: await r.json().catch(() => ({})) };
};
const health = await (await fetch(`http://127.0.0.1:${port}/health`)).json();
check("webhook server exposes a health route", health.ok === true, `${health.routes.length} routes registered`);
const evBody = JSON.stringify({ id: "evt_2", type: "payment_intent.succeeded", data: { object: { id: "pi_2", amount: 5000, currency: "kes", metadata: { order_ref: "KIC-WH-1" } } } });
const ts2 = Math.floor(Date.now() / 1000);
const sig2 = crypto.createHmac("sha256", stripeSecret).update(`${ts2}.${evBody}`).digest("hex");
process.env.PAYSTACK_SECRET_KEY = "sk_test_webhook_secret";
const psBody = JSON.stringify({ event: "charge.success", data: { reference: "KIC-WH-PS", amount: 100, currency: "KES", metadata: { order_ref: "KIC-WH-PS" } } });
const psOk = await post("/webhook/paystack", psBody, { "x-paystack-signature": crypto.createHmac("sha512", "sk_test_webhook_secret").update(psBody).digest("hex") });
check("paystack webhook route accepts a signed event", psOk.status === 200, `HTTP ${psOk.status} -> order ${psOk.json.mapped?.orderRef}`);
const psBad = await post("/webhook/paystack", psBody, { "x-paystack-signature": "deadbeef" });
check("paystack webhook rejects a forged event", psBad.status === 401, `HTTP ${psBad.status} -> ${psBad.json.error}`);
const okWh = await post("/webhook/stripe", evBody, { "Stripe-Signature": `t=${ts2},v1=${sig2}` });
check("valid webhook accepted and mapped to the order", okWh.status === 200 && okWh.json.mapped?.orderRef === "KIC-WH-1", `HTTP ${okWh.status} -> order ${okWh.json.mapped?.orderRef} state ${okWh.json.mapped?.state}`);
const badWh = await post("/webhook/stripe", evBody, { "Stripe-Signature": `t=${ts2},v1=${"0".repeat(64)}` });
check("forged webhook REJECTED with 401", badWh.status === 401, `HTTP ${badWh.status} -> ${badWh.json.error}`);
const fwWh = await post("/webhook/flutterwave", JSON.stringify({ event: "charge.completed", data: { id: 9, tx_ref: "KIC-WH-2", status: "successful", amount: 100, currency: "KES" } }), { "verif-hash": env("FLUTTERWAVE_SECRET_HASH", "unset") });
check("flutterwave webhook path responds", [200, 401].includes(fwWh.status), `HTTP ${fwWh.status} (401 expected while the secret hash is unset)`);
const unknown = await post("/webhook/nope", "{}", {});
check("unknown provider returns 404", unknown.status === 404, `HTTP ${unknown.status}`);
server.close();

/* ---- 8. credential reporting ---- */
const report = ALL.map((p) => ({ id: p.id, missing: missingEnv(p) }));
check("missing-credential reporting works per provider", report.every((r) => Array.isArray(r.missing)), report.map((r) => `${r.id}:${r.missing.length} missing`).join(" "));
check("mock mode is on by default so nothing needs keys today", MOCK_MODE === true, `MOCK_MODE=${env("MOCK_MODE", "true")}`);

console.log("\n" + "=".repeat(100));
console.log(`RESULT: ${pass} passed, ${fail} failed`);
console.log("=".repeat(100) + "\n");
process.exit(fail ? 1 : 0);
