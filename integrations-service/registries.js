// registries.js — one place that knows every driver and every route.
import fs from "fs";
import path from "path";
import { ROOT, flag, env, configured, missingEnv } from "./lib/core.js";
import stripe from "./providers/payments/stripe.js";
import flutterwave from "./providers/payments/flutterwave.js";
import paystack from "./providers/payments/paystack.js";
import mpesa from "./providers/payments/mpesa.js";
import pesapal from "./providers/payments/pesapal.js";
import bank from "./providers/payments/bank.js";
import dhl from "./providers/freight/dhl.js";
import fedex from "./providers/freight/fedex.js";
import aramex from "./providers/freight/aramex.js";
import sendy from "./providers/freight/sendy.js";
import posta from "./providers/freight/posta.js";
import ocean from "./providers/freight/ocean.js";
import customs from "./providers/customs.js";
import fx from "./providers/fx.js";

export const PAYMENTS = { stripe, flutterwave, paystack, mpesa, pesapal, bank };
export const FREIGHT = { dhl, fedex, aramex, sendy, posta, ocean };
export const SERVICES = { customs, fx };
export const ALL = [...Object.values(PAYMENTS), ...Object.values(FREIGHT), ...Object.values(SERVICES)];

/* --- minimal YAML reader for the two config files (flat-ish structures) --- */
function yaml(file) {
  const text = fs.readFileSync(path.join(ROOT, "config", file), "utf8");
  const root = {}; const stack = [{ indent: -1, node: root }];
  for (const raw of text.split(/\r?\n/)) {
    if (!raw.trim() || raw.trim().startsWith("#")) continue;
    const nocomment = raw.replace(/\s+#.*$/, "");   // drop inline "# ..." comments so numbers parse
    if (!nocomment.trim()) continue;
    const indent = nocomment.match(/^ */)[0].length;
    const line = nocomment.trim();
    while (stack.length > 1 && indent <= stack[stack.length - 1].indent) stack.pop();
    const parent = stack[stack.length - 1].node;
    const m = line.match(/^([^:]+):\s*(.*)$/);
    if (!m) continue;
    const key = m[1].replace(/^["']|["']$/g, "").trim();
    const val = m[2].trim();
    if (val === "" || val === ">" || val === "|") { const node = {}; parent[key] = node; stack.push({ indent, node }); }
    else if (val.startsWith("[") && val.endsWith("]")) parent[key] = val.slice(1, -1).split(",").map((s) => s.trim()).filter(Boolean)
      .map((s) => (/^-?\d+$/.test(s) ? Number(s) : s.replace(/^["']|["']$/g, "")));   // numeric ids stay numbers, so .includes(5) works
    else if (val === "true" || val === "false") parent[key] = val === "true";
    else if (/^-?\d+(\.\d+)?$/.test(val)) parent[key] = Number(val);
    else parent[key] = val.replace(/^["']|["']$/g, "");
  }
  return root;
}

export const providersCfg = yaml("providers.yaml");
export const routesCfg = yaml("routes.yaml");

/** Ordered candidate list for a payment lane, most preferred first. */
export function paymentCandidates(method, country) {
  const lane = routesCfg.payments?.[method] || routesCfg.payments?.card || {};
  const list = lane[country] || lane.default || [];
  return list.map((id) => PAYMENTS[id]).filter(Boolean);
}
/** Ordered candidate list for a freight lane. */
export function freightCandidates(lane) {
  const list = routesCfg.freight?.[lane] || routesCfg.freight?.["eu-uk"] || [];
  return list.map((id) => FREIGHT[id]).filter(Boolean);
}
/** Pick the first candidate that can actually serve this call. */
export function pick(candidates, reason = "") {
  for (const c of candidates) {
    const live = configured(c) && !(env("MOCK_MODE", "true") === "true");
    if (live) return { provider: c, mode: "live", reason };
  }
  const first = candidates[0];
  return first ? { provider: first, mode: "mock", reason: reason || "no live credentials - using mock" } : { provider: null, mode: "none" };
}
export const feeFor = (pipelineId) => {
  const num = (v) => { const n = Number(v); return Number.isFinite(n) ? n : null; };
  const pct = num(routesCfg.fee_basis?.percent_pipelines?.[pipelineId]);
  if (pct !== null) return { basis: "percent", value: pct };
  const flat = num(routesCfg.fee_basis?.flat_pipelines?.[pipelineId]);
  if (flat !== null) return { basis: "flat", value: flat };
  return { basis: "percent", value: 0 };
};
export const specialHandling = routesCfg.special_handling || {};
export { flag, env, configured, missingEnv };
