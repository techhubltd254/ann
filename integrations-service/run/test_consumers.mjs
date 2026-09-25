// run/test_consumers.mjs — proves the kicc_algorithms and ml-engine consumers really
// consume bus events: end-to-end chain, backpressure, poison->DLQ, replays, checkpoint
// durability and graceful shutdown. No mocks for the bus itself - it is the real Bus.
import fs from "fs";
import os from "os";
import path from "path";
import { spawn } from "child_process";
import { Bus } from "../lib/bus.mjs";
import { buildConsumers, CONSUMER_SPEC } from "../consumers/registry.mjs";
import { Consumer } from "../lib/consumer.mjs";
import { TOPICS } from "../lib/contracts.mjs";
import { CONSUMER_TOPICS } from "../lib/contracts.consumers.mjs";

const sleep = (ms) => new Promise((r) => setTimeout(r, ms));
let pass = 0, fail = 0;
const ok = (m) => { pass++; console.log("  PASS", m); };
const no = (m) => { fail++; console.log("  FAIL", m); };
const eq = (m, a, b) => (JSON.stringify(a) === JSON.stringify(b) ? ok(`${m} (=${JSON.stringify(a)})`) : no(`${m}: expected ${JSON.stringify(b)}, got ${JSON.stringify(a)}`));
const truthy = (m, v) => (v ? ok(m) : no(`${m} (got ${JSON.stringify(v)})`));
async function until(fn, ms = 8000) {
  const t0 = Date.now();
  for (;;) { if (fn()) return true; if (Date.now() - t0 > ms) return false; await sleep(15); }
}
const tmp = fs.mkdtempSync(path.join(os.tmpdir(), "kicc-cons-"));
const lines = (f) => { try { return fs.readFileSync(f, "utf8").split("\n").filter(Boolean); } catch { return []; } };

console.log(`consumer groups under test: ${CONSUMER_SPEC.map((s) => s.service + "->" + s.group).join(", ")}`);
console.log(`journal under test: ${tmp}/bus.jsonl\n`);

/* ---------- 1. end-to-end chain: settled -> algorithms -> ml request -> prediction ---------- */
console.log("1. end-to-end chain (algorithms consumes, ML consumes the algorithms handoff)");
{
  const dir = path.join(tmp, "e2e");
  const journal = path.join(dir, "bus.jsonl");
  const bus = new Bus({ artifactsDir: dir, eventsFile: "bus.jsonl" });
  const [alg, ml] = buildConsumers({ bus, journal, stateRoot: path.join(dir, "offsets"), outDir: path.join(dir, "out"), pollMs: 3 });
  await alg.start({ handleSignals: false }); await ml.start({ handleSignals: false });
  for (const id of [1, 2, 3]) {
    await bus.publish(TOPICS.SETTLED, { pipeline_id: id, value_kes: 1000 * id, commission_kes: 50 * id, mechanism: "county-content", correlationId: `corr-e2e-${id}` }, { correlationId: `corr-e2e-${id}`, key: String(id) });
  }
  await bus.drain();
  await bus.eventSink.flush();
  await until(() => alg.stats.processed >= 3 && ml.stats.processed >= 3);
  const mlReqSeen = lines(path.join(dir, "out", "ml-predictions.csv")).length - 1;
  const algOut = lines(path.join(dir, "out", "algorithms-results.csv")).length - 1;
  eq("kicc_algorithms consumed settled events", alg.stats.processed, 3);
  eq("ml-engine consumed the algorithms handoff", ml.stats.processed, 3);
  truthy("ml.inference.requested was emitted onto the bus", bus.readEvents().some((e) => e.topic === CONSUMER_TOPICS.ML_REQUEST));
  truthy("ml.prediction was emitted onto the bus", bus.readEvents().some((e) => e.topic === CONSUMER_TOPICS.ML_PREDICTION));
  eq("algorithms result rows written", algOut, 3);
  eq("ml prediction rows written", mlReqSeen, 3);
  eq("no dead letters on the happy path", alg.stats.dlq + ml.stats.dlq, 0);
  await alg.stop(); await ml.stop();
}

