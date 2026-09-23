// providers/payments/pesapal.js — card + mobile money, East Africa.
import { request, sign, env, flag } from "../../lib/core.js";

export default {
  id: "pesapal",
  label: "Pesapal",
  kind: "payment",
  enabled_env: "PESAPAL_ENABLED",
  envRequired: ["PESAPAL_CONSUMER_KEY", "PESAPAL_CONSUMER_SECRET", "PESAPAL_IPN_URL"],
  lanes: ["card", "mobile_money"],
  get base_url() {
    return env("PESAPAL_ENVIRONMENT", "sandbox") === "production"
      ? "https://pay.pesapal.com/v3" : "https://cybqa.pesapal.com/pesapalv3";
  },
  endpoints: {
    token: { method: "POST", path: "/api/Auth/RequestToken" },
    ipn: { method: "POST", path: "/api/URLSetup/RegisterIPN" },
    authorize: { method: "POST", path: "/api/Transactions/SubmitOrderRequest" },
    verify: { method: "GET", path: "/api/Transactions/GetTransactionStatus" },
  },
  async token() {
    if (env("MOCK_MODE", "true") === "true") return "mock-token";
    const r = await request(this, "token", { method: "POST", body: { consumer_key: env("PESAPAL_CONSUMER_KEY"), consumer_secret: env("PESAPAL_CONSUMER_SECRET") } });
    return r?.token || "mock-token";
  },
  async registerIpn() {
    this.authHeaders = { Authorization: sign.bearer(await this.token()) };
    return request(this, "ipn", { method: "POST", body: { url: env("PESAPAL_IPN_URL"), ipn_notification_type: "POST" } });
  },
  async authorize({ order, amount, currency }) {
    this.authHeaders = { Authorization: sign.bearer(await this.token()) };
    const r = await request(this, "authorize", {
      method: "POST", body: {
        id: order.order_ref, currency, amount,
        description: `KICC order ${order.order_ref}`,
        callback_url: `${env("PLATFORM_BASE_URL")}/checkout/return`,
        notification_id: env("PESAPAL_IPN_ID", "REPLACE_ME"),
        billing_address: {
          email_address: order.buyer.email, phone_number: order.buyer.phone,
          country_code: order.buyer.country, first_name: order.buyer.first, last_name: order.buyer.last,
        },
      },
    });
    return { ok: !r.error, providerRef: r?.order_tracking_id || r.id, status: r?.status || "pending", raw: r };
  },
  async capture(ref) { return request(this, "verify", { method: "GET", body: { orderTrackingId: ref } }); },
  async refund() { return { ok: false, error: "Pesapal refunds are handled in the merchant dashboard" }; },
  async status(ref) { return request(this, "verify", { method: "GET", body: { orderTrackingId: ref } }); },

  webhook: {
    header: null,
    scheme: "none",
    // Pesapal's IPN is unsigned; confirm by calling GetTransactionStatus.
    verify() { return { ok: true, scheme: "none", warning: "unsigned IPN - confirm via GetTransactionStatus" }; },
  },
  mapEvent(body) {
    return {
      orderRef: body?.OrderTrackingId || body?.order_tracking_id,
      state: String(body?.PaymentStatusDescription || "").toLowerCase().includes("completed") ? "paid" : "update",
      amount: body?.Amount, currency: body?.Currency, providerRef: body?.OrderTrackingId,
    };
  },
  live: () => flag("PESAPAL_ENABLED"),
};
