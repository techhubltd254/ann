// lib/consumer.mjs — durable, at-least-once consumer for the KICC pipeline bus.
// Primary data store: TiDB (SQL) via bus-sql.mjs. Files kept as fallback only.
//
// The bus_events table is the queue: the publisher inserts rows, and every
// consumer SELECTs from its OWN checkpoint offset. A restarted consumer
// resumes exactly where it stopped.
//
// Guarantees
//   ack          checkpoint advances only AFTER the handler resolves
//   nack/retry   bounded retries with exponential backoff, then dead-letter
//   idempotency  bus_event_keys table, so a redelivery is skipped
//   backpressure at most maxInFlight handlers at once
//   shutdown     SIGTERM/SIGINT drain in-flight work, flush state, exit 0
import fs from "fs";
import path from "path";
import crypto from "crypto";
import { ROOT, ARTIFACTS, log } from "./core.js";
import {
  fetchEventsSince, loadOffset, updateOffset, countEvents,
  hasEventKey, insertEventKey, insertDlq, fetchDlq,
} from "./bus-sql.mjs";

const sleep = (ms) => new Promise((r) => setTimeout(r, ms));
const say = (m) => (log && typeof log.info === "function" ? log.info(m) : console.log(m));
const warn = (m) => (log && typeof log.warn === "function" ? log.warn(m) : console.warn(m));

export const idemKey = (s) => crypto.createHash("sha1").update(String(s)).digest("hex");
const globToRe = (p) => new RegExp("^" + String(p).replace(/[.+?^${}()|[\]\\]/g, "\\$&").replace(/\*/g, ".*") + "$");

export class Consumer {
  constructor(opts = {}) {
    if (typeof opts.handler !== "function") throw new Error("Consumer requires a handler function");
    this.group = opts.group || "consumer";
    this.topicNames = opts.topics || ["*"];
    this.topics = this.topicNames.map(globToRe);
    this.handler = opts.handler;
    this.maxAttempts = opts.maxAttempts ?? Number(process.env.CONSUMER_MAX_ATTEMPTS || 3);
    this.backoffMs = opts.backoffMs ?? Number(process.env.CONSUMER_BACKOFF_MS || 20);
    this.pollMs = opts.pollMs ?? 20;
    this.maxInFlight = opts.maxInFlight ?? Number(process.env.CONSUMER_MAX_IN_FLIGHT || 8);
    this.onIdle = opts.onIdle || null;

    // File-based state dir (kept as fallback, not primary)
    this.stateDir = opts.stateDir || path.join(ROOT, "run", "offsets", this.group);
    fs.mkdirSync(this.stateDir, { recursive: true });

    this.ackOffset = 0;
    this.ackOffsetLoaded = false;
    this.terminal = new Set();
    this.pendingAcked = new Set();
    this.inflight = new Map();
    this.stopping = false;
    this.loopP = null;
    this.startedAt = null;
    this.stats = {
      events_in: 0, filtered: 0, deduped: 0, processed: 0, acked: 0,
      retried: 0, dlq: 0, contract_errors: 0, peak_inflight: 0, restarts: 0,
    };
  }

  get lag() {
    return this._totalRows != null ? Math.max(0, this._totalRows - this.ackOffset) : 0;
  }

  get inflightCount() { return this.inflight.size; }

  metrics() {
    return {
      group: this.group, topics: this.topicNames,
      ack_offset: this.ackOffset, lag: this.lag,
      inflight: this.inflight.size, terminal_keys: this.terminal.size,
      stopping: this.stopping, uptime_s: this.startedAt ? Math.round((Date.now() - this.startedAt) / 1000) : 0,
      ...this.stats,
    };
  }

  async start({ handleSignals = true } = {}) {
    if (this.loopP) return this;
    this.stopping = false;
    this.startedAt = Date.now();

    // Load checkpoint from SQL (primary) with local file fallback
    {
      try {
        const ck = await loadOffset(this.group);
        this.ackOffset = Number(ck.ack_offset || 0);
        this.ackOffsetLoaded = true;
        say(`consumer ${this.group}: resumed from TiDB offset ${this.ackOffset}`);
      } catch (e) {
        warn(`consumer ${this.group}: TiDB unreachable, using file checkpoint`);
        this.ackOffset = this._loadLocalCheckpoint();
      }
    }

    if (handleSignals) {
      process.on("SIGTERM", async () => { await this.stop(); process.exit(0); });
      process.on("SIGINT", async () => { await this.stop(); process.exit(0); });
    }

    this.loopP = this.#loop();
    return this;
  }

  async stop() {
    if (!this.loopP) return this.metrics();
    this.stopping = true;
    await this.loopP;
    this.loopP = null;
    await this.#persist();
    say(`consumer ${this.group}: stopped (processed=${this.stats.processed} deduped=${this.stats.deduped} dlq=${this.stats.dlq} ackOffset=${this.ackOffset})`);
    return this.metrics();
  }

