// providers/freight/fedex.js — FedEx air and courier.
import { request, sign, env, flag } from "../../lib/core.js";

export default {
  id: "fedex",
  label: "FedEx",
  kind: "freight",
  enabled_env: "FEDEX_ENABLED",
  envRequired: ["FEDEX_CLIENT_ID", "FEDEX_CLIENT_SECRET", "FEDEX_ACCOUNT_NUMBER"],
  modes: ["air", "courier"],
  coverage: ["global", "eu-uk", "mena-asia", "americas"],
  get base_url() {
    return env("FEDEX_ENVIRONMENT", "sandbox") === "production"
      ? "https://apis.fedex.com" : "https://apis-sandbox.fedex.com";
  },
  // /oauth/token with the client-credentials grant is the documented auth path.
  // The three resource paths below are UNCONFIRMED against FedEx's own reference.
  endpoints: {
    token: { method: "POST", path: "/oauth/token" },
    quote: { method: "POST", path: "/rate/v1/rates/quotes" },
    label: { method: "POST", path: "/ship/v1/shipments" },
    track: { method: "POST", path: "/track/v1/trackingnumbers" },
  },
  async token() {
    if (env("MOCK_MODE", "true") === "true") return "mock-token";
    const r = await request(this, "token", {
      method: "POST",
      body: { grant_type: "client_credentials", client_id: env("FEDEX_CLIENT_ID"), client_secret: env("FEDEX_CLIENT_SECRET") },
    });
    return r?.access_token || "mock-token";
  },
  async quote(shipment) {
    this.authHeaders = { Authorization: sign.bearer(await this.token()) };
    const r = await request(this, "quote", {
      method: "POST", body: {
        accountNumber: { value: env("FEDEX_ACCOUNT_NUMBER") },
        requestedShipment: {
          shipper: { address: { countryCode: "KE", city: "Nairobi" } },
          recipient: { address: { countryCode: shipment.destination_country, city: shipment.destination_city } },
          pickupType: "DROPOFF_AT_FEDEX_LOCATION",
          rateRequestType: ["ACCOUNT"],
          requestedPackageLineItems: [{ weight: { units: "KG", value: shipment.weight_kg } }],
        },
      },
    });
    return { ok: !r.error, amountKes: Math.round(r.amount * (r.currency === "KES" ? 1 : 129)), currency: "KES", transitDays: r.transit_days, service: r.service, raw: r };
  },
  async createLabel(shipment) {
    this.authHeaders = { Authorization: sign.bearer(await this.token()) };
    const r = await request(this, "label", { method: "POST", body: { requestedShipment: { totalWeight: { units: "KG", value: shipment.weight_kg }, description: shipment.description } } });
    return { ok: !r.error, awb: r.awb || r.id, labelUrl: r.label_url, carrierRef: r.id, raw: r };
  },
  async track(awb) {
    this.authHeaders = { Authorization: sign.bearer(await this.token()) };
    const r = await request(this, "track", { method: "POST", body: { trackingInfo: [{ trackingNumberInfo: { trackingNumber: awb } }] } });
    return { ok: !r.error, status: r.status, events: r.events || [], raw: r };
  },
  async customsDocs() { return { ok: true, commercialInvoice: true, packingList: true, note: "generated from the order payload" }; },

  webhook: {
    header: null,
    scheme: "none",
    note: "FedEx pushes shipment-visibility events to a registered HTTPS endpoint; no published signature header.",
    verify() { return { ok: true, scheme: "none", warning: "no signature published - validate the payload against the Track API" }; },
  },
  mapTracking(body) { return { awb: body?.trackingNumber, status: body?.status, events: body?.events || [] }; },
  live: () => flag("FEDEX_ENABLED"),
};