/* ---------- 2. backpressure: maxInFlight caps concurrency ---------- */
console.log("\n2. backpressure / in-flight cap under a 500-event burst");
{
  const dir = path.join(tmp, "burst");
  const journal = path.join(dir, "bus.jsonl");
  const bus = new Bus({ artifactsDir: dir, eventsFile: "bus.jsonl" });
  const [alg] = buildConsumers({ bus, journal, stateRoot: path.join(dir, "offsets"), outDir: path.join(dir, "out"), pollMs: 1, maxInFlight: 4 });
  await alg.start({ handleSignals: false });
  for (let i = 100; i < 600; i++) {
    await bus.publish(TOPICS.SETTLED, { pipeline_id: i, value_kes: i, commission_kes: 1, mechanism: "burst", correlationId: `corr-b-${i}` }, { correlationId: `corr-b-${i}`, key: String(i) });
  }
  await bus.drain(); await bus.eventSink.flush();
  await until(() => alg.stats.processed >= 500);
  eq("all 500 burst events consumed", alg.stats.processed, 500);
  truthy(`peak in-flight (${alg.stats.peak_inflight}) never exceeded maxInFlight=4`, alg.stats.peak_inflight <= 4);
  const drained = await until(() => alg.lag === 0, 12000);
  truthy(`lag drained to 0 (lag=${alg.lag})`, drained);
  await alg.stop();
}

/* ---------- 3. poison event: bounded retries then dead-letter, neighbours unaffected ---------- */
console.log("\n3. poison event -> 3 attempts -> DLQ without blocking the good event");
{
  const dir = path.join(tmp, "poison");
  const journal = path.join(dir, "bus.jsonl");
  const bus = new Bus({ artifactsDir: dir, eventsFile: "bus.jsonl" });
  const c = new Consumer({
    group: "poison-probe", topics: [TOPICS.SETTLED], journal, stateDir: path.join(dir, "st"),
    pollMs: 2, maxAttempts: 3, backoffMs: 5,
    handler: async (ev) => { if (ev.payload.pipeline_id === 999) throw new Error("kicc_algorithms threw: model timeout"); },
  });
  await c.start({ handleSignals: false });
  await bus.publish(TOPICS.SETTLED, { pipeline_id: 999, value_kes: 1, commission_kes: 0, mechanism: "x", correlationId: "corr-poison" }, { correlationId: "corr-poison" });
  await bus.publish(TOPICS.SETTLED, { pipeline_id: 1000, value_kes: 2, commission_kes: 0, mechanism: "x", correlationId: "corr-good" }, { correlationId: "corr-good" });
  await bus.drain(); await bus.eventSink.flush();
  await until(() => c.stats.dlq >= 1 && c.stats.processed >= 1);
  eq("poison event retried 2 extra times (3 attempts)", c.stats.retried, 2);
  eq("poison event dead-lettered", c.stats.dlq, 1);
  eq("good event still processed (failure isolated)", c.stats.processed, 1);
  const d = c.readDlq()[0];
  truthy("DLQ row records topic + error + attempts", d && d.topic === TOPICS.SETTLED && /model timeout/.test(d.error) && d.attempts === 3);
  await c.stop();
}

/* ---------- 4. contract violation is rejected, not silently accepted ---------- */
console.log("\n4. malformed event violates the typed contract -> DLQ, counted as contract_error");
{
  const dir = path.join(tmp, "contract");
  const journal = path.join(dir, "bus.jsonl");
  const bus = new Bus({ artifactsDir: dir, eventsFile: "bus.jsonl" });
  const [alg] = buildConsumers({ bus, journal, stateRoot: path.join(dir, "offsets"), outDir: path.join(dir, "out"), pollMs: 2 });
  await alg.start({ handleSignals: false });
  await bus.publish(TOPICS.SETTLED, { pipeline_id: 7, value_kes: 5 }, { correlationId: "corr-bad" });  // commission_kes + mechanism missing
  await bus.drain(); await bus.eventSink.flush();
  await until(() => alg.stats.dlq >= 1);
  eq("contract violation dead-lettered", alg.stats.dlq, 1);
  eq("contract error counted", alg.stats.contract_errors, 1);
  truthy("DLQ names the missing fields", /contract violation/.test(String((alg.readDlq()[0]||{}).error)));
  await alg.stop();
}

