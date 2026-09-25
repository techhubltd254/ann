// lib/consumer.mjs — durable, at-least-once consumer for the KICC pipeline bus.
//
// The bus journal (run/artifacts/bus.jsonl) IS the queue: the publisher appends one
// JSON event per line and every consumer tails that file from its OWN checkpoint in
// its OWN state directory. A crashed or restarted consumer resumes exactly where it
// stopped instead of losing the pipeline chain.
//
// Guarantees
//   ack          checkpoint advances only AFTER the handler has resolved
//   nack/retry   bounded retries with exponential backoff, then dead-letter (never dropped)
//   idempotency  every terminal event key is persisted, so a redelivery is skipped
//   backpressure at most maxInFlight handlers run at once; the tail pauses instead
//   shutdown     SIGTERM/SIGINT drain in-flight work, flush state, exit 0
import fs from "fs";
import path from "path";
import crypto from "crypto";
import { ROOT, ARTIFACTS, log } from "./core.js";

const sleep = (ms) => new Promise((r) => setTimeout(r, ms));
const say = (m) => (log && typeof log.info === "function" ? log.info(m) : console.log(m));
const warn = (m) => (log && typeof log.warn === "function" ? log.warn(m) : console.warn(m));

/** stable 40-hex idempotency digest (sha1) */
export const idemKey = (s) => crypto.createHash("sha1").update(String(s)).digest("hex");

const globToRe = (p) =>
  new RegExp("^" + String(p).replace(/[.+?^${}()|[\]\\]/g, "\\$&").replace(/\*/g, ".*") + "$");
const readJson = (f, d) => { try { return JSON.parse(fs.readFileSync(f, "utf8")); } catch { return d; } };
const readLines = (f) => { try { return fs.readFileSync(f, "utf8").split("\n").filter(Boolean); } catch { return []; } };
const writeJson = (f, o) => { fs.mkdirSync(path.dirname(f), { recursive: true }); fs.writeFileSync(f, JSON.stringify(o, null, 2)); };
const append = (f, s) => { fs.mkdirSync(path.dirname(f), { recursive: true }); fs.appendFileSync(f, s + "\n"); };

export class Consumer {
  constructor(opts = {}) {
    if (typeof opts.handler !== "function") throw new Error("Consumer requires a handler function");
    this.group = opts.group || "consumer";
    this.topicNames = opts.topics || ["*"];
    this.topics = this.topicNames.map(globToRe);
    this.handler = opts.handler;
    this.journal = opts.journal || process.env.PIPELINE_BUS_JOURNAL || path.join(ARTIFACTS, "bus.jsonl");
    this.stateDir = opts.stateDir || path.join(ROOT, "run", "offsets", this.group);
    this.maxAttempts = opts.maxAttempts ?? Number(process.env.CONSUMER_MAX_ATTEMPTS || 3);
    this.backoffMs = opts.backoffMs ?? Number(process.env.CONSUMER_BACKOFF_MS || 20);
    this.pollMs = opts.pollMs ?? 20;
    this.maxInFlight = opts.maxInFlight ?? Number(process.env.CONSUMER_MAX_IN_FLIGHT || 8);
    this.onIdle = opts.onIdle || null;
    fs.mkdirSync(this.stateDir, { recursive: true });
    this.ckptPath = path.join(this.stateDir, "checkpoint.json");
    this.terminalPath = path.join(this.stateDir, "terminal.keys");
    this.dlqPath = path.join(this.stateDir, "dlq.jsonl");
    this.acksPath = path.join(this.stateDir, "acks.jsonl");
    const ck = readJson(this.ckptPath, { group: this.group, ackOffset: 0 });
    this.ackOffset = Number(ck.ackOffset) || 0;
    this.nextRead = this.ackOffset;
    this.terminal = new Set(readLines(this.terminalPath));
    this.pendingAcked = new Set();
    this.inflight = new Map();
    this.stopping = false;
    this.loopP = null;
    this.signals = null;
    this.startedAt = null;
    this._cache = null;
    this.stats = {
      events_in: 0, filtered: 0, deduped: 0, processed: 0, acked: 0, retried: 0, dlq: 0,
      contract_errors: 0, peak_inflight: 0, restarts: 0,
    };
  }

  get lag() {
    const n = readLines(this.journal).length;
    return Math.max(0, n - this.ackOffset);
  }
  get inflightCount() { return this.inflight.size; }

  metrics() {
    return {
      group: this.group, topics: this.topicNames, journal: this.journal, state_dir: this.stateDir,
      ack_offset: this.ackOffset, lag: this.lag, inflight: this.inflight.size,
      terminal_keys: this.terminal.size, stopping: this.stopping,
      uptime_s: this.startedAt ? Math.round((Date.now() - this.startedAt) / 1000) : 0,
      ...this.stats,
    };
  }

  readRecords() {
    return readLines(this.journal).map((l) => { try { return JSON.parse(l); } catch { return null; } }).filter(Boolean);
  }
  readDlq() {
    return readLines(this.dlqPath).map((l) => { try { return JSON.parse(l); } catch { return null; } }).filter(Boolean);
  }

