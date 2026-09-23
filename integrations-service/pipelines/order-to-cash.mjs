// pipelines/order-to-cash.mjs — the end-to-end chain:
// order -> payment authorise -> escrow hold -> carrier quote -> label -> customs
// -> tracking -> escrow release -> desk payout -> pipeline ledger.
import { emit, ledger, log, MOCK_MODE, env } from "../lib/core.js";
import { paymentCandidates, freightCandidates, pick, feeFor, specialHandling } from "../registries.js";
import { PAYMENTS, FREIGHT, SERVICES } from "../registries.js";

const STEPS = [];
const step = (name, detail) => { STEPS.push({ name, ...detail }); emit({ kind: "step", step: name, ...detail }); return detail; };

/** Cold chain, licensed export and high-value flags come from routes.yaml. */
function handlingFor(order) {
  const ids = order.lines.map((l) => Number(l.pipeline_id));
  const h = { cold_chain: false, licensed_export: false, high_value: false, notes: [] };
  for (const id of ids) {
    if ((specialHandling.cold_chain_pipelines || []).includes(id)) { h.cold_chain = true; h.notes.push(`pipeline ${id} needs cold chain`); }
    if ((specialHandling.licensed_export_pipelines || []).includes(id)) { h.licensed_export = true; h.notes.push(`pipeline ${id} needs export licensing`); }
    if ((specialHandling.high_value_pipelines || []).includes(id)) { h.high_value = true; h.notes.push(`pipeline ${id} is high value`); }
  }
  return h;
}

