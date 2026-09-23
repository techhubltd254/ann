// providers/freight/posta.js — Postal Corporation of Kenya, remote and ASAL counties.
import { request, env, flag } from "../../lib/core.js";

export default {
  id: "posta",
  label: "Postal Corporation of Kenya",
  kind: "freight",
  enabled_env: "POSTA_ENABLED",
  envRequired: ["POSTA_API_KEY", "POSTA_API_BASE"],
  modes: ["road"],
  coverage: ["ke"],
  get base_url() { return env("POSTA_API_BASE", "https://api.posta.example"); },
  endpoints: {
    quote: { method: "POST", path: "/v1/rates" },
    label: { method: "POST", path: "/v1/parcels" },
    track: { method: "GET", path: "/v1/parcels/{awb}" },
  },
  get authHeaders() { return { "X-API-Key": env("POSTA_API_KEY") }; },

  async quote(shipment) {
    const r = await request(this, "quote", {
      method: "POST", body: {
        destination_county: shipment.destination_county, weight_kg: shipment.weight_kg,
        service: shipment.express ? "EMS" : "PARCEL", insured_value_kes: shipment.insured_value_kes,
      },
    });
    return { ok: !r.error, amountKes: r.amount, currency: "KES", transitDays: r.transit_days, service: r.service || "PARCEL", raw: r };
  },
  async createLabel(shipment) {
    const r = await request(this, "label", { method: "POST", body: { order_ref: shipment.order_ref, destination_county: shipment.destination_county, weight_kg: shipment.weight_kg } });
    return { ok: !r.error, awb: r.awb || r.id, labelUrl: r.label_url, carrierRef: r.id, raw: r };
  },
  async track(awb) {
    const r = await request(this, "track", { method: "GET", body: { awb } });
    return { ok: !r.error, status: r.status, events: r.events || [], raw: r };
  },
  async customsDocs() { return { ok: true, note: "domestic lane - no customs documents required" }; },
  webhook: { header: null, scheme: "none", verify() { return { ok: true, scheme: "none", warning: "poll the tracking endpoint" }; } },
  mapTracking(body) { return { awb: body?.parcel_number, status: body?.status, events: body?.events || [] }; },
  live: () => flag("POSTA_ENABLED"),
};
