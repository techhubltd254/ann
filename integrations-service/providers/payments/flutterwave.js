// providers/payments/flutterwave.js — card + mobile money across Africa.
import { request, sign, env, flag } from "../../lib/core.js";

export default {
  id: "flutterwave",
  label: "Flutterwave",
  kind: "payment",
  enabled_env: "FLUTTERWAVE_ENABLED",
  envRequired: ["FLUTTERWAVE_SECRET_KEY", "FLUTTERWAVE_PUBLIC_KEY", "FLUTTERWAVE_SECRET_HASH"],
  lanes: ["card", "mobile_money", "bank"],
  base_url: "https://api.flutterwave.com/v3",
  endpoints: {
    authorize: { method: "POST", path: "/payments" },
    verify: { method: "GET", path: "/transactions/{id}/verify" },
    refund: { method: "POST", path: "/transactions/{id}/refund" },
  },
  get authHeaders() { return { Authorization: sign.bearer(env("FLUTTERWAVE_SECRET_KEY")) }; },

  async authorize({ order, amount, currency }) {
    const r = await request(this, "authorize", {
      method: "POST", body: {
        tx_ref: order.order_ref, amount, currency,
        redirect_url: `${env("PLATFORM_BASE_URL")}/checkout/return`,
        customer: { email: order.buyer.email, phonenumber: order.buyer.phone, name: `${order.buyer.first} ${order.buyer.last}` },
        customizations: { title: "KICC Market", description: `Order ${order.order_ref}` },
      },
    });
    return { ok: !r.error, providerRef: r?.data?.id || r.id, status: r?.data?.status || r.status, raw: r };
  },
  async capture(ref) { return request(this, "verify", { method: "GET", body: { id: ref } }); },
  async refund(ref, amount) { return request(this, "refund", { method: "POST", body: { id: ref, amount } }); },
  async status(ref) { return request(this, "verify", { method: "GET", body: { id: ref } }); },

  webhook: {
    header: "verif-hash",
    scheme: "static_hash",
    secret_env: "FLUTTERWAVE_SECRET_HASH",
    // Verified: Flutterwave includes your configured secret hash in a `verif-hash`
    // header; you compare it directly. No HMAC is used on the payload.
    verify(rawBody, headers) {
      return sign.verifyStaticHash(headers["verif-hash"], env("FLUTTERWAVE_SECRET_HASH"));
    },
  },
  mapEvent(body) {
    const d = body?.data || {};
    const state = body?.event === "charge.completed" && d.status === "successful" ? "paid"
      : body?.event === "charge.completed" ? "failed" : "update";
    return { orderRef: d.tx_ref, state, amount: d.amount, currency: d.currency, providerRef: String(d.id ?? "") };
  },
  live: () => flag("FLUTTERWAVE_ENABLED"),
};
