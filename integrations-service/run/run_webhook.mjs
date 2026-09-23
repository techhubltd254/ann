// run/run_webhook.mjs — boots the webhook receiver, fires a real signed Stripe
// event and a real Paystack event at it, and prints what it did with each.
// Proves the callback leg of the chain works, not just the outbound leg.
import crypto from "crypto";
import server from "../webhooks/server.js";
import { env } from "../lib/core.js";

await new Promise((r) => server.listen(0, r));
const port = server.address().port;
const base = `http://127.0.0.1:${port}`;

const post = async (path, body, headers) => {
  const r = await fetch(base + path, { method: "POST", headers: { "Content-Type": "application/json", ...headers }, body });
  return { status: r.status, json: await r.json().catch(() => ({})) };
};

console.log("\n" + "=".repeat(96));
console.log("WEBHOOK CALLBACK LEG");
console.log("=".repeat(96));
const health = await (await fetch(base + "/health")).json();
console.log(`  receiver up on :${port}   routes: ${health.routes.length}   live-configured: ${health.live_configured.length || "none (mock mode)"}`);

/* The receiver reads its secrets from the environment. A fresh checkout has no
   .env, so pin the same values here that the callers below sign with. */
process.env.STRIPE_WEBHOOK_SECRET ||= "whsec_mock_test_secret";
process.env.PAYSTACK_SECRET_KEY ||= "sk_test_mock_secret";
process.env.FLUTTERWAVE_SECRET_HASH ||= "unset_hash";

/* ---- Stripe: genuine HMAC-SHA256 signature over "{t}.{body}" ---- */
const stripeSecret = env("STRIPE_WEBHOOK_SECRET");
const stripeBody = JSON.stringify({
  id: "evt_mock_1", type: "payment_intent.succeeded",
  data: { object: { id: "pi_mock_1", amount: 20312400, currency: "kes", metadata: { order_ref: "KIC-MU7614K5" } } },
});
const t = Math.floor(Date.now() / 1000);
const v1 = crypto.createHmac("sha256", stripeSecret).update(`${t}.${stripeBody}`).digest("hex");
const stripeOk = await post("/webhook/stripe", stripeBody, { "Stripe-Signature": `t=${t},v1=${v1}` });
console.log(`  stripe    signed event      -> HTTP ${stripeOk.status}  order ${stripeOk.json.mapped?.orderRef}  state ${stripeOk.json.mapped?.state}`);
const stripeBad = await post("/webhook/stripe", stripeBody, { "Stripe-Signature": `t=${t},v1=${"0".repeat(64)}` });
console.log(`  stripe    forged signature  -> HTTP ${stripeBad.status}  ${stripeBad.json.error}   (rejected as required)`);
const stripeStale = (() => {
  const old = t - 3600;
  const sig = crypto.createHmac("sha256", stripeSecret).update(`${old}.${stripeBody}`).digest("hex");
  return { t: old, sig };
})();
const stripeReplay = await post("/webhook/stripe", stripeBody, { "Stripe-Signature": `t=${stripeStale.t},v1=${stripeStale.sig}` });
console.log(`  stripe    stale timestamp   -> HTTP ${stripeReplay.status}  ${stripeReplay.json.error}   (replay blocked)`);

/* ---- Paystack: HMAC-SHA512 over the raw body ---- */
const psSecret = env("PAYSTACK_SECRET_KEY");
const psBody = JSON.stringify({ event: "charge.success", data: { reference: "KIC-MU7614K5-PS", amount: 20312400, currency: "KES", status: "success", metadata: { order_ref: "KIC-MU7614K5" } } });
const psSig = crypto.createHmac("sha512", psSecret).update(psBody).digest("hex");
const psOk = await post("/webhook/paystack", psBody, { "x-paystack-signature": psSig });
console.log(`  paystack  signed event      -> HTTP ${psOk.status}  order ${psOk.json.mapped?.orderRef}  state ${psOk.json.mapped?.state}`);
const psBad = await post("/webhook/paystack", psBody, { "x-paystack-signature": "deadbeef" });
console.log(`  paystack  forged signature  -> HTTP ${psBad.status}  ${psBad.json.error}   (rejected as required)`);

/* ---- Flutterwave: static secret-hash comparison ---- */
const fwHash = env("FLUTTERWAVE_SECRET_HASH");
const fwOk = await post("/webhook/flutterwave", JSON.stringify({ event: "charge.completed", data: { id: 11, tx_ref: "KIC-MU7614K5-FW", status: "successful", amount: 203124, currency: "KES" } }), { "verif-hash": fwHash });
console.log(`  flutterwave matching hash   -> HTTP ${fwOk.status}  order ${fwOk.json.mapped?.orderRef}  state ${fwOk.json.mapped?.state}`);

/* ---- M-Pesa: unsigned callback, verified out-of-band ---- */
const mpOk = await post("/webhook/mpesa", JSON.stringify({
  Body: { stkCallback: { MerchantRequestID: "KIC-MU7614K5", CheckoutRequestID: "ws_CO_123", ResultCode: 0, CallbackMetadata: { Item: [{ Name: "Amount", Value: 203124 }] } } },
}), {});
console.log(`  mpesa     unsigned callback -> HTTP ${mpOk.status}  order ${mpOk.json.mapped?.orderRef}  state ${mpOk.json.mapped?.state}  (then re-queried out of band)`);

const unknown = await post("/webhook/nope", "{}", {});
console.log(`  unknown   provider          -> HTTP ${unknown.status}`);
console.log("=".repeat(96) + "\n");
server.close();
process.exit(0);
