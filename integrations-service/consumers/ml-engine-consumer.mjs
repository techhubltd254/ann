// consumers/ml-engine-consumer.mjs — the event face of ml-engine.
// ml-engine used to be pure request/response with no queue, so it could never be
// driven by another pipeline. It now consumes the algorithms handoff and publishes
// a scored prediction that other pipelines can depend on.
import fs from "fs";
import path from "path";
import { Consumer, idemKey } from "../lib/consumer.mjs";
import { CONSUMER_TOPICS, validate } from "../lib/contracts.consumers.mjs";
import { TOPICS } from "../lib/contracts.mjs";
import { ROOT, ARTIFACTS, MOCK_MODE, env } from "../lib/core.js";

export const GROUP = "ml-engine";
export const TOPICS_SUBSCRIBED = [CONSUMER_TOPICS.ML_REQUEST, TOPICS.TRIGGER];
export const EMITS = [CONSUMER_TOPICS.ML_PREDICTION];

const band = (s) => (s >= 0.75 ? "high" : s >= 0.45 ? "medium" : "low");

async function infer(payload) {
  if (MOCK_MODE) {
    const raw = parseInt(idemKey(`ml|${payload.pipeline_id}`).slice(0, 8), 16) % 10000;
    const score = Number((0.6 * (payload.edge_score ?? 0.5) + 0.4 * (raw / 10000)).toFixed(4));
    return { score, band: band(score), mode: "mock" };
  }
  const url = env("ML_ENGINE_URL", "http://127.0.0.1:8802/predict");
  const res = await fetch(url, {
    method: "POST",
    headers: { "content-type": "application/json", "x-consumer-group": GROUP },
    body: JSON.stringify(payload),
  });
  if (!res.ok) throw new Error(`ml-engine responded ${res.status}`);
  const body = await res.json().catch(() => ({}));
  const score = Number(body.score ?? 0);
  return { score, band: body.band || band(score), mode: "live" };
}

export function buildMlEngineConsumer(opts = {}) {
  const outFile = opts.outFile || path.join(ARTIFACTS, "ml-predictions.csv");
  fs.mkdirSync(path.dirname(outFile), { recursive: true });
  if (!fs.existsSync(outFile)) fs.writeFileSync(outFile, "pipeline_id,score,band,edge_score,correlation_id,ts\n");
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
      const r = await infer(p);
      fs.appendFileSync(outFile, [p.pipeline_id, r.score, r.band, p.edge_score ?? "", ev.correlationId, ev.ts].join(",") + "\n");
      if (ev.topic === CONSUMER_TOPICS.ML_REQUEST && typeof opts.publish === "function") {
        await opts.publish(CONSUMER_TOPICS.ML_PREDICTION, {
          pipeline_id: p.pipeline_id, score: r.score, band: r.band, correlationId: ev.correlationId,
        }, { correlationId: ev.correlationId, causationId: ev.id, hop: (ev.hop ?? 0) + 1, key: String(p.pipeline_id) });
      }
    },
  });
}
