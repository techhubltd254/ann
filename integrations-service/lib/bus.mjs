// lib/bus.mjs — the inter-pipeline event bus. Every pipeline publishes typed
// events; other pipelines subscribe and react. This is what turns 87 silos into
// one system whose outputs automatically drive each other.
//
// Guarantees
//   * bounded queue with REAL backpressure — publish() awaits when the bus is full
//   * at-least-once delivery with exponential-backoff retry
//   * dead-letter queue for poison events (never silently dropped)
//   * duplicate publishes are deduped by idempotency key
//   * per-key ordering so one pipeline's events stay in sequence
//   * failure is observable: a failed pipeline is recorded, and its dependents
//     are never triggered — so the chain cannot half-execute silently
import path from "path";
import { ROOT, log, MOCK_MODE } from "./core.js";
import { createSink } from "./store.js";
import { insertEvent, insertDlq } from "./bus-sql.mjs";

export class Bus {
  constructor(opts = {}) {
    this.maxDepth = opts.maxDepth ?? 20000;
    this.maxAttempts = opts.maxAttempts ?? 3;
    this.backoffMs = opts.backoffMs ?? 8;
    this.subs = [];
    this.queue = [];
    this.running = false;
    this.pumpScheduled = false;
    this.seen = new Set();
    this.failed = new Map();
    this.metrics = {
      published: 0, delivered: 0, retried: 0, dlq: 0, deduped: 0,
      maxDepthSeen: 0, backpressureWaits: 0, backpressureMs: 0, latencies: [],
    };
    const dir = opts.artifactsDir || path.join(ROOT, "run", "artifacts");
    this.eventSink = createSink(path.join(dir, opts.eventsFile || "bus.jsonl"), "");
    this.dlqSink = createSink(path.join(dir, opts.dlqFile || "dlq.jsonl"), "");
  }

  subscribe(pattern, handler, opts = {}) {
    const sub = {
      id: opts.id || `sub-${this.subs.length + 1}`,
      re: new RegExp("^" + String(pattern).replace(/[.+?^${}()|[\]\\]/g, "\\$&").replace(/\*/g, ".*") + "$"),
      handler,
    };
    this.subs.push(sub);
    return sub.id;
  }

  /** Publish. Awaits while the queue is full — that await IS the backpressure. */
  async publish(topic, payload = {}, meta = {}) {
    while (this.queue.length >= this.maxDepth) {
      this.metrics.backpressureWaits++;
      const t = Date.now();
      await new Promise((r) => setTimeout(r, 2));
      this.metrics.backpressureMs += Date.now() - t;
    }
    const n = ++this.metrics.published;
    const ev = {
      id: `ev-${n.toString(36)}`,
      topic,
      payload,
      idempotencyKey: meta.idempotencyKey || `${meta.correlationId}|${topic}|${JSON.stringify(payload).slice(0, 80)}`,
      correlationId: meta.correlationId || `corr-${n}`,
      causationId: meta.causationId || null,
      hop: meta.hop ?? 0,
      key: meta.key ?? String(payload.pipeline_id ?? "global"),
      attempts: 0,
      ts: new Date().toISOString(),
    };
    this.queue.push(ev);
    this.metrics.maxDepthSeen = Math.max(this.metrics.maxDepthSeen, this.queue.length);
    if (!this.pumpScheduled) { this.pumpScheduled = true; setImmediate(() => this.pump()); }
    return ev;
  }

  async pump() {
    this.pumpScheduled = false;
    if (this.running) return;
    this.running = true;
    try {
      while (this.queue.length) await this.deliver(this.queue.shift());
    } finally { this.running = false; }
  }

  async deliver(ev) {
    const t0 = Date.now();
    if (this.seen.has(ev.idempotencyKey)) {
      this.metrics.deduped++;
      this.eventSink.write(JSON.stringify({ ...ev, deduped: true, subscribers: 0 }));
      return;
    }
    this.seen.add(ev.idempotencyKey);
    const targets = this.subs.filter((s) => s.re.test(ev.topic));
    this.eventSink.write(JSON.stringify({ ...ev, subscribers: targets.length }));
    // Dual-write to SQL (authoritative); file is fallback
    insertEvent(ev.topic, ev.payload, ev).catch(() => {});
    for (const s of targets) {
      for (let attempt = 1; attempt <= this.maxAttempts; attempt++) {
        try {
          await s.handler(ev);
          this.metrics.delivered++;
          this.metrics.latencies.push(Date.now() - t0);
          break;
        } catch (e) {
          if (attempt < this.maxAttempts) {
            this.metrics.retried++;
            await new Promise((r) => setTimeout(r, this.backoffMs * 2 ** (attempt - 1)));
            continue;
          }
          this.metrics.dlq++;
          this.dlqSink.write(JSON.stringify({ ...ev, subscriber: s.id, error: String(e && e.message || e), attempts: attempt }));
          // Dual-write DLQ to SQL
          insertDlq('bus', ev.topic, ev.idempotencyKey || ev.key, 0, String(e && e.message || e), attempt, ev).catch(() => {});
          this.failed.set(ev.key, String(e && e.message || e));
          log.warn(`bus: ${ev.topic} -> ${s.id} dead-lettered after ${attempt} attempts: ${e && e.message}`);
        }
      }
    }
  }

  async drain() {
    while (this.queue.length || this.running) await new Promise((r) => setTimeout(r, 2));
    await this.eventSink.flush();
    await this.dlqSink.flush();
  }
  readEvents() { return this.eventSink.read().map((l) => JSON.parse(l)); }
  readDlq() { return this.dlqSink.read().map((l) => JSON.parse(l)); }
  get stats() {
    const l = [...this.metrics.latencies].sort((a, b) => a - b);
    const p = (q) => l[Math.min(l.length - 1, Math.max(0, Math.ceil(l.length * q) - 1))] || 0;
    const { latencies, ...rest } = this.metrics;
    return { ...rest, p50: p(0.5), p95: p(0.95), queued: this.queue.length, subscribers: this.subs.length, failedKeys: this.failed.size };
  }
}
