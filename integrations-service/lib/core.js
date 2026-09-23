// lib/core.js — env loading, logging, HTTP with retry + idempotency, signing, state.
import fs from "fs";
import path from "path";
import crypto from "crypto";
import { fileURLToPath } from "url";

export const ROOT = path.resolve(path.dirname(fileURLToPath(import.meta.url)), "..");
export const ARTIFACTS = path.join(ROOT, "run", "artifacts");
fs.mkdirSync(ARTIFACTS, { recursive: true });

/* ------------------------------------------------------------------ env ---- */
function parseDotEnv(text) {
  const out = {};
  for (const line of text.split(/\r?\n/)) {
    const m = line.match(/^\s*([A-Z0-9_]+)\s*=\s*(.*)\s*$/);
    if (!m) continue;
    out[m[1]] = m[2].replace(/^["']|["']$/g, "");
  }
  return out;
}
let dotenv = {};
const envPath = path.join(ROOT, ".env");
if (fs.existsSync(envPath)) dotenv = parseDotEnv(fs.readFileSync(envPath, "utf8"));

export const env = (k, d = undefined) => (process.env[k] ?? dotenv[k] ?? d);
export const flag = (k) => String(env(k, "false")).toLowerCase() === "true";
export const MOCK_MODE = String(env("MOCK_MODE", "true")).toLowerCase() === "true";
export const isPlaceholder = (v) => !v || /REPLACE_ME|your-domain|your-bank/i.test(String(v));
export const configured = (provider) =>
  flag(provider.enabled_env) &&
  (provider.envRequired || []).every((k) => !isPlaceholder(env(k)));

/** Exactly which env vars are still blank for a provider. */
export function missingEnv(provider) {
  return (provider.envRequired || []).filter((k) => isPlaceholder(env(k)));
}
export function credentialReport(providers) {
  return providers.map((p) => ({
    id: p.id, label: p.label, kind: p.kind,
    enabled: flag(p.enabled_env),
    envRequired: p.envRequired || [],
    missing: missingEnv(p),
    willRunLive: configured(p) && !MOCK_MODE,
  }));
}

/* ---------------------------------------------------------------- logger --- */
const LEVELS = { error: 0, warn: 1, info: 2, debug: 3 };
const LVL = LEVELS[env("LOG_LEVEL", "info")] ?? 2;
export const log = {
  error: (...a) => LVL >= 0 && console.error("[error]", ...a),
  warn: (...a) => LVL >= 1 && console.warn("[warn ]", ...a),
  info: (...a) => LVL >= 2 && console.log("[info ]", ...a),
  debug: (...a) => LVL >= 3 && console.log("[debug]", ...a),
};

/* ----------------------------------------------------------------- state --- */
const EVENTS = path.join(ARTIFACTS, "events.jsonl");
const LEDGER = path.join(ARTIFACTS, "ledger.csv");
export function emit(event) {
  const row = { ts: new Date().toISOString(), ...event };
  fs.appendFileSync(EVENTS, JSON.stringify(row) + "\n");
  return row;
}
export function readEvents() {
  if (!fs.existsSync(EVENTS)) return [];
  return fs.readFileSync(EVENTS, "utf8").trim().split("\n").filter(Boolean).map((l) => JSON.parse(l));
}
export function resetArtifacts() {
  for (const f of [EVENTS, LEDGER]) if (fs.existsSync(f)) fs.unlinkSync(f);
}
const LEDGER_HEAD = "ts,pipeline_id,pipeline_name,category,mechanism,event_type,value_kes,commission_kes,provider_ref,settled\n";
export function ledger(entry) {
  if (!fs.existsSync(LEDGER)) fs.writeFileSync(LEDGER, LEDGER_HEAD);
  const esc = (v) => `"${String(v ?? "").replace(/"/g, '""')}"`;
  fs.appendFileSync(LEDGER, [
    entry.ts, entry.pipeline_id, esc(entry.pipeline_name), esc(entry.category), entry.mechanism,
    entry.event_type, entry.value_kes, entry.commission_kes, esc(entry.provider_ref), entry.settled,
  ].join(",") + "\n");
}
export function readLedger() {
  if (!fs.existsSync(LEDGER)) return [];
  const [head, ...rows] = fs.readFileSync(LEDGER, "utf8").trim().split("\n");
  const cols = head.split(",");
  return rows.filter(Boolean).map((r) => {
    const cells = r.match(/("([^"]|"")*"|[^,]*)(,|$)/g).map((c) => c.replace(/,$/, "").replace(/^"|"$/g, "").replace(/""/g, '"'));
    return Object.fromEntries(cols.map((c, i) => [c, cells[i]]));
  });
}
export const LEDGER_PATH = LEDGER;
export const EVENTS_PATH = EVENTS;

/* ------------------------------------------------------------------ sign --- */
export const sign = {
  basic: (u, p = "") => "Basic " + Buffer.from(`${u}:${p}`).toString("base64"),
  bearer: (t) => `Bearer ${t}`,
  hmac256: (secret, body) => crypto.createHmac("sha256", secret).update(body).digest("hex"),
  /** Stripe: v1 = HMAC-SHA256(secret, "{t}.{rawBody}"), 5-minute tolerance. */
  verifyStripe(rawBody, header, secret, tolerance = 300) {
    if (!header || !secret) return { ok: false, reason: "missing header or secret" };
    const parts = Object.fromEntries(header.split(",").map((p) => p.split("=")));
    const expected = crypto.createHmac("sha256", secret).update(`${parts.t}.${rawBody}`).digest("hex");
    const a = Buffer.from(expected), b = Buffer.from(parts.v1 || "");
    if (a.length !== b.length || !crypto.timingSafeEqual(a, b)) return { ok: false, reason: "signature mismatch" };
    const age = Math.abs(Date.now() / 1000 - Number(parts.t));
    if (age > tolerance) return { ok: false, reason: `timestamp ${Math.round(age)}s outside ${tolerance}s tolerance` };
    return { ok: true, scheme: "hmac_sha256_timestamped" };
  },
  /** Flutterwave: the header must simply equal the dashboard secret hash. */
  verifyStaticHash(header, secret) {
    if (!header || !secret) return { ok: false, reason: "missing header or secret" };
    const a = Buffer.from(String(header)), b = Buffer.from(String(secret));
    if (a.length !== b.length || !crypto.timingSafeEqual(a, b)) return { ok: false, reason: "hash mismatch" };
    return { ok: true, scheme: "static_hash" };
  },
  /** Generic: hex HMAC-SHA256 of the raw body in a named header. */
  verifyHmac(rawBody, header, secret) {
    if (!header || !secret) return { ok: false, reason: "missing header or secret" };
    const expected = crypto.createHmac("sha256", secret).update(rawBody).digest("hex");
    const a = Buffer.from(expected), b = Buffer.from(String(header));
    if (a.length !== b.length || !crypto.timingSafeEqual(a, b)) return { ok: false, reason: "signature mismatch" };
    return { ok: true, scheme: "hmac_sha256" };
  },
  /** Paystack-style: hex HMAC-SHA512 of the raw body in a named header. */
  verifyHmacSha512(rawBody, header, secret) {
    if (!header || !secret) return { ok: false, reason: "missing header or secret" };
    const expected = crypto.createHmac("sha512", secret).update(rawBody).digest("hex");
    const a = Buffer.from(expected), b = Buffer.from(String(header));
    if (a.length !== b.length || !crypto.timingSafeEqual(a, b)) return { ok: false, reason: "signature mismatch" };
    return { ok: true, scheme: "hmac_sha512" };
  },
};

/* ------------------------------------------------------------------ http --- */
let SEQ = 0;
const sleep = (ms) => new Promise((r) => setTimeout(r, ms));

/** Deterministic PRNG so mock responses are stable across runs. */
function seeded(str) {
  let h = 2166136261;
  for (let i = 0; i < str.length; i++) { h ^= str.charCodeAt(i); h = Math.imul(h, 16777619); }
  return () => { h ^= h << 13; h ^= h >>> 17; h ^= h << 5; return ((h >>> 0) % 100000) / 100000; };
}

export function mockResponse(providerId, key, payload) {
  const rnd = seeded(providerId + ":" + key + ":" + JSON.stringify(payload ?? {}).slice(0, 120));
  const ref = (p) => `${p}-${Math.floor(rnd() * 9e8 + 1e8).toString(36).toUpperCase()}`;
  const base = { _mock: true, _provider: providerId, _endpoint: key, _at: new Date().toISOString() };
  switch (key) {
    case "authorize":
      return { ...base, id: ref("AUTH"), status: "requires_capture", amount: payload?.amount, currency: payload?.currency };
    case "capture":
      return { ...base, id: payload?.id, status: "succeeded" };
    case "refund":
      return { ...base, id: ref("REF"), status: "refunded" };
    case "verify":
      return { ...base, id: payload?.id, status: "succeeded" };
    case "hold":
      return { ...base, id: ref("ESC"), status: "held" };
    case "release":
      return { ...base, id: payload?.id, status: "released" };
    case "payout":
      return { ...base, id: ref("PO"), status: "paid" };
    case "quote": {
      const kg = Math.max(1, payload?.weight_kg ?? 1);
      return { ...base, amount: Math.round(900 + kg * 320), currency: "KES", transit_days: 3 + Math.floor(rnd() * 5), service: "EXPRESS" };
    }
    case "label":
      return { ...base, awb: ref("AWB"), label_url: `https://labels.example/${ref("L")}.pdf`, status: "created" };
    case "track":
      return { ...base, awb: payload?.awb, status: "DELIVERED", events: [
        { code: "PU", at: new Date(Date.now() - 3 * 864e5).toISOString(), text: "Picked up" },
        { code: "TR", at: new Date(Date.now() - 2 * 864e5).toISOString(), text: "In transit" },
        { code: "OD", at: new Date(Date.now() - 1 * 864e5).toISOString(), text: "Out for delivery" },
        { code: "DL", at: new Date().toISOString(), text: "Delivered" },
      ] };
    case "pickup":
      return { ...base, id: ref("PU"), status: "scheduled" };
    case "declaration":
      return { ...base, id: ref("DEC"), status: "lodged" };
    case "coo":
      return { ...base, id: ref("COO"), status: "issued" };
    case "kebs_permit":
      return { ...base, id: ref("KEBS"), status: "granted" };
    case "rates":
      return { ...base, base: "KES", as_of: new Date().toISOString().slice(0, 10), rates: { USD: 0.00775, EUR: 0.00710, GBP: 0.00610, AED: 0.02847, ZAR: 0.13860 } };
    default:
      return { ...base, status: "ok" };
  }
}

/**
 * Uniform request. In MOCK_MODE, or when the provider has no real credentials,
 * returns a deterministic mock and never touches the network.
 */
export async function request(provider, key, { method = "GET", path: p, body, idempotencyKey } = {}) {
  const call = {
    seq: ++SEQ, provider: provider.id, endpoint: key, method,
    path: (p || provider.endpoints?.[key]?.path || "").replace(/\{[^}]+\}/g, (m) => body?.[m.slice(1, -1)] ?? m),
    idempotencyKey: idempotencyKey || `${env("IDEMPOTENCY_NAMESPACE", "kicc")}-${provider.id}-${key}-${Date.now()}-${SEQ}`,
  };
  const live = configured(provider) && !MOCK_MODE;
  if (!live) {
    const res = mockResponse(provider.id, key, body);
    emit({ kind: "http.mock", ...call, status: 200, note: "no live credentials or MOCK_MODE=true" });
    return res;
  }
  const url = new URL(call.path, provider.baseUrlResolved || provider.base_url);
  const headers = { "Content-Type": "application/json", "Idempotency-Key": call.idempotencyKey, ...(provider.authHeaders || {}) };
  const max = Number(env("MAX_RETRIES", 3));
  for (let attempt = 1; attempt <= max; attempt++) {
    const ctl = new AbortController();
    const to = setTimeout(() => ctl.abort(), Number(env("REQUEST_TIMEOUT_MS", 20000)));
    try {
      const r = await fetch(url, { method, headers, body: body ? JSON.stringify(body) : undefined, signal: ctl.signal });
      clearTimeout(to);
      const text = await r.text();
      const json = (() => { try { return JSON.parse(text); } catch { return { raw: text }; } })();
      emit({ kind: "http.live", ...call, status: r.status, attempt });
      if (r.status === 429 || r.status >= 500) {
        if (attempt < max) { await sleep(2 ** attempt * 250); continue; }
        return { ok: false, status: r.status, error: "retries exhausted", raw: json };
      }
      return { ok: r.ok, status: r.status, ...json };
    } catch (e) {
      clearTimeout(to);
      emit({ kind: "http.error", ...call, attempt, error: String(e.message || e) });
      if (attempt < max) { await sleep(2 ** attempt * 250); continue; }
      return { ok: false, error: String(e.message || e) };
    }
  }
}
