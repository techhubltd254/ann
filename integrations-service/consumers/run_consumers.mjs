// consumers/run_consumers.mjs — supervisor/docker entry point.
//   node consumers/run_consumers.mjs                    -> both consumers in one process
//   node consumers/run_consumers.mjs --only=ml-engine   -> one consumer per process (supervisor)
// Exposes GET /api/consumers/status so the Mother Admin can monitor lag, offsets,
// dead letters and retry counts for the kicc_algorithms and ml-engine consumers.
import http from "http";
import { Bus } from "../lib/bus.mjs";
import { buildConsumers, CONSUMER_SPEC } from "./registry.mjs";

const only = (process.argv.find((a) => a.startsWith("--only=")) || "").split("=")[1];
const bus = new Bus({});
const all = buildConsumers({ bus, onIdle: () => bus.eventSink.flush() });
const consumers = only ? all.filter((c) => c.group === only) : all;
if (!consumers.length) {
  console.error(`unknown consumer group '${only}' - known: ${CONSUMER_SPEC.map((s) => s.group).join(", ")}`);
  process.exit(2);
}

await Promise.all(consumers.map((c) => c.start({ handleSignals: false })));

// Flush is event-driven via onIdle() — no timer. The 20ms setInterval was
// a self-DDoS (50 TiDB writes/sec per consumer, 1,698 CPU-min in 4 days,
// 1.1 GB log). The onIdle callback fires after each batch processed.

const port = Number(process.env.CONSUMER_STATUS_PORT || 8791);
const server = http.createServer((req, res) => {
  const url = new URL(req.url, `http://${req.headers.host || "localhost"}`);
  if (url.pathname === "/api/consumers/status" || url.pathname === "/health") {
    const body = {
      ok: true, service: "kicc-bus-consumers", uptime_s: Math.round(process.uptime()),
      spec: CONSUMER_SPEC, consumers: consumers.map((c) => c.metrics()), bus: bus.stats,
    };
    res.writeHead(200, { "content-type": "application/json" });
    res.end(JSON.stringify(body, null, 2));
    return;
  }
  res.writeHead(404, { "content-type": "text/plain" });
  res.end("not found");
});
server.listen(port, "127.0.0.1", () => {
  console.log(`kicc-bus-consumers up: ${consumers.map((c) => c.group).join(", ")} | journal=${consumers[0].journal} | status :${port}/api/consumers/status`);
});

let shuttingDown = false;
for (const sig of ["SIGTERM", "SIGINT"]) {
  process.on(sig, async () => {
    if (shuttingDown) return;
    shuttingDown = true;
    console.log(`kicc-bus-consumers: ${sig} received - draining ${consumers.length} consumer(s)`);
    await Promise.all(consumers.map((c) => c.stop()));
    server.close();
    process.exit(0);
  });
}
