// lib/contracts.consumers.mjs — the consumer-facing half of the event contract.
// The base contracts (pipeline.trigger/settled/failed) are untouched; this file
// adds the two stages the automation chain needs so a consumer cannot emit or
// accept an event that does not validate.
import { SCHEMAS as BASE } from "./contracts.mjs";

export const CONSUMER_TOPICS = {
  ML_REQUEST: "ml.inference.requested",
  ML_PREDICTION: "ml.prediction",
  ALGO_RESULT: "algorithms.result",
};

export const SCHEMAS = {
  ...BASE,
  "ml.inference.requested": {
    required: ["pipeline_id", "edge_score"],
    types: { pipeline_id: "number", edge_score: "number" },
  },
  "ml.prediction": {
    required: ["pipeline_id", "score", "band"],
    types: { pipeline_id: "number", score: "number", band: "string" },
  },
  "algorithms.result": {
    required: ["pipeline_id", "edge_score"],
    types: { pipeline_id: "number", edge_score: "number" },
  },
};

export function validate(topic, payload = {}) {
  const s = SCHEMAS[topic];
  if (!s) return { ok: false, error: `unknown topic ${topic}` };
  const missing = s.required.filter((k) => payload[k] === undefined || payload[k] === null);
  if (missing.length) return { ok: false, error: `missing ${missing.join(",")}` };
  for (const [k, t] of Object.entries(s.types || {})) {
    if (typeof payload[k] !== t) return { ok: false, error: `${k} must be ${t}, got ${typeof payload[k]}` };
  }
  return { ok: true };
}

export const CONSUMER_SCHEMA_TOPICS = Object.keys(SCHEMAS);