  async start({ handleSignals = true } = {}) {
    if (this.loopP) return this;
    this.stopping = false;
    this.startedAt = Date.now();
    if (handleSignals && !this.signals) {
      this.signals = {};
      this.signals.SIGTERM = () => { say(`consumer ${this.group}: SIGTERM received - draining`); this.stop().then(() => process.exit(0)); };
      this.signals.SIGINT = () => { say(`consumer ${this.group}: SIGINT received - draining`); this.stop().then(() => process.exit(0)); };
      process.on("SIGTERM", this.signals.SIGTERM);
      process.on("SIGINT", this.signals.SIGINT);
    }
    this.loopP = this.#loop();
    return this;
  }

  async stop() {
    if (!this.loopP) return this.metrics();
    this.stopping = true;
    await this.loopP;
    this.loopP = null;
    this.#persist();
    if (this.signals) {
      process.off("SIGTERM", this.signals.SIGTERM);
      process.off("SIGINT", this.signals.SIGINT);
      this.signals = null;
    }
    say(`consumer ${this.group}: stopped cleanly (processed=${this.stats.processed} deduped=${this.stats.deduped} dlq=${this.stats.dlq} ackOffset=${this.ackOffset})`);
    return this.metrics();
  }

  async #loop() {
    while (!this.stopping || this.inflight.size) {
      if (this.inflight.size >= this.maxInFlight) { await sleep(2); continue; }
      let batch = [];
      try { batch = this.#readNew(this.maxInFlight - this.inflight.size); } catch { batch = []; }
      if (!batch.length) {
        if (this.onIdle) { try { await this.onIdle(); } catch { /* flush is best effort */ } }
        await sleep(this.pollMs);
        continue;
      }
      for (const rec of batch) this.#dispatch(rec);
      await sleep(0);
    }
  }

  #readNew(n) {
    let stat = null;
    try { stat = fs.statSync(this.journal); } catch { return []; }
    if (!this._cache || this._cache.size !== stat.size) this._cache = { size: stat.size, lines: readLines(this.journal) };
    const lines = this._cache.lines;
    if (lines.length < this.nextRead) {
      warn(`consumer ${this.group}: journal shrank (${lines.length} < ${this.nextRead}) - restarting from 0`);
      this.stats.restarts++;
      this.nextRead = 0; this.ackOffset = 0; this.pendingAcked.clear();
    }
    const out = [];
    while (out.length < n && this.nextRead < lines.length) {
      const i = this.nextRead++;
      let ev = null;
      try { ev = JSON.parse(lines[i]); } catch { ev = null; }
      out.push({ offset: i, ev });
    }
    return out;
  }

  #dispatch({ offset, ev }) {
    if (offset < this.ackOffset) return;
    this.stats.events_in++;
    if (!ev || !this.topics.some((re) => re.test(ev.topic || ""))) { this.stats.filtered++; this.#ack(offset); return; }
    const key = ev.idempotencyKey || `line-${offset}`;
    if (this.terminal.has(key)) { this.stats.deduped++; this.#ack(offset); return; }
    const p = this.#attempt(ev, key, offset);
    this.inflight.set(offset, p);
    this.stats.peak_inflight = Math.max(this.stats.peak_inflight, this.inflight.size);
    p.finally(() => this.inflight.delete(offset));
  }

  async #attempt(ev, key, offset) {
    let lastErr = null;
    for (let a = 1; a <= this.maxAttempts; a++) {
      if (a > 1) { this.stats.retried++; await sleep(this.backoffMs * 2 ** (a - 2)); }
      try {
        await this.handler(ev, { group: this.group, attempt: a, offset });
        this.stats.processed++; this.stats.acked++;
        append(this.acksPath, JSON.stringify({ offset, topic: ev.topic, key, attempts: a, ts: new Date().toISOString() }));
        this.terminal.add(key); append(this.terminalPath, key);
        this.#ack(offset);
        return;
      } catch (e) { lastErr = e; }
    }
    this.stats.dlq++;
    if (/contract/i.test(String(lastErr && lastErr.message))) this.stats.contract_errors++;
    append(this.dlqPath, JSON.stringify({ topic: ev.topic, key, offset, attempts: this.maxAttempts, error: String((lastErr && lastErr.message) || lastErr), event: ev, ts: new Date().toISOString() }));
    this.terminal.add(key); append(this.terminalPath, key);
    warn(`consumer ${this.group}: ${ev.topic} dead-lettered after ${this.maxAttempts} attempts: ${(lastErr && lastErr.message) || lastErr}`);
    this.#ack(offset);
  }

  #ack(offset) {
    this.pendingAcked.add(offset);
    while (this.pendingAcked.has(this.ackOffset)) { this.pendingAcked.delete(this.ackOffset); this.ackOffset++; }
    this.#persist();
  }

  #persist() {
    writeJson(this.ckptPath, { group: this.group, ackOffset: this.ackOffset, updatedAt: new Date().toISOString(), journal: this.journal });
  }
}
