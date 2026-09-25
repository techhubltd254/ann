// consumers/registry.mjs — one place that knows which service consumes what.
// Both consumers are built from the SAME durable Consumer class, so ack/retry/
// idempotency/shutdown behaviour cannot drift between them.
import path from "path";
import { ROOT, ARTIFACTS } from "../lib/core.js";
import { buildAlgorithmsConsumer, GROUP as ALGORITHMS_GROUP, TOPICS_SUBSCRIBED as ALGORITHMS_TOPICS, EMITS as ALGORITHMS_EMITS } from "./algorithms-consumer.mjs";
import { buildMlEngineConsumer, GROUP as ML_GROUP, TOPICS_SUBSCRIBED as ML_TOPICS, EMITS as ML_EMITS } from "./ml-engine-consumer.mjs";

export const CONSUMER_SPEC = [
  {
    group: ALGORITHMS_GROUP, service: "kicc_algorithms",
    consumes: ALGORITHMS_TOPICS, emits: ALGORITHMS_EMITS,
    entry: "integrations-service/consumers/run_consumers.mjs --only=" + ALGORITHMS_GROUP,
    endpoint_env: "KICC_ALGORITHMS_URL",
  },
  {
    group: ML_GROUP, service: "ml-engine",
    consumes: ML_TOPICS, emits: ML_EMITS,
    entry: "integrations-service/consumers/run_consumers.mjs --only=" + ML_GROUP,
    endpoint_env: "ML_ENGINE_URL",
  },
];

export function buildConsumers({ bus, journal, stateRoot, outDir, pollMs, maxInFlight, onIdle } = {}) {
  const publish = bus ? (t, p, m) => bus.publish(t, p, m) : async () => {};
  const base = { journal, pollMs, maxInFlight, onIdle, publish };
  const states = stateRoot || path.join(ROOT, "run", "offsets");
  const outs = outDir || ARTIFACTS;
  return [
    buildAlgorithmsConsumer({ ...base, stateDir: path.join(states, ALGORITHMS_GROUP), outFile: path.join(outs, "algorithms-results.csv") }),
    buildMlEngineConsumer({ ...base, stateDir: path.join(states, ML_GROUP), outFile: path.join(outs, "ml-predictions.csv") }),
  ];
}