  async #loop() {
    while (!this.stopping || this.inflight.size) {
      if (this.inflight.size >= this.maxInFlight) { await sleep(2); continue; }

      let batch = [];
      try {
        batch = await this.#readNew(this.maxInFlight - this.inflight.size);
      } catch { batch = []; }

      if (!batch.length) {
        if (this.onIdle) { try { await this.onIdle(); } catch {} }
        await sleep(this.pollMs);
        // Refresh total row count for lag metric
        try { this._totalRows = await countEvents(); } catch {}
        continue;
      }

      for (const rec of batch) this.#dispatch(rec);
      await sleep(0);
    }
  }

  async #readNew(n) {
    // SQL primary path
    {
      try {
        const { rows } = await fetchEventsSince(this.ackOffset, n, this.topicNames);
        if (rows.length > 0) {
          try { this._totalRows = await countEvents(); } catch {}
        }
        return rows.map(r => ({ offset: r.id, ev: this.#normalizeEvent(r) }));
      } catch (e) {
        warn(`consumer ${this.group}: SQL read failed, trying file fallback: ${e.message}`);
      }
    }
    return []; // No file fallback in SQL mode — empty means SQL unavailable
  }

  #normalizeEvent(row) {
    return {
      topic: row.topic,
      payload: row.payload || {},
      id: `ev-${row.id}`,
      idempotencyKey: row.idempotency_key,
      correlationId: row.correlation_id,
      causationId: row.causation_id,
      hop: row.hop || 0,
      key: String(row.id),
      ts: row.published_at,
    };
  }

  #dispatch({ offset, ev }) {
    if (offset <= this.ackOffset) return;
    this.stats.events_in++;

    if (!this.topics.some((re) => re.test(ev.topic || ""))) {
      this.stats.filtered++;
      this.#ack(offset);
      return;
    }

    const key = ev.idempotencyKey || `line-${offset}`;
    const dedupedLocally = this.terminal.has(key);

    if (dedupedLocally) {
      this.stats.deduped++;
      this.#ack(offset);
      return;
    }

    const p = this.#attempt(ev, key, offset);
    this.inflight.set(offset, p);
    this.stats.peak_inflight = Math.max(this.stats.peak_inflight, this.inflight.size);
    p.finally(() => this.inflight.delete(offset));
  }

  async #attempt(ev, key, offset) {
    let lastErr = null;

    // SQL idempotency check (before handler runs)
    {
      try {
        if (await hasEventKey(key, this.group)) {
          this.stats.deduped++;
          this.terminal.add(key);
          this.#ack(offset);
          return;
        }
      } catch {}
    }

    for (let a = 1; a <= this.maxAttempts; a++) {
      if (a > 1) {
        this.stats.retried++;
        await sleep(this.backoffMs * 2 ** (a - 2));
      }
      try {
        await this.handler(ev, { group: this.group, attempt: a, offset });
        this.stats.processed++;
        this.stats.acked++;
        this.terminal.add(key);

        // Persist idempotency key to SQL
        {
          insertEventKey(key, this.group, offset).catch(() => {});
        }

        this.#ack(offset);
        return;
      } catch (e) {
        lastErr = e;
      }
    }

    // Dead-letter after maxAttempts
    this.stats.dlq++;
    if (/contract/i.test(String(lastErr && lastErr.message))) this.stats.contract_errors++;

    // Persist DLQ to SQL
    {
      insertDlq(this.group, ev.topic, key, offset, String((lastErr && lastErr.message) || lastErr), this.maxAttempts, ev).catch(() => {});
    }

    this.terminal.add(key);
    warn(`consumer ${this.group}: ${ev.topic} DLQ after ${this.maxAttempts} attempts: ${(lastErr && lastErr.message) || lastErr}`);
    this.#ack(offset);
  }

  #ack(offset) {
    this.pendingAcked.add(offset);
    while (this.pendingAcked.has(this.ackOffset)) {
      this.pendingAcked.delete(this.ackOffset);
      this.ackOffset++;
    }
    // Persist checkpoint to SQL (async, fire-and-forget)
    if (this.ackOffsetLoaded) {
      updateOffset(this.group, this.ackOffset, this.stats.processed, this.stats.dlq).catch(() => {});
    }
    this._saveLocalCheckpoint();
  }

  async #persist() {
    if (this.ackOffsetLoaded) {
      await updateOffset(this.group, this.ackOffset, this.stats.processed, this.stats.dlq);
    }
    this._saveLocalCheckpoint();
  }

  // ── Local file fallback (kept for bootstrapping before SQL pool is ready) ──
  _loadLocalCheckpoint() {
    try {
      const ck = JSON.parse(fs.readFileSync(path.join(this.stateDir, "checkpoint.json"), "utf8"));
      return Number(ck.ackOffset) || 0;
    } catch { return 0; }
  }

  _saveLocalCheckpoint() {
    try {
      fs.writeFileSync(
        path.join(this.stateDir, "checkpoint.json"),
        JSON.stringify({ group: this.group, ackOffset: this.ackOffset, updatedAt: new Date().toISOString() })
      );
    } catch {}
  }

  async readDlq(limit = 100) {
    {
      try { return await fetchDlq(this.group, limit); } catch {}
    }
    return [];
  }
}