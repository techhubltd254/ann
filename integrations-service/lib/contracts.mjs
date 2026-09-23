// lib/contracts.mjs — typed event contracts. A pipeline cannot emit or consume an
// event that does not validate, which is what stops silent drift between the 87 lines.
export const TOPICS = {
  TRIGGER: "pipeline.trigger",
  SETTLED: "pipeline.settled",
  FAILED: "pipeline.failed",
};

export const SCHEMAS = {
  "pipeline.trigger": {
    required: ["pipeline_id", "hop"],
    types: { pipeline_id: "number", hop: "number" },
  },
  "pipeline.settled": {
    required: ["pipeline_id", "value_kes", "commission_kes", "mechanism", "correlationId"],
    types: { pipeline_id: "number", value_kes: "number", commission_kes: "number", mechanism: "string" },
  },
  "pipeline.failed": {
    required: ["pipeline_id", "reason"],
    types: { pipeline_id: "number", reason: "string" },
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