/* ---------- 5. redelivery replay is idempotent + checkpoint survives a restart ---------- */
console.log("\n5. crash/restart replay: same events re-appended are skipped, checkpoint honoured");
{
  const dir = path.join(tmp, "replay");
  const journal = path.join(dir, "bus.jsonl");
  const bus = new Bus({ artifactsDir: dir, eventsFile: "bus.jsonl" });
  const stateRoot = path.join(dir, "offsets");
  const [alg1] = buildConsumers({ bus, journal, stateRoot, outDir: path.join(dir, "out"), pollMs: 2 });
  await alg1.start({ handleSignals: false });
  for (const id of [11, 12]) await bus.publish(TOPICS.SETTLED, { pipeline_id: id, value_kes: id, commission_kes: 1, mechanism: "r", correlationId: `corr-r-${id}` }, { correlationId: `corr-r-${id}` });
  await bus.drain(); await bus.eventSink.flush();
  await until(() => alg1.stats.processed >= 2);
  await until(() => alg1.lag === 0, 12000);
  await alg1.stop();
  const ck = JSON.parse(fs.readFileSync(path.join(stateRoot, "kicc-algorithms", "checkpoint.json"), "utf8"));
  const keys = lines(path.join(stateRoot, "kicc-algorithms", "terminal.keys"));
  eq("checkpoint ackOffset persisted to journal end", ck.ackOffset, lines(journal).length);
  eq("one idempotency key recorded per consumed event", keys.length, 2);
  eq("idempotency keys are the bus event keys", keys.slice().sort().join("|") === bus.readEvents().filter((e) => e.topic === TOPICS.SETTLED).map((e) => e.idempotencyKey).sort().join("|"), true);

  // simulate the publisher redelivering the whole journal after a crash
  const dup = lines(journal).map((l) => JSON.parse(l)).map((e) => JSON.stringify({ ...e, id: e.id + "-r", ts: new Date().toISOString() }));
  fs.appendFileSync(journal, dup.join("\n") + "\n");
  const [alg2] = buildConsumers({ bus, journal, stateRoot, outDir: path.join(dir, "out"), pollMs: 2 });
  await alg2.start({ handleSignals: false });
  await until(() => alg2.stats.deduped >= 2 || alg2.stats.processed >= 1);
  eq("redelivered events reprocessed", alg2.stats.processed - alg2.stats.dlq, 0);
  eq("redelivery skipped by idempotency key", alg2.stats.deduped, 2);
  await alg2.stop();
}

/* ---------- 6. graceful shutdown of the real supervisor entry point ---------- */
console.log("\n6. graceful shutdown: SIGTERM drains and exits 0");
{
  const dir = path.join(tmp, "sig");
  const journal = path.join(dir, "bus.jsonl");
  const bus = new Bus({ artifactsDir: dir, eventsFile: "bus.jsonl" });
  await bus.publish(TOPICS.SETTLED, { pipeline_id: 42, value_kes: 5, commission_kes: 1, mechanism: "sig", correlationId: "corr-sig" }, {});
  await bus.drain(); await bus.eventSink.flush();
  const child = spawn(process.execPath, ["consumers/run_consumers.mjs", "--only=ml-engine"], {
    cwd: path.resolve("."),
    env: { ...process.env, PIPELINE_BUS_JOURNAL: journal, CONSUMER_STATUS_PORT: "8799", MOCK_MODE: "true" },
  });
  let out = "";
  child.stdout.on("data", (d) => { out += String(d); });
  child.stderr.on("data", (d) => { out += String(d); });
  const up = await until(() => /kicc-bus-consumers up/.test(out), 8000);
  truthy("supervisor entry point started the ml-engine consumer", up);
  let res = null;
  try { res = await (await fetch("http://127.0.0.1:8799/api/consumers/status")).json(); } catch { res = null; }
  truthy("status endpoint reports the consumer group", res && res.ok === true && res.consumers[0].group === "ml-engine");
  truthy("status endpoint reports its live checkpoint/offset", res && typeof res.consumers[0].ack_offset === "number");
  child.kill("SIGTERM");
  const code = await new Promise((r) => child.on("exit", (c) => r(c)));
  eq("SIGTERM exit code", code, 0);
  truthy("shutdown was graceful (drained, not killed)", /SIGTERM received - draining/.test(out));
}

console.log(`\n${pass} passed, ${fail} failed`);
process.exit(fail ? 1 : 0);
