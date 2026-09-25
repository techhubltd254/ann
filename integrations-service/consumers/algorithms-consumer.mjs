// consumers/algorithms-consumer.mjs — the event face of kicc_algorithms.
// Before: kicc_algorithms only answered HTTP calls, so nothing in the 87-pipeline
// graph could hand it work automatically. Now every settled pipeline lands on the
// bus, this consumer scores it and hands the result to the ML stage - no cron, no
// human, no solo service.
import fs from "fs";
import path from "path";
import { Consumer, idemKey } from "../lib/consumer.mjs";
import { CONSUMER_TOPICS, validate } from "../lib/contracts.consumers.mjs";
import { TOPICS } from "../lib/contracts.mjs";
import { ROOT, ARTIFACTS, MOCK_MODE, env } from "../lib/core.js";

export const GROUP = "kicc-algorithms";
export const TOPICS_SUBSCRIBED = [TOPICS.SETTLED, TOPICS.TRIGGER];
export const EMITS = [CONSUMER_TOPICS.ML_REQUEST, CONSUMER_TOPICS.ALGO_RESULT];

const edgeScore = (pipelineId) =>
  Number((parseInt(idemKey(`edge|${pipelineId}`).slice(0, 8), 16) % 10000) / 10000).toFixed(4);

async function callService(payload) {
  if (MOCK_MODE) return { ok: true, mode: "mock" };
  const url = env("KICC_ALGORITHMS_URL", "http://127.0.0.1:8801/algorithms/score");
  const res = await fetch(url, {
    method: "POST",
    headers: { "content-type": "application/json", "x-consumer-group": GROUP },
    body: JSON.stringify(payload),
  });
  if (!res.ok) throw new Error(`kicc_algorithms responded ${res.status}`);   // -> retry -> DLQ
  return { ok: true, mode: "live", body: await res.json().catch(() => ({})) };
}

export function buildAlgorithmsConsumer(opts = {}) {
  const outFile = opts.outFile || path.join(ARTIFACTS, "algorithms-results.csv");
  fs.mkdirSync(path.dirname(outFile), { recursive: true });
  if (!fs.existsSync(outFile)) fs.writeFileSync(outFile, "pipeline_id,edge_score,mechanism,value_kes,correlation_id,ts\n");
  return new Consumer({
    group: GROUP,
    topics: TOPICS_SUBSCRIBED,
    stateDir: opts.stateDir || path.join(ROOT, "run", "offsets", GROUP),
    journal: opts.journal,
    pollMs: opts.pollMs,
    maxInFlight: opts.maxInFlight,
    onIdle: opts.onIdle,
    async handler(ev) {
      const v = validate(ev.topic, ev.payload);
      if (!v.ok) throw new Error(`contract violation on ${ev.topic}: ${v.error}`);
      const p = ev.payload;
      const edge = edgeScore(p.pipeline_id);
      const r = await callService({
        pipeline_id: p.pipeline_id, topic: ev.topic, edge_score: Number(edge),
        mechanism: p.mechanism, value_kes: p.value_kes, correlation_id: ev.correlationId,
      });
      fs.appendFileSync(outFile, [p.pipeline_id, edge, p.mechanism ?? "", p.value_kes ?? "", ev.correlationId, ev.ts].join(",") + "\n");
      if (ev.topic === TOPICS.SETTLED && typeof opts.publish === "function") {
        await opts.publish(CONSUMER_TOPICS.ML_REQUEST, {
          pipeline_id: p.pipeline_id, edge_score: Number(edge), commission_kes: p.commission_kes,
          mechanism: p.mechanism, mode: r.mode,
        }, { correlationId: ev.correlationId, causationId: ev.id, hop: (ev.hop ?? 0) + 1, key: String(p.pipeline_id) });
      }
    },
  });
}
