// providers/payments/bank.js — bank transfer and the escrow account (pipeline 58).
import { request, sign, env, flag } from "../../lib/core.js";

export default {
  id: "bank",
  label: "Bank / escrow settlement",
  kind: "payment",
  enabled_env: "BANK_ENABLED",
  envRequired: ["BANK_NAME", "BANK_API_BASE", "BANK_CLIENT_ID", "BANK_CLIENT_SECRET", "BANK_ACCOUNT_NUMBER", "BANK_ESCROW_ACCOUNT_REF", "BANK_WEBHOOK_SECRET"],
  lanes: ["bank"],
  get base_url() { return env("BANK_API_BASE", "https://api.your-bank.example"); },
  endpoints: {
    hold: { method: "POST", path: "/escrow/holds" },
    release: { method: "POST", path: "/escrow/holds/{id}/release" },
    payout: { method: "POST", path: "/payments/payouts" },
  },
  async authorize({ order, amount, currency }) {
    const r = await request(this, "hold", {
      method: "POST", body: {
        escrow_account: env("BANK_ESCROW_ACCOUNT_REF"),
        collection_account: env("BANK_COLLECTION_ACCOUNT"),
        reference: order.order_ref, amount, currency,
        release_condition: "delivery_verified",
        split: order.lines.map((l) => ({ pipeline_id: l.pipeline_id, amount: l.unit_kes * l.qty })),
      },
    });
    return { ok: !r.error, providerRef: r.id, status: r.status, raw: r };
  },
  async capture(ref) { return request(this, "release", { method: "POST", body: { id: ref } }); },
  async refund(ref, amount) { return request(this, "release", { method: "POST", body: { id: ref, reverse: true, amount } }); },
  async status(ref) { return request(this, "release", { method: "POST", body: { id: ref, probe: true } }); },

  /** Pay a pipeline desk its commission once the buyer's funds have released. */
  async payoutDesk({ pipelineId, pipelineName, amount, currency, orderRef }) {
    return request(this, "payout", {
      method: "POST", body: {
        from: env("BANK_ESCROW_ACCOUNT_REF"), to_pipeline: pipelineId, to_desk: pipelineName,
        amount, currency, reference: `${orderRef}-P${pipelineId}`, narration: `Commission pipeline ${pipelineId}`,
      },
    });
  },

  webhook: {
    header: "X-Bank-Signature",
    scheme: "hmac_sha256",
    secret_env: "BANK_WEBHOOK_SECRET",
    verify(rawBody, headers) { return sign.verifyHmac(rawBody, headers["x-bank-signature"], env("BANK_WEBHOOK_SECRET")); },
  },
  mapEvent(body) {
    return { orderRef: body?.reference, state: body?.status === "released" ? "paid" : "update", amount: body?.amount, currency: body?.currency, providerRef: body?.id };
  },
  live: () => flag("BANK_ENABLED"),
};
