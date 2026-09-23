// pipelines/cascade.mjs — the orchestrator. One pipeline settling automatically
// drives every pipeline that depends on it, recursively, through the bus.
// Failure stops the dependent chain rather than half-executing it.
import path from "path";
import { ROOT, ledger } from "../lib/core.js";
import { Bus } from "../lib/bus.mjs";
import { validate, TOPICS } from "../lib/contracts.mjs";
import { loadGraph } from "./graph.mjs";
import { feeFor } from "../registries.js";

export const REGISTRY_DIR = path.join(ROOT, "..");

export async function runCascade(opts = {}) {
  const rootDir = opts.rootDir || REGISTRY_DIR;
  const roots = (opts.roots || [1]).map(Number);
  const maxHops = opts.maxHops ?? 14;
  const poison = new Set((opts.poison || []).map(Number));
  const g = loadGraph(rootDir);
  const bus = new Bus({ maxDepth: opts.maxDepth ?? 20000, backoffMs: 4, ...(opts.busOpts || {}) });
  const byId = new Map(g.nodes.map((n) => [Number(n.id), n]));
  const executed = [];
  const skipped = [];
  const done = new Set();          // `${id}|${corr}` — idempotent under bus retry

  bus.subscribe(TOPICS.TRIGGER, async (ev) => {
    const id = Number(ev.payload.pipeline_id);
    const corr = ev.correlationId;
    const key = `${id}|${corr}`;
    if (done.has(key)) return;      // already settled under this correlation
    if (ev.payload.hop > maxHops) { skipped.push({ pipeline_id: id, corr, reason: "hop-limit" }); return; }
    if (poison.has(id)) throw new Error(`pipeline ${id} failed permanently (injected fault)`);

    const p = byId.get(id);
    const value = Math.max(50000, Math.round((p?.full_maturity_kes || 1e6) / 120));
    const fee = feeFor(id);
    const commission = fee.basis === "percent" ? Math.round(value * fee.value) : fee.value;

    await ledger({
      ts: new Date().toISOString(), pipeline_id: id, pipeline_name: p?.name || `#${id}`,
      category: p?.category || "?", mechanism: p?.mechanism || "?", event_type: "cascade.settled",
      value_kes: value, commission_kes: commission, provider_ref: `cascade/${corr}`, settled: true,
    });

    done.add(key);                  // marked done only AFTER the ledger write, so a bus retry re-runs rather than silently succeeding
    const settled = { pipeline_id: id, value_kes: value, commission_kes: commission, mechanism: p?.mechanism || "?", correlationId: corr };
    const v = validate(TOPICS.SETTLED, settled);
    if (!v.ok) throw new Error(`contract violation on ${TOPICS.SETTLED}: ${v.error}`);
    executed.push({ pipeline_id: id, correlationId: corr, hop: ev.payload.hop, value_kes: value, commission_kes: commission, mechanism: settled.mechanism });
    await bus.publish(TOPICS.SETTLED, settled, { correlationId: corr, causationId: ev.id, hop: ev.payload.hop, key: String(id) });

    for (const d of g.dependentsOf(id)) {
      await bus.publish(TOPICS.TRIGGER, { pipeline_id: d, hop: ev.payload.hop + 1, root: ev.payload.root ?? id },
        { correlationId: corr, causationId: ev.id, hop: ev.payload.hop + 1, key: String(d) });
    }
  });

  bus.subscribe(TOPICS.SETTLED, async (ev) => {
    const v = validate(TOPICS.SETTLED, ev.payload);
    if (!v.ok) throw new Error(`downstream rejected contract: ${v.error}`);
  });

  const stamp = Date.now().toString(36);
  for (const r of roots) {
    await bus.publish(TOPICS.TRIGGER, { pipeline_id: r, hop: 0, root: r }, { correlationId: `casc-${r}-${stamp}`, key: String(r) });
  }
  await bus.drain();

  const dlq = bus.readDlq();
  return {
    graph: g.summary,
    executed,
    skipped,
    failed: [...bus.failed.entries()].map(([k, reason]) => ({ key: k, reason })),
    dlq,
    metrics: bus.stats,
    bus,
    expectedFrom: (r) => [...g.descendantsOf(r)],
    descendants: (r) => [...g.descendantsOf(r)],
  };
}
