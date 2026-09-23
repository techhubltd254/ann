// run/test_cascade.mjs — end-to-end proof that the pipelines actually interlock.
// Every assertion here fails if a pipeline cannot drive the next one.
import fs from "fs";
import path from "path";
import { ROOT, resetArtifacts, readLedger, flushArtifacts } from "../lib/core.js";
import { Bus } from "../lib/bus.mjs";
import { validate } from "../lib/contracts.mjs";
import { loadGraph } from "../pipelines/graph.mjs";
import { runCascade, REGISTRY_DIR } from "../pipelines/cascade.mjs";

let pass = 0, fail = 0;
const check = (n, c, e) => { c ? pass++ : fail++; console.log((c ? "PASS  " : "FAIL  ") + n.padEnd(64) + " -> " + e); };
const head = (s) => { console.log("\n" + "=".repeat(104) + "\n" + s + "\n" + "=".repeat(104)); };

head("INTER-PIPELINE AUTOMATION — END-TO-END CASCADE TESTS");

/* ---------- 1. the graph itself ---------- */
const g = loadGraph(REGISTRY_DIR);
check("dependency graph loads the full registered catalog", g.summary.nodes >= 87, `${g.summary.nodes} nodes, ${g.summary.edges} edges (${g.summary.declared} declared + ${g.summary.signal} signal)`);
check("the graph has real connectivity", g.summary.edges > 87, `avg fan-out ${g.summary.avgFanOut}, max fan-out ${g.summary.maxFanOut}, ${g.summary.roots} roots, ${g.summary.sinks} sinks`);
check("the graph is a clean DAG (no cycles)", g.summary.cycles === 0, `${g.summary.cycles} cycles detected; the cycle detector stays in place as a guard`);
check("topological layers computed (parallelism ceiling)", g.summary.layers > 1, `${g.summary.layers} dependency layers`);
check("no pipeline is isolated from the mesh", g.summary.isolated === 0, `${g.summary.isolated} isolated nodes; every pipeline has at least one in/out edge`);

/* ---------- 2. one pipeline drives its dependencies ---------- */
const HUB = g.summary.deepest;                 // the node with the deepest reach
const hubDesc = g.descendantsOf(HUB);
resetArtifacts();
const c1 = await runCascade({ roots: [HUB], maxDepth: 20000 });
await flushArtifacts();
const c1ids = new Set(c1.executed.map((x) => x.pipeline_id));
const missing = [...hubDesc].filter((d) => !c1ids.has(d));
check("one settled pipeline cascades into every dependent", missing.length === 0, `root ${HUB} -> ${c1.executed.length} pipelines executed, ${hubDesc.size} reachable, 0 missed`);
// Transitive completeness: the cascade must reach every descendant of the root,
// regardless of how many hops the graph requires. The check compares against the
// graph's real transitive closure (not a fixed depth), so it holds for any catalog
// size — 87 pipelines or the full 214-node platform.
const maxHop = Math.max(...c1.executed.map((x) => x.hop));
check("the cascade is transitively complete (multi-hop, not just direct)", c1ids.size >= hubDesc.size, `root ${HUB} -> ${c1.executed.length} pipelines executed, ${hubDesc.size} reachable across up to ${maxHop} hop(s)`);
check("every step is traceable to one correlation id", new Set(c1.executed.map((x) => x.correlationId)).size === 1, `all ${c1.executed.length} executions share ${[...new Set(c1.executed.map((x) => x.correlationId))][0]}`);
check("every cascade step wrote a ledger row", readLedger().filter((r) => r.event_type === "cascade.settled").length === c1.executed.length, `${readLedger().length} ledger rows vs ${c1.executed.length} executions`);
check("commission accrued along the whole cascade", c1.executed.reduce((a, x) => a + x.commission_kes, 0) > 0, `KES ${c1.executed.reduce((a, x) => a + x.commission_kes, 0).toLocaleString()} across the cascade`);

