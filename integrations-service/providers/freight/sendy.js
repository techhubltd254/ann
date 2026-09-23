// providers/freight/sendy.js — Sendy, domestic Kenya road.
import { request, env, flag } from "../../lib/core.js";

export default {
  id: "sendy",
  label: "Sendy",
  kind: "freight",
  enabled_env: "SENDY_ENABLED",
  envRequired: ["SENDY_API_KEY"],
  modes: ["road"],
  coverage: ["ke"],
  get base_url() { return env("SENDY_API_BASE", "https://api.sendyit.com/v1"); },
  // UNCONFIRMED: Sendy's docs site could not be retrieved this session.
  endpoints: {
    quote: { method: "POST", path: "/api/v1/price" },
    label: { method: "POST", path: "/api/v1/deliveries" },
    track: { method: "GET", path: "/api/v1/deliveries/{awb}" },
  },
  get authHeaders() { return { "X-Api-Key": env("SENDY_API_KEY") }; },

  async quote(shipment) {
    const r = await request(this, "quote", {
      method: "POST", body: {
        pickup: { name: "KICC", latitude: shipment.origin_lat, longitude: shipment.origin_lng },
        delivery: { name: shipment.consignee, latitude: shipment.destination_lat, longitude: shipment.destination_lng },
        weight_kg: shipment.weight_kg, cargo_type: shipment.cold_chain ? "perishable" : "general",
      },
    });
    return { ok: !r.error, amountKes: r.amount, currency: "KES", transitDays: r.transit_days, service: r.service, raw: r };
  },
  async createLabel(shipment) {
    const r = await request(this, "label", { method: "POST", body: { order_ref: shipment.order_ref, weight_kg: shipment.weight_kg, destination_city: shipment.destination_city } });
    return { ok: !r.error, awb: r.awb || r.id, labelUrl: r.label_url, carrierRef: r.id, raw: r };
  },
  async track(awb) {
    const r = await request(this, "track", { method: "GET", body: { awb } });
    return { ok: !r.error, status: r.status, events: r.events || [], raw: r };
  },
  async customsDocs() { return { ok: true, note: "domestic lane - no customs documents required" }; },
  webhook: { header: null, scheme: "none", verify() { return { ok: true, scheme: "none", warning: "poll the tracking endpoint" }; } },
  mapTracking(body) { return { awb: body?.tracking_number, status: body?.status, events: body?.events || [] }; },
  live: () => flag("SENDY_ENABLED"),
};
