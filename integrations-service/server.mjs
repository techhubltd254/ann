// server.mjs — HTTP surface for the inter-pipeline bus.
// Lets the Laravel app (IntegrationClient / PipelineBusClient) and the Mother Admin
// panel drive and monitor ONE automation graph over the 87 pipelines.
import http from "http";
import fs from "fs";
import path from "path";
import { fileURLToPath } from "url";
import { runCascade, REGISTRY_DIR } from "./pipelines/cascade.mjs";
import { loadGraph } from "./pipelines/graph.mjs";

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const PORT = process.env.PIPELINE_BUS_PORT || 8790;
const ART = path.join(__dirname, "run", "artifacts");

let lastRun = {
  published: 0, delivered: 0, deduped: 0, deadLettered: 0,
  settled: 0, failed: 0, elapsed_ms: 0, correlation: null, runs: 0,
};

const json = (res, code, body) => {
  res.writeHead(code, { "content-type": "application/json" });
  res.end(JSON.stringify(body));
};
const readBody = (req) => new Promise((r) => {
  let b = "";
  req.on("data", (c) => (b += c));
  req.on("end", () => { try { r(b ? JSON.parse(b) : {}); } catch { r({}); } });
});
const firstExisting = (names) => {
  for (const n of names) { const p = path.join(ART, n); if (fs.existsSync(p)) return p; }
  return null;
};
const normalize = (o) => {
  const m = { pipeline: "pipeline_id", pipelineId: "pipeline_id", value: "value_kes",
    commission: "commission_kes", correlation: "correlationId", correlation_id: "correlationId" };
  for (const [k, v] of Object.entries(m)) if (o[k] !== undefined && o[v] === undefined) o[v] = o[k];
  return o;
};
function readLedger(limit) {
  const p = firstExisting(["ledger.csv", "ledger.jsonl"]);
  if (!p) return [];
  const txt = fs.readFileSync(p, "utf8").trim();
  if (!txt) return [];
  if (p.endsWith(".csv")) {
    const lines = txt.split("\n").filter(Boolean);
    const head = lines[0].split(",").map((s) => s.trim());
    return lines.slice(1).map((l) => {
      const cells = l.split(",");
      const o = {};
      head.forEach((h, i) => (o[h] = (cells[i] ?? "").trim()));
      return normalize(o);
    }).slice(-limit).reverse();
  }
  return txt.split("\n").filter(Boolean).map((l) => { try { return normalize(JSON.parse(l)); } catch { return null; } })
    .filter(Boolean).slice(-limit).reverse();
}
function readDlq(limit) {
  const p = firstExisting(["dlq.jsonl", "bus-dlq.jsonl", "catalog-dlq.jsonl"]);
  if (!p) return [];
  return fs.readFileSync(p, "utf8").split("\n").filter(Boolean)
    .map((l) => { try { return JSON.parse(l); } catch { return null; } })
    .filter(Boolean).slice(-limit).reverse();
}

http.createServer(async (req, res) => {
  const url = new URL(req.url, "http://x");
  try {
    if (req.method === "GET" && url.pathname === "/health") {
      return json(res, 200, { ok: true, service: "kicc-pipeline-bus", uptime_s: Math.round(process.uptime()) });
    }
    if (req.method === "GET" && url.pathname === "/api/pipeline/graph") {
      const g = loadGraph(REGISTRY_DIR);
      return json(res, 200, { ok: true, summary: g.summary, edges: g.edges.slice(0, 3000) });
    }
    if (req.method === "GET" && url.pathname === "/api/pipeline/upstream") {
      const g = loadGraph(REGISTRY_DIR);
      const id = Number(url.searchParams.get("id"));
      return json(res, 200, { ok: true, id, upstream: g.upstreamOf(id), downstream: g.dependentsOf(id), descendants: [...g.descendantsOf(id)] });
    }
    if (req.method === "GET" && url.pathname === "/api/pipeline/status") {
      const g = loadGraph(REGISTRY_DIR);
      return json(res, 200, { ok: true, service: "kicc-pipeline-bus", graph: g.summary, metrics: lastRun, last_run: lastRun });
    }
    if (req.method === "GET" && url.pathname === "/api/pipeline/ledger") {
      return json(res, 200, { ok: true, rows: readLedger(Number(url.searchParams.get("limit") || 100)) });
    }
    if (req.method === "GET" && url.pathname === "/api/pipeline/dlq") {
      return json(res, 200, { ok: true, rows: readDlq(Number(url.searchParams.get("limit") || 100)) });
    }
    if (req.method === "POST" && (url.pathname === "/api/pipeline/cascade" || url.pathname === "/api/pipeline/trigger")) {
      const body = await readBody(req);
      const isTrigger = url.pathname.endsWith("trigger");
      const roots = (isTrigger ? [Number(body.pipeline_id || 1)] : (body.roots ?? [1])).map(Number);
      const t0 = Date.now();
      const r = await runCascade({ roots, poison: body.poison ?? [], maxHops: body.maxHops ?? 14 });
      const settled = [...new Set(r.executed.map((x) => x.pipeline_id))];
      lastRun = {
        ...lastRun, ...(r.metrics || {}),
        settled: settled.length,
        failed: (r.failed || []).length,
        deadLettered: (r.dlq || []).length,
        elapsed_ms: Date.now() - t0,
        correlation: (r.metrics && r.metrics.correlation) || null,
        runs: lastRun.runs + 1,
      };

      // ── Bridge: notify Laravel to turn settled pipelines into real DB revenue ──
      const laravelUrl = process.env.LARAVEL_BASE_URL || "http://127.0.0.1:8000";
      const laravelSecret = process.env.KICC_INTEGRATION_WEBHOOK_SECRET || "";
      try {
        await fetch(`${laravelUrl}/api/pipeline/earn-settled`, {
          method: "POST",
          headers: { "Content-Type": "application/json", "X-Integration-Secret": laravelSecret },
          body: JSON.stringify({ pipeline_ids: settled }),
        });
      } catch (e) {
        // Non-fatal — the cascade still succeeded; revenue sync can run later via kicc:pipeline:earn
        console.log(`[bus] laravel earn-bridge skipped: ${e.message}`);
      }

      return json(res, 200, {
        ok: true, graph: r.graph, pipelines: settled, settled: settled.length,
        failed: r.failed, dlq: (r.dlq || []).length, metrics: lastRun,
      });
    }
    return json(res, 404, { ok: false, error: "not found" });
  } catch (e) {
    return json(res, 500, { ok: false, error: String((e && e.message) || e) });
  }
}).listen(PORT, () => console.log(`kicc-pipeline-bus on :${PORT}`));
