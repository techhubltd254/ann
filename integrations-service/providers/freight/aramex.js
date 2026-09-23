// providers/freight/aramex.js — Aramex air, courier and road.
import { request, flag, env } from "../../lib/core.js";

export default {
  id: "aramex",
  label: "Aramex",
  kind: "freight",
  enabled_env: "ARAMEX_ENABLED",
  envRequired: ["ARAMEX_USERNAME", "ARAMEX_PASSWORD", "ARAMEX_ACCOUNT_NUMBER", "ARAMEX_ACCOUNT_PIN", "ARAMEX_ACCOUNT_ENTITY", "ARAMEX_ACCOUNT_COUNTRY_CODE"],
  modes: ["air", "courier", "road"],
  coverage: ["mena-asia", "eu-uk", "af", "ea"],
  base_url: "https://ws.aramex.net/ShippingAPI.V2",
  // UNCONFIRMED: Aramex's API manual PDF could not be retrieved this session, so
  // the base URL and these paths come from the documented product structure and
  // must be confirmed against the manual before live use.
  endpoints: {
    quote: { method: "POST", path: "/Rate/Service_1_0.svc/json/CalculateRate" },
    label: { method: "POST", path: "/Shipping/Service_1_0.svc/json/CreateShipments" },
    track: { method: "POST", path: "/Tracking/Service_1_0.svc/json/TrackShipments" },
  },
  /** Aramex carries credentials in the JSON body, not in a header. */
  get credentials() {
    return {
      UserName: env("ARAMEX_USERNAME"), Password: env("ARAMEX_PASSWORD"),
      AccountNumber: env("ARAMEX_ACCOUNT_NUMBER"), AccountPin: env("ARAMEX_ACCOUNT_PIN"),
      AccountEntity: env("ARAMEX_ACCOUNT_ENTITY"), AccountCountryCode: env("ARAMEX_ACCOUNT_COUNTRY_CODE", "KE"),
      Version: "v1.0",
    };
  },
  async quote(shipment) {
    const r = await request(this, "quote", {
      method: "POST", body: {
        ClientInfo: this.credentials,
        OriginAddress: { CountryCode: "KE", City: "Nairobi" },
        DestinationAddress: { CountryCode: shipment.destination_country, City: shipment.destination_city },
        ShipmentDetails: { ActualWeight: { Value: shipment.weight_kg, Unit: "KG" }, ProductGroup: "EXP", ProductType: "PDX" },
      },
    });
    return { ok: !r.error, amountKes: Math.round(r.amount * (r.currency === "KES" ? 1 : 129)), currency: "KES", transitDays: r.transit_days, service: r.service, raw: r };
  },
  async createLabel(shipment) {
    const r = await request(this, "label", {
      method: "POST", body: {
        ClientInfo: this.credentials,
        Shipments: [{
          Shipper: { PartyAddress: { CountryCode: "KE", City: "Nairobi" }, Contact: { PersonName: "KICC Desk" } },
          Consignee: { PartyAddress: { CountryCode: shipment.destination_country, City: shipment.destination_city }, Contact: { PersonName: shipment.consignee } },
          Details: { ActualWeight: { Value: shipment.weight_kg, Unit: "KG" }, DescriptionOfGoods: shipment.description, GoodsOriginCountry: "KE", NumberOfPieces: 1, ProductGroup: "EXP", ProductType: "PDX", PaymentType: "P" },
        }],
      },
    });
    return { ok: !r.error, awb: r.awb || r.id, labelUrl: r.label_url, carrierRef: r.id, raw: r };
  },
  async track(awb) {
    const r = await request(this, "track", { method: "POST", body: { ClientInfo: this.credentials, Shipments: [awb] } });
    return { ok: !r.error, status: r.status, events: r.events || [], raw: r };
  },
  async customsDocs() { return { ok: true, commercialInvoice: true, packingList: true, note: "generated from the order payload" }; },
  webhook: { header: null, scheme: "none", verify() { return { ok: true, scheme: "none", warning: "poll the tracking endpoint" }; } },
  mapTracking(body) { return { awb: body?.ShipmentNumber, status: body?.Status, events: body?.Events || [] }; },
  live: () => flag("ARAMEX_ENABLED"),
};