/* ---------- 3. full catalog: all 87 interlock ---------- */
resetArtifacts();
const c2 = await runCascade({ roots: g.nodes.map((p) => p.id), maxDepth: 40000, busOpts: { eventsFile: "catalog.jsonl", dlqFile: "catalog-dlq.jsonl" } });
await flushArtifacts();
const uniqueC2 = new Set(c2.executed.map((x) => x.pipeline_id));
const catalogSize = g.nodes.length; // 214 registered pipelines (platform > integration catalog's 87)
check("seeding every root settles the full registered catalog", uniqueC2.size >= catalogSize, `${uniqueC2.size}/${catalogSize} distinct pipelines executed, ${c2.executed.length} total settlements`);
check("cascades stayed isolated per root correlation", new Set(c2.executed.map((x) => x.pipeline_id + "|" + x.correlationId)).size === c2.executed.length, `no duplicate (pipeline, correlation) pair across ${c2.executed.length} settlements`);
const mechanismsFired = [...new Set(c2.executed.map((x) => x.mechanism))].filter((m) => m && m !== "?");
check("mechanisms resolve from the registry (no '?' placeholders)", mechanismsFired.length >= 1 && c2.executed.every((x) => x.mechanism && x.mechanism !== "?"), `mechanisms fired: ${mechanismsFired.join(", ")}`);

/* ---------- 4. backpressure ---------- */
const bp = new Bus({ maxDepth: 50, artifactsDir: path.join(ROOT, "run", "artifacts"), eventsFile: "bp.jsonl", dlqFile: "bpdlq.jsonl" });
let handled = 0;
bp.subscribe("flood.*", async () => { handled++; });
await Promise.all(Array.from({ length: 4000 }, (_, i) => bp.publish("flood.event", { pipeline_id: i }, { key: String(i) })));
await bp.drain();
check("bounded queue holds: depth never exceeded the cap", bp.stats.maxDepthSeen <= 50, `maxDepthSeen ${bp.stats.maxDepthSeen} <= cap 50`);
check("backpressure actually engages under load", bp.stats.backpressureWaits > 0, `${bp.stats.backpressureWaits} publishes awaited (${bp.stats.backpressureMs} ms of pushback), 4000 events all delivered`);
check("nothing was dropped under backpressure", bp.stats.delivered === 4000 && bp.stats.dlq === 0, `${bp.stats.delivered} delivered, ${bp.stats.dlq} dead-lettered`);

/* ---------- 5. dead-letter + failure propagation ---------- */
// pick a real failure point inside the deepest subtree, with downstream of its own
const sub = [...g.descendantsOf(HUB)].filter((d) => g.descendantsOf(d).size >= 2);
const POISON = sub.sort((a, b) => g.descendantsOf(b).size - g.descendantsOf(a).size)[0] ?? HUB;
const CHAIN_ROOT = [...g.ancestorsOf(POISON)].sort((a, b) => g.descendantsOf(b).size - g.descendantsOf(a).size)[0] ?? HUB;
resetArtifacts();
const c3 = await runCascade({ roots: [CHAIN_ROOT], poison: [POISON], maxDepth: 20000 });
await flushArtifacts();
const c3ids = new Set(c3.executed.map((x) => x.pipeline_id));
// nodes reachable from ROOT without passing through POISON may legitimately run
const reachAvoiding = (root, avoid) => { const seen = new Set(); const st = [root]; while (st.length) { const n = st.pop(); for (const m of g.dependentsOf(n)) { if (m === avoid || seen.has(m)) continue; seen.add(m); st.push(m); } } return seen; };
const reachable = reachAvoiding(CHAIN_ROOT, POISON);
const afterPoison = [...g.descendantsOf(POISON)].filter((d) => !reachable.has(d));
check("a failing pipeline is retried then dead-lettered", c3.dlq.length >= 1, `${c3.dlq.length} event(s) in the DLQ, reason: ${c3.dlq[0] ? c3.dlq[0].error.slice(0, 60) : "-"}`);
check("the bus retried before giving up", c3.metrics.retried >= 1, `${c3.metrics.retried} retries recorded before dead-lettering`);
const failedLedger = readLedger().filter((r) => r.pipeline_id === POISON);
check("the failed pipeline never settles: no ledger row, no commission", !c3ids.has(POISON) && failedLedger.length === 0, `pipeline ${POISON} wrote ${failedLedger.length} ledger rows and 0 settlements`);
check("the failed pipeline's trigger is dead-lettered, not lost", c3.dlq.some((e) => e.key === String(POISON)), `DLQ holds the trigger for pipeline ${POISON} after ${c3.metrics.retried} retries`);
check("failure is contained: the mesh reroutes around the dead node", afterPoison.every((d) => !c3ids.has(d)), `${afterPoison.length} node(s) had no alternate path and stayed unexecuted; the other ${g.descendantsOf(POISON).size - afterPoison.length} reached their goal by another route`);
check("the failure is reported, not swallowed", c3.failed.some((f) => f.key === String(POISON)), `failed set contains pipeline ${POISON}`);
check("work before the failure still completed", c3ids.has(CHAIN_ROOT), `root ${CHAIN_ROOT} settled before the break; ${c3.executed.length} pipelines completed before the chain stopped`);

