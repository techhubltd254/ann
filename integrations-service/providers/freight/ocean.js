// providers/freight/ocean.js — sea freight, FCL + LCL container booking.
// Carriers: Maersk (Ocean Booking v2, built on the DCSA Booking Interface standard)
//           Hapag-Lloyd (booking / schedules / tracking APIs on api-portal.hlag.com)
//
// STATUS — PLACEHOLDER PATHS. No ocean-carrier resource path was read from the
// carrier's own reference page this session, so every path below is a placeholder.
// Set OCEAN_CARRIER, OCEAN_API_BASE and the OAuth vars, then run
//   node run/verify_endpoints.mjs --probe
// before any live booking. Everything works in mock mode today.
import { request, sign, env, flag } from "../../lib/core.js";

export default {
  id: "ocean",
  label: "Ocean freight (Maersk DCSA / Hapag-Lloyd)",
  kind: "freight",
  enabled_env: "OCEAN_ENABLED",
  envRequired: ["OCEAN_CARRIER", "OCEAN_API_BASE", "OCEAN_CLIENT_ID", "OCEAN_CLIENT_SECRET"],
  modes: ["sea"],
  coverage: ["global", "eu-uk", "mena-asia", "americas", "af"],
  docs: "https://developer.maersk.com/catalogue/EDP%20Booking · https://api-portal.hlag.com/",
  get base_url() { return env("OCEAN_API_BASE", "https://api.your-carrier.example"); },
  endpoints: {
    token: { method: "POST", path: "/oauth2/token", confirmed: false },
    quote: { method: "POST", path: "/v2/quotes", confirmed: false },
    booking: { method: "POST", path: "/v2/bookings", confirmed: false },
    label: { method: "GET", path: "/v2/bookings/{id}/shipping-instructions", confirmed: false },
    track: { method: "GET", path: "/v2/shipments/{awb}", confirmed: false },
  },
  get authHeaders() { return { Authorization: sign.bearer(env("OCEAN_ACCESS_TOKEN", "unset")) }; },

  async token() {
    if (String(env("MOCK_MODE", "true")) === "true") return "mock-token";
    const r = await request(this, "token", {
      method: "POST", path: env("OCEAN_TOKEN_PATH", "/oauth2/token"),
      body: {
        grant_type: "client_credentials",
        client_id: env("OCEAN_CLIENT_ID"), client_secret: env("OCEAN_CLIENT_SECRET"),
        consumer_key: env("OCEAN_CONSUMER_KEY"),
      },
    });
    return r?.access_token || "mock-token";
  },

  /** Container rate request. FCL is priced per container, LCL per cubic metre. */
  async quote(shipment) {
    this.authHeaders = { Authorization: sign.bearer(await this.token()) };
    const r = await request(this, "quote", {
      method: "POST", body: {
        carrier: env("OCEAN_CARRIER", "maersk"),
        transportPlan: { incoterm: shipment.incoterm || "FOB", vesselOperator: env("OCEAN_CARRIER", "maersk") },
        origin: {
          UNLocationCode: shipment.origin_unloc || "KEMBA",      // Mombasa
          cityName: shipment.origin_city || "Mombasa", countryCode: "KE",
        },
        destination: {
          UNLocationCode: shipment.destination_unloc || "NLRTM",
          cityName: shipment.destination_city, countryCode: shipment.destination_country,
        },
        serviceContractReference: env("OCEAN_CONTRACT_REF", ""),
        requestedEquipment: [{ isoEquipmentCode: shipment.container || "40HC", units: shipment.units || 1 }],
        cargoGrossWeight: shipment.weight_kg, cargoGrossVolume: shipment.volume_cbm,
        commodity: shipment.description,
        lcl: { lclIndicator: !!shipment.lcl, volumeCbm: shipment.volume_cbm, weightKg: shipment.weight_kg },
      },
    });
    return {
      ok: !r.error,
      amountKes: Math.round((r.amount || r.amountUsd || 0) * (r.currency === "KES" || !r.currency ? 1 : 129)),
      currency: r.currency || "KES",
      transitDays: r.transit_days || r.transitDays,
      service: r.service || `${shipment.container || "40HC"} ${shipment.lcl ? "LCL" : "FCL"}`,
      vessel: r.vessel, cutOff: r.cut_off, raw: r,
    };
  },

  /** Booking request -> carrier booking reference (DCSA booking confirmation). */
  async booking(shipment) {
    this.authHeaders = { Authorization: sign.bearer(await this.token()) };
    const r = await request(this, "booking", {
      method: "POST", body: {
        carrier: env("OCEAN_CARRIER", "maersk"),
        receiptTypeAtOrigin: "CY", deliveryTypeAtDestination: "CY",
        cargoMovementTypeAtOrigin: shipment.lcl ? "LCL" : "FCL",
        requestedEquipment: [{ isoEquipmentCode: shipment.container || "40HC", units: shipment.units || 1 }],
        commodity: shipment.description, isExportDeclarationRequired: true,
        vessels: [{ vesselName: shipment.vessel || "TBN" }],
        shipper: { partyName: "KICC Desk", address: { countryCode: "KE", cityName: "Nairobi" } },
        consignee: { partyName: shipment.consignee, address: { countryCode: shipment.destination_country, cityName: shipment.destination_city } },
      },
    });
    return { ok: !r.error, bookingRef: r.bookingRef || r.id, awb: r.billOfLading || r.id, status: r.status || "requested", raw: r };
  },

  /** Booking confirmation -> shipping instruction / draft bill of lading. */
  async createLabel(shipment) {
    this.authHeaders = { Authorization: sign.bearer(await this.token()) };
    const r = await request(this, "label", {
      method: "GET", body: { id: shipment.bookingRef || shipment.awb, awb: shipment.bookingRef || shipment.awb },
    });
    return { ok: !r.error, awb: r.billOfLading || shipment.awb, labelUrl: r.label_url, carrierRef: r.id, raw: r };
  },

  async track(awb) {
    this.authHeaders = { Authorization: sign.bearer(await this.token()) };
    const r = await request(this, "track", { method: "GET", body: { awb } });
    return { ok: !r.error, status: r.status, events: r.events || [], raw: r };
  },

  webhook: {
    header: "X-Carrier-Signature",
    scheme: "hmac_sha256",
    secret_env: "OCEAN_WEBHOOK_SECRET",
    // Not confirmed: carriers push container events (DCSA shipment events) to a
    // registered endpoint, but the signing header differs per carrier. Stub only.
    verify(rawBody, headers) { return sign.verifyHmac(rawBody, headers["x-carrier-signature"], env("OCEAN_WEBHOOK_SECRET")); },
  },
  mapTracking(body) {
    return { awb: body?.billOfLading || body?.transportDocumentReference, status: body?.status, events: body?.events || [] };
  },
  live: () => flag("OCEAN_ENABLED"),
};
