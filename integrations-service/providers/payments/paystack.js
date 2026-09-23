// providers/payments/paystack.js — card, bank and mobile money, Africa + Kenya.
import { request, sign, env, flag } from "../../lib/core.js";

export default {
  id: "paystack",
  label: "Paystack",
  kind: "payment",
  enabled_env: "PAYSTACK_ENABLED",
  envRequired: ["PAYSTACK_SECRET_KEY", "PAYSTACK_PUBLIC_KEY"],
  lanes: ["card", "bank", "mobile_money"],
  coverage: ["KE", "NG", "GH", "ZA"],
  base_url: "https://api.paystack.co",
  // VERIFIED against paystack.com/docs/api/transaction this session:
  //   POST https://api.paystack.co/transaction/initialize      Authorization: Bearer SECRET_KEY
  //   GET  https://api.paystack.co/transaction/verify/{reference}
  endpoints: {
    authorize: { method: "POST", path: "/transaction/initialize", confirmed: true },
    verify: { method: "GET", path: "/transaction/verify/{reference}", confirmed: true },
    refund: { method: "POST", path: "/refund", confirmed: false },
  },
  get authHeaders() { return { Authorization: sign.bearer(env("PAYSTACK_SECRET_KEY")) }; },

  async authorize({ order, amount, currency, idempotencyKey }) {
    const r = await request(this, "authorize", {
      method: "POST", idempotencyKey,
      body: {
        email: order.buyer?.email || `buyer+${order.order_ref}@kicc.example`,
        amount: Math.round(Number(amount) * 100),          // Paystack takes the smallest unit (kobo/cents)
        currency: String(currency || "KES").toUpperCase(),
        reference: idempotencyKey || order.order_ref,
        callback_url: `${env("PLATFORM_BASE_URL", "https://api.your-domain.example")}/checkout/return`,
        metadata: { order_ref: order.order_ref, pipelines: order.lines.map((l) => l.pipeline_id).join(",") },
      },
    });
    return {
      ok: !r.error, providerRef: r?.data?.reference || r.id,
      status: r?.data?.status || "pending",
      authorizationUrl: r?.data?.authorization_url, raw: r,
    };
  },
  async capture(ref) {
    return { ok: true, status: "settled", note: "Paystack settles on the customer's authorisation; nothing to capture" };
  },
  async refund(ref, amount) {
    return request(this, "refund", { method: "POST", body: { transaction: ref, amount: Math.round(Number(amount) * 100) } });
  },
  async status(ref) { return request(this, "verify", { method: "GET", body: { reference: ref } }); },

  webhook: {
    header: "x-paystack-signature",
    scheme: "hmac_sha512",
    secret_env: "PAYSTACK_SECRET_KEY",
    // Documented as HMAC-SHA512 of the raw request body keyed with the secret key.
    // NOT re-read from Paystack's own page this session -> the verifier below is a
    // working stub: confirm the digest + header name before going live.
    verify(rawBody, headers) {
      return sign.verifyHmacSha512(rawBody, headers["x-paystack-signature"], env("PAYSTACK_SECRET_KEY"));
    },
  },
  mapEvent(body) {
    const e = String(body?.event || "");
    return {
      orderRef: body?.data?.metadata?.order_ref,
      state: e === "charge.success" ? "paid" : e.startsWith("refund") ? "refunded" : "update",
      amount: body?.data?.amount ? body.data.amount / 100 : undefined,
      currency: body?.data?.currency, providerRef: body?.data?.reference,
    };
  },
  live: () => flag("PAYSTACK_ENABLED"),
};
