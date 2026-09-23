// providers/customs.js — KRA iCMS declaration, KEBS permit, EPC certificate of origin.
import { request, flag } from "../lib/core.js";

export default {
  id: "customs",
  label: "KRA iCMS / KEBS / EPC documentation",
  kind: "service",
  enabled_env: "CUSTOMS_ENABLED",
  envRequired: ["KRA_ICMS_CLIENT_ID", "KRA_ICMS_CLIENT_SECRET", "KEBS_CLIENT_ID", "KEBS_CLIENT_SECRET", "EPC_CLIENT_ID", "EPC_CLIENT_SECRET"],
  base_url: "https://api.kra.go.ke",
  endpoints: {
    declaration: { method: "POST", path: "/icms/declarations" },
    coo: { method: "POST", path: "/epc/certificate-of-origin" },
    kebs_permit: { method: "POST", path: "/kebs/permits" },
  },

  /** Build the whole customs pack for one consignment. */
  async buildPack(shipment) {
    const declaration = await request(this, "declaration", {
      method: "POST", body: {
        exporter_pin: shipment.exporter_pin, consignee: shipment.consignee,
        destination_country: shipment.destination_country,
        lines: shipment.lines.map((l) => ({
          hs_code: l.hs_code, description: l.title, quantity: l.qty,
          unit_price: l.unit_kes, country_of_origin: "KE",
        })),
        incoterm: shipment.incoterm || "DAP", total_value_kes: shipment.total_value_kes,
      },
    });
    const coo = await request(this, "coo", {
      method: "POST", body: { declaration_ref: declaration.id, destination_country: shipment.destination_country, origin: "KE", goods: shipment.lines.map((l) => l.hs_code) },
    });
    const permit = await request(this, "kebs_permit", {
      method: "POST", body: { declaration_ref: declaration.id, goods: shipment.lines.map((l) => ({ hs_code: l.hs_code, description: l.title })) },
    });
    return {
      ok: !declaration.error,
      declarationRef: declaration.id, certificateOfOriginRef: coo.id, kebsPermitRef: permit.id,
      documents: {
        commercial_invoice: true, packing_list: true,
        certificate_of_origin: !!coo.id, kebs_permit: !!permit.id,
        export_declaration: !!declaration.id,
      },
      raw: { declaration, coo, permit },
    };
  },
  live: () => flag("CUSTOMS_ENABLED"),
};