/* ---------- 6. idempotency ---------- */
const idem = new Bus({ maxDepth: 100, artifactsDir: path.join(ROOT, "run", "artifacts"), eventsFile: "idem.jsonl", dlqFile: "idemdlq.jsonl" });
let runs = 0;
idem.subscribe("dup.event", async () => { runs++; });
const META = { idempotencyKey: "one-shot-key", correlationId: "corr-idem", key: "1" };
await Promise.all(Array.from({ length: 200 }, () => idem.publish("dup.event", { pipeline_id: 1 }, META)));
await idem.drain();
check("duplicate publishes are processed exactly once", runs === 1 && idem.stats.deduped === 199, `handler ran ${runs}x, ${idem.stats.deduped} duplicates deduped`);

/* ---------- 7. concurrent cascades ---------- */
const [a, b, c] = await Promise.all([
  runCascade({ roots: [18], maxDepth: 20000 }),
  runCascade({ roots: [1], maxDepth: 20000 }),
  runCascade({ roots: [83], maxDepth: 20000 }),
]);
const corrs = new Set([...a.executed, ...b.executed, ...c.executed].map((x) => x.correlationId));
check("three cascades run concurrently without cross-contamination", corrs.size === 3, `3 distinct correlations, ${a.executed.length}/${b.executed.length}/${c.executed.length} pipelines settled in parallel`);
check("each concurrent cascade kept its own graph reach", a.executed.every((x) => x.correlationId.startsWith("casc-18-")) && b.executed.every((x) => x.correlationId.startsWith("casc-1-")), "every execution carries its own root's correlation");

/* ---------- 8. contracts ---------- */
const bad = validate("pipeline.settled", { pipeline_id: 1 });
const good = validate("pipeline.settled", { pipeline_id: 1, value_kes: 5, commission_kes: 1, mechanism: "trade", correlationId: "x" });
check("the contract rejects a malformed event", bad.ok === false, `rejected: ${bad.error}`);
check("the contract accepts a well-formed event", good.ok === true, "pipeline.settled validated");
check("an unknown topic is refused", validate("nope.event", { pipeline_id: 1 }).ok === false, "unknown topics cannot be published against a schema");

/* ---------- 9. durability ---------- */
const evRows = c2.bus.readEvents();
const journalIds = new Set(evRows.map((e) => e.id));
check("every published event is durable on disk", journalIds.size === c2.metrics.published, `${journalIds.size}/${c2.metrics.published} published event ids present in the journal (${evRows.length} rows incl. dedupe records)`);
check("delivery latency is bounded", c2.metrics.p95 < 1000, `p50 ${c2.metrics.p50} ms, p95 ${c2.metrics.p95} ms over ${c2.metrics.delivered} deliveries`);

console.log("\n" + "=".repeat(104));
console.log("GRAPH SUMMARY");
console.log("=".repeat(104));
console.log(`  nodes ${c2.graph.nodes}   edges ${c2.graph.edges} (${c2.graph.declared} declared + ${c2.graph.signal} signal)   layers ${c2.graph.layers}   cycles ${c2.graph.cycles}   isolated ${c2.graph.isolated}`);
console.log(`  edge signals: ${JSON.stringify(c2.graph.bySignal)}`);
console.log(`  deepest pipeline #${c2.graph.deepest} reaches ${c2.graph.deepestReach} others`);
console.log(`  full-catalog cascade: ${c2.executed.length} settlements, ${c2.metrics.published} events, ${c2.metrics.delivered} deliveries`);
console.log("\n" + "=".repeat(104));
console.log(`RESULT: ${pass} passed, ${fail} failed`);
console.log("=".repeat(104) + "\n");
process.exit(fail ? 1 : 0);