export async function runOrder(order) {
  STEPS.length = 0;
  const t0 = Date.now();
  const total = order.totals_kes.grand;
  const handling = handlingFor(order);
  const lane = order.lines[0].ship_zone || "eu-uk";
  const country = order.buyer.country === "Kenya" ? "KE" : "default";

  emit({ kind: "order.received", order_ref: order.order_ref, total_kes: total, lines: order.lines.length, lane, handling: handling.notes });

  // ---- 1. payment -----------------------------------------------------------
  const method = order.buyer.payment || "card";
  const { provider: pay, mode: payMode } = pick(paymentCandidates(method, country), `lane=${method} country=${country}`);
  step("payment.select", { provider: pay?.id, mode: payMode, lane: method });
  const auth = await pay.authorize({ order, amount: total, currency: "KES", idempotencyKey: `${order.order_ref}-AUTH` });
  step("payment.authorize", { provider: pay.id, ref: auth.providerRef, status: auth.status, mode: payMode });

  // ---- 2. escrow hold (pipeline 58) ----------------------------------------
  const esc = await PAYMENTS.bank.authorize({ order, amount: total, currency: "KES" });
  step("escrow.hold", { provider: "bank", ref: esc.providerRef, status: esc.status, release_condition: "delivery_verified" });

  // ---- 3. freight: quote every routed carrier, then take the best ----------
  const candidates = freightCandidates(lane);
  const quotes = [];
  for (const c of candidates) {
    const q = await c.quote({
      weight_kg: order.lines.reduce((a, l) => a + (l.weight_kg || 0) * l.qty, 0),
      destination_country: order.buyer.country === "Kenya" ? "KE" : (lane === "eu-uk" ? "GB" : "AE"),
      destination_city: order.buyer.town, destination_county: order.buyer.town,
      consignee: `${order.buyer.first} ${order.buyer.last}`, description: order.lines.map((l) => l.title).join("; "),
      cold_chain: handling.cold_chain, order_ref: order.order_ref,
    });
    quotes.push({ carrier: c.id, amountKes: q.amountKes, transitDays: q.transitDays, service: q.service });
  }
  quotes.sort((a, b) => a.amountKes - b.amountKes);
  const chosen = quotes[0];
  step("freight.quote", { lane, quoted: quotes, chosen: chosen.carrier, amount_kes: chosen.amountKes });

  const carrier = FREIGHT[chosen.carrier];
  const label = await carrier.createLabel({
    order_ref: order.order_ref, weight_kg: order.lines.reduce((a, l) => a + (l.weight_kg || 0) * l.qty, 0),
    destination_country: order.buyer.country === "Kenya" ? "KE" : "GB", destination_city: order.buyer.town,
    destination_county: order.buyer.town, consignee: `${order.buyer.first} ${order.buyer.last}`,
    description: order.lines.map((l) => l.title).join("; "),
  });
  step("freight.label", { carrier: carrier.id, awb: label.awb, label_url: label.labelUrl });

  // ---- 4. customs pack, only on a customs-declarable lane ------------------
  let customsPack = null;
  if (lane !== "ke-nairobi" && lane !== "ke-upcountry" && lane !== "ke-remote") {
    customsPack = await SERVICES.customs.buildPack({
      consignee: `${order.buyer.first} ${order.buyer.last}`, destination_country: "GB",
      lines: order.lines, total_value_kes: order.totals_kes.subtotal,
      incoterm: "DAP", exporter_pin: env("ETIMS_PIN", "P051234567X"),
    });
    step("customs.pack", { declaration: customsPack.declarationRef, coo: customsPack.certificateOfOriginRef, kebs: customsPack.kebsPermitRef, documents: customsPack.documents });
  } else {
    step("customs.pack", { skipped: true, reason: "domestic lane - no customs documents required" });
  }

  // ---- 5. tracking ---------------------------------------------------------
  const trk = await carrier.track(label.awb);
  step("freight.track", { carrier: carrier.id, awb: label.awb, status: trk.status, events: trk.events.length });

  // ---- 6. release escrow, capture payment, pay the desks -------------------
  const released = await PAYMENTS.bank.capture(esc.providerRef);
  step("escrow.release", { ref: esc.providerRef, status: released.status, trigger: "delivery_verified" });
  const captured = await pay.capture(auth.providerRef, total);
  step("payment.capture", { provider: pay.id, ref: auth.providerRef, status: captured.status || captured.raw?.status });

  // ---- 7. settle each line to its pipeline desk ---------------------------
  const settlements = [];
  for (const l of order.lines) {
    const fee = feeFor(Number(l.pipeline_id));
    const value = l.unit_kes * l.qty;
    const commission = fee.basis === "percent" ? Math.round(value * fee.value) : fee.value;
    const payout = await PAYMENTS.bank.payoutDesk({
      pipelineId: l.pipeline_id, pipelineName: l.pipeline_name, amount: commission, currency: "KES", orderRef: order.order_ref,
    });
    ledger({
      ts: new Date().toISOString(), pipeline_id: l.pipeline_id, pipeline_name: l.pipeline_name,
      category: l.pipeline_name, mechanism: fee.basis, event_type: "sale.settled",
      value_kes: value, commission_kes: commission, provider_ref: payout.id, settled: true,
    });
    settlements.push({ pipeline_id: l.pipeline_id, pipeline_name: l.pipeline_name, value_kes: value, commission_kes: commission, payout_ref: payout.id, basis: fee.basis, rate: fee.value });
  }
  step("desk.payout", { settlements, total_commission_kes: settlements.reduce((a, s) => a + s.commission_kes, 0) });

  const result = {
    order_ref: order.order_ref, mode: payMode === "live" ? "live" : "mock", mock_mode: MOCK_MODE,
    elapsed_ms: Date.now() - t0,
    payment: { lane: method, provider: pay.id, ref: auth.providerRef, status: auth.status },
    escrow: { ref: esc.providerRef, state: "released", condition: "delivery_verified" },
    freight: { lane, carrier: carrier.id, awb: label.awb, quoted: quotes, amount_kes: chosen.amountKes, transit_days: chosen.transitDays, tracking: trk.status },
    customs: customsPack ? { declaration: customsPack.declarationRef, coo: customsPack.certificateOfOriginRef, kebs: customsPack.kebsPermitRef } : null,
    handling,
    settlements,
    total_commission_kes: settlements.reduce((a, s) => a + s.commission_kes, 0),
    steps: STEPS.map((s) => s.name),
  };
  emit({ kind: "order.settled", order_ref: order.order_ref, total_commission_kes: result.total_commission_kes, steps: result.steps });
  log.info(`order ${order.order_ref} settled in ${result.elapsed_ms}ms via ${carrier.id}/${pay.id} -> commission KES ${result.total_commission_kes.toLocaleString()}`);
  return result;
}
