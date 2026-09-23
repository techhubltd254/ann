// webhooks/server.js — one HTTP server for every provider callback.
// Verifies the signature the provider actually uses, then maps the event onto
// order state. Start with:  node webhooks/server.js
import http from "http";
import fs from "fs";
import path from "path";
import { env, emit, log, ARTIFACTS } from "../lib/core.js";
import { ALL, configured } from "../registries.js";

const BY_ID = Object.fromEntries(ALL.filter((p) => p.webhook).map((p) => [p.id, p]));
const ORDERS = path.join(ARTIFACTS, "order-state.jsonl");

function readBody(req) {
  return new Promise((resolve) => {
    let raw = "";
    req.on("data", (c) => (raw += c));
    req.on("end", () => resolve(raw));
  });
}

function verify(provider, rawBody, headers) {
  const w = provider.webhook;
  if (!w) return { ok: false, reason: "provider has no webhook defined" };
  if (w.verify) return w.verify(rawBody, headers);
  return { ok: false, reason: "no verifier" };
}

const server = http.createServer(async (req, res) => {
  const url = new URL(req.url, `http://${req.headers.host}`);
  const parts = url.pathname.split("/").filter(Boolean);

  if (url.pathname === "/health") {
    res.writeHead(200, { "Content-Type": "application/json" });
    return res.end(JSON.stringify({
      ok: true, mock_mode: env("MOCK_MODE", "true") === "true",
      routes: Object.keys(BY_ID),
      live_configured: Object.values(BY_ID).filter(configured).map((p) => p.id),
    }));
  }

  if (parts[0] !== "webhook" || !parts[1]) { res.writeHead(404); return res.end("not found"); }
  const id = parts[1];
  const provider = BY_ID[id];
  if (!provider) { res.writeHead(404); return res.end(`unknown provider ${id}`); }

  const rawBody = await readBody(req);
  const check = verify(provider, rawBody, req.headers);
  emit({ kind: "webhook.received", provider: id, bytes: rawBody.length, verified: check.ok, scheme: check.scheme, reason: check.reason });

  if (!check.ok) {
    log.warn(`webhook ${id} REJECTED: ${check.reason}`);
    res.writeHead(401, { "Content-Type": "application/json" });
    return res.end(JSON.stringify({ ok: false, error: check.reason }));
  }

  let body = {};
  try { body = JSON.parse(rawBody || "{}"); } catch { /* leave empty */ }
  const mapped = provider.mapEvent ? provider.mapEvent(body) : {};
  if (mapped.orderRef) {
    fs.appendFileSync(ORDERS, JSON.stringify({ ts: new Date().toISOString(), provider: id, ...mapped }) + "\n");
  }
  emit({ kind: "webhook.accepted", provider: id, mapped });
  log.info(`webhook ${id} accepted -> ${mapped.orderRef || "(no order ref)"} state=${mapped.state || "?"}`);
  res.writeHead(200, { "Content-Type": "application/json" });
  res.end(JSON.stringify({ ok: true, provider: id, mapped }));
});

const port = Number(env("WEBHOOK_PORT", 8787));
if (process.argv[1] && process.argv[1].endsWith("server.js")) {
  server.listen(port, () => {
    log.info(`webhook server listening on http://localhost:${port}`);
    log.info(`routes: ${Object.keys(BY_ID).map((k) => `/webhook/${k}`).join("  ")}`);
    log.info(`health: http://localhost:${port}/health`);
  });
}
export default server;
