// providers/fx.js — FX rates. Static fallback, CBK or bank feed.
import { request, env, flag } from "../lib/core.js";

const STATIC = { KES: 1, USD: 0.00775, EUR: 0.00710, GBP: 0.00610, AED: 0.02847, ZAR: 0.13860 };

export default {
  id: "fx",
  label: "FX rates",
  kind: "service",
  enabled_env: "FX_PROVIDER",           // static | cbk | bank
  envRequired: [],
  base_url: "https://www.centralbank.go.ke",
  endpoints: {
    rates: { method: "GET", path: "/rates" },
  },
  async rates() {
    const provider = env("FX_PROVIDER", "static");
    if (provider === "static") return { source: "static", asOf: new Date().toISOString().slice(0, 10), rates: STATIC };
    const r = await request(this, "rates", { method: "GET", body: {} });
    return { source: provider, asOf: r.as_of, rates: r.rates || STATIC };
  },
  async convert(amountKes, currency, rates) {
    const r = rates || (await this.rates()).rates;
    return Math.round(amountKes * (r[currency] || 1) * 100) / 100;
  },
  live: () => flag("FX_PROVIDER") !== "static",
};
