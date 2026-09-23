// providers/payments/stripe.js — card and wallet, global.
import { request, sign, env, flag } from "../../lib/core.js";

export default {
  id: "stripe",
  label: "Stripe",
  kind: "payment",
  enabled_env: "STRIPE_ENABLED",
  envRequired: ["STRIPE_SECRET_KEY", "STRIPE_PUBLISHABLE_KEY", "STRIPE_WEBHOOK_SECRET"],
  lanes: ["card", "wallet"],
  base_url: "https://api.stripe.com",
  // Verified: POST /v1/payment_intents ; HTTP Basic with the secret key as username.
  endpoints: {
    authorize: { method: "POST", path: "/v1/payment_intents" },
    capture: { method: "POST", path: "/v1/payment_intents/{id}/capture" },
    refund: { method: "POST", path: "/v1/refunds" },
    verify: { method: "GET", path: "/v1/payment_intents/{id}" },
  },
  get authHeaders() { return { Authorization: sign.basic(env("STRIPE_SECRET_KEY"), "") }; },

  async authorize({ order, amount, currency, idempotencyKey }) {
    const r = await request(this, "authorize", {
      method: "POST", body: {
        amount: Math.round(amount), currency: currency.toLowerCase(),
        capture_method: "manual",                 // authorise now, capture on verified delivery
        metadata: { order_ref: order.order_ref, pipelines: order.lines.map((l) => l.pipeline_id).join(",") },
      }, idempotencyKey,
    });
    return { ok: r.status !== undefined && !r.error, providerRef: r.id, status: r.status, raw: r };
  },
  async capture(ref) { return request(this, "capture", { method: "POST", body: { id: ref } }); },
  async refund(ref, amount) { return request(this, "refund", { method: "POST", body: { payment_intent: ref, amount: Math.round(amount) } }); },
  async status(ref) { return request(this, "verify", { method: "GET", body: { id: ref } }); },

  webhook: {
    header: "Stripe-Signature",
    scheme: "hmac_sha256_timestamped",
    secret_env: "STRIPE_WEBHOOK_SECRET",
    // Verified: verify with the raw payload, the Stripe-Signature header and the
    // endpoint secret; Stripe's own libraries use a 5-minute tolerance.
    verify(rawBody, headers) {
      return sign.verifyStripe(rawBody, headers["stripe-signature"], env("STRIPE_WEBHOOK_SECRET"), 300);
    },
  },
  mapEvent(body) {
    const o = body?.data?.object || {};
    const state = body?.type === "payment_intent.succeeded" ? "paid"
      : body?.type === "payment_intent.amount_capturable_updated" ? "authorized"
        : body?.type === "payment_intent.payment_failed" ? "failed" : "update";
    return { orderRef: o.metadata?.order_ref, state, amount: o.amount, currency: o.currency, providerRef: o.id };
  },
  live: () => flag("STRIPE_ENABLED"),
};
