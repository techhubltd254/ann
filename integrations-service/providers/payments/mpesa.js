// providers/payments/mpesa.js — Safaricom M-Pesa STK Push, Kenya.
import { request, env, flag, sign } from "../../lib/core.js";

function baseUrl() {
  return env("MPESA_ENVIRONMENT", "sandbox") === "production"
    ? "https://api.safaricom.co.ke" : "https://sandbox.safaricom.co.ke";
}
function timestamp() {
  const d = new Date(), p = (n) => String(n).padStart(2, "0");
  return `${d.getFullYear()}${p(d.getMonth() + 1)}${p(d.getDate())}${p(d.getHours())}${p(d.getMinutes())}${p(d.getSeconds())}`;
}

export default {
  id: "mpesa",
  label: "Safaricom M-Pesa (Daraja)",
  kind: "payment",
  enabled_env: "MPESA_ENABLED",
  envRequired: ["MPESA_CONSUMER_KEY", "MPESA_CONSUMER_SECRET", "MPESA_SHORTCODE", "MPESA_PASSKEY", "MPESA_CALLBACK_URL"],
  lanes: ["mobile_money"],
  get base_url() { return baseUrl(); },
  // OAuth 2.0 bearer confirmed by third-party Daraja guides; exact path names
  // UNCONFIRMED against Safaricom's own reference page (it renders client-side).
  endpoints: {
    token: { method: "GET", path: "/oauth/v1/generate?grant_type=client_credentials" },
    authorize: { method: "POST", path: "/mpesa/stkpush/v1/processrequest" },
    verify: { method: "POST", path: "/mpesa/stkpushquery/v1/query" },
  },
  async token() {
    if (env("MOCK_MODE", "true") === "true") return "mock-token";
    const r = await request(this, "token", { method: "GET", path: this.endpoints.token.path });
    return r?.access_token || "mock-token";
  },
  async authorize({ order, amount }) {
    const ts = timestamp();
    const password = Buffer.from(`${env("MPESA_SHORTCODE")}${env("MPESA_PASSKEY")}${ts}`).toString("base64");
    this.authHeaders = { Authorization: sign.bearer(await this.token()) };
    const r = await request(this, "authorize", {
      method: "POST", body: {
        BusinessShortCode: env("MPESA_SHORTCODE"), Password: password, Timestamp: ts,
        TransactionType: "CustomerPayBillOnline",
        Amount: Math.round(amount),
        PartyA: String(order.buyer.phone || "").replace(/\D/g, ""),
        PartyB: env("MPESA_SHORTCODE"),
        PhoneNumber: String(order.buyer.phone || "").replace(/\D/g, ""),
        CallBackURL: env("MPESA_CALLBACK_URL"),
        AccountReference: order.order_ref, TransactionDesc: `KICC order ${order.order_ref}`,
      },
    });
    return {
      ok: !r.error, providerRef: r?.CheckoutRequestID || r.id,
      status: r?.ResponseCode === "0" ? "pending_stk" : (r.status || "pending_stk"), raw: r,
    };
  },
  async capture(ref) { return { ok: true, status: "settled", note: "M-Pesa settles directly; nothing to capture" }; },
  async refund(ref, amount) { return request(this, "refund", { method: "POST", body: { id: ref, amount } }); },
  async status(ref) {
    this.authHeaders = { Authorization: sign.bearer(await this.token()) };
    return request(this, "verify", { method: "POST", body: { CheckoutRequestID: ref } });
  },

  webhook: {
    header: null,
    scheme: "none",
    // Daraja posts an unsigned asynchronous callback. Do not trust it on its own:
    // confirm by calling the STK query endpoint before releasing funds.
    verify() { return { ok: true, scheme: "none", warning: "unsigned callback - confirm via STK query before releasing funds" }; },
  },
  mapEvent(body) {
    const cb = body?.Body?.stkCallback || {};
    const items = Object.fromEntries((cb.CallbackMetadata?.Item || []).map((i) => [i.Name, i.Value]));
    return {
      orderRef: cb.MerchantRequestID || cb.CheckoutRequestID,
      state: cb.ResultCode === 0 ? "paid" : "failed",
      amount: items.Amount, currency: "KES", providerRef: cb.CheckoutRequestID,
    };
  },
  live: () => flag("MPESA_ENABLED"),
};
