// lib/store.js — the scalability primitives the hot path was missing.
//
// The original code appended every event and every ledger row with its own
// fs.appendFileSync call. That is fine for a demo but it is the first thing that
// breaks under load:
//   1. one syscall per line, so throughput is capped by syscall cost,
//   2. concurrent writers can interleave mid-line and corrupt the file,
//   3. there is no way to bound how hard a provider gets hammered,
//   4. a retried request has no dedupe, so a double-submit becomes a double-charge.
// Everything below fixes one of those four.
import fs from "fs";
import path from "path";

/* --------------------------------------------------------------- sinks ---- */
/**
 * Coalesced append sink.
 * - lines are buffered and written in ONE syscall per event-loop tick
 *   (1,000 rows cost ~1 write, not 1,000)
 * - the buffer is flushed synchronously on read, so a reader can never observe a
 *   half-written file or miss a row it just wrote
 * - a line is assembled completely before it is queued, so two writers can never
 *   interleave inside a row
 * This is the drop-in replacement for the per-line fs.appendFileSync in core.js.
 */
export function createSink(file, header = "") {
  fs.mkdirSync(path.dirname(file), { recursive: true });
  if (!fs.existsSync(file)) fs.writeFileSync(file, header);
  let buf = [];
  let scheduled = false;
  let written = 0;

  const drainNow = () => {
    scheduled = false;
    if (!buf.length) return;
    const chunk = buf.join("");
    written += buf.length;
    buf = [];
    fs.appendFileSync(file, chunk);        // one syscall for the whole tick's worth of rows
  };

  return {
    path: file,
    write(line) {
      buf.push(line.endsWith("\n") ? line : line + "\n");
      if (!scheduled) { scheduled = true; setImmediate(drainNow); }
      return true;
    },
    flush() { if (scheduled || buf.length) drainNow(); return Promise.resolve(); },
    get written() { return written; },
    get pending() { return buf.length; },
    read() {
      if (scheduled || buf.length) drainNow();   // guarantee the reader sees its own writes
      if (!fs.existsSync(file)) return [];
      return fs.readFileSync(file, "utf8").split("\n").filter(Boolean);
    },
    reset() {
      buf = []; written = 0; scheduled = false;
      fs.writeFileSync(file, header);
    },
  };
}

/* -------------------------------------------------------- rate limiting ---- */
/**
 * Token bucket. Providers publish a request-per-second ceiling; blowing past it
 * is how you get 429s and then a blocked account. Every live call can pass
 * through this, so the platform cannot outrun a provider no matter how many
 * orders land at once.
 */
export function rateLimiter(perSec, burst = Math.max(1, Math.ceil(perSec))) {
  let tokens = burst;
  let last = Date.now();
  let admitted = 0, delayed = 0, waitedMs = 0;
  const queue = [];
  let pumping = false;

  const refill = () => {
    const now = Date.now();
    tokens = Math.min(burst, tokens + ((now - last) / 1000) * perSec);
    last = now;
  };

  // A single pump drains the queue in arrival order. The earlier version had every
  // waiting caller sleep and then admit itself, which let the whole batch through
  // at once (600 requests observed at 40,000/s against a 200/s cap). Re-checking
  // the bucket inside one serialized loop is what actually enforces the ceiling.
  async function pump() {
    if (pumping) return;
    pumping = true;
    try {
      while (queue.length) {
        refill();
        if (tokens < 1) {
          const waitMs = Math.max(1, Math.ceil(((1 - tokens) / perSec) * 1000));
          await new Promise((r) => setTimeout(r, waitMs));
          continue;
        }
        tokens -= 1;
        const { resolve, t0 } = queue.shift();
        const waited = Date.now() - t0;
        admitted++;
        if (waited > 0) { delayed++; waitedMs += waited; }
        resolve(waited);
      }
    } finally { pumping = false; }
  }

  return {
    acquire() { return new Promise((resolve) => { queue.push({ resolve, t0: Date.now() }); pump(); }); },
    get available() { refill(); return Math.floor(tokens); },
    get stats() { return { admitted, delayed, waitedMs, queued: queue.length }; },
  };
}

/* --------------------------------------------------------- idempotency ---- */
/**
 * Exactly-once keyed work. The same idempotency key fired 500 times concurrently
 * runs the work once and replays the stored result to the other 499 callers.
 * This is what stops a buyer double-tapping "Pay" from becoming two captures.
 */
export function idempotencyCache() {
  const seen = new Map();
  let hits = 0, misses = 0;
  return {
    async once(key, fn) {
      if (seen.has(key)) { hits++; const r = await seen.get(key); return { ...r, replayed: true }; }
      misses++;
      const p = (async () => fn())();
      seen.set(key, p);
      try { const r = await p; return { ...r, replayed: false }; }
      catch (e) { seen.delete(key); throw e; }
    },
    has: (key) => seen.has(key),
    get stats() { return { unique: misses, replayed: hits, tracked: seen.size }; },
    reset() { seen.clear(); hits = 0; misses = 0; },
  };
}

/* ---------------------------------------------------------- concurrency ---- */
/** Bounded worker pool. Replaces serial for-loops so a batch is not one-at-a-time. */
export async function pool(items, concurrency, worker) {
  const out = new Array(items.length);
  const limit = Math.max(1, Math.min(concurrency, items.length || 1));
  let cursor = 0;
  await Promise.all(Array.from({ length: limit }, async () => {
    for (;;) {
      const i = cursor++;
      if (i >= items.length) return;
      out[i] = await worker(items[i], i);
    }
  }));
  return out;
}

/* -------------------------------------------------------------- metrics ---- */
export function percentile(samples, p) {
  if (!samples.length) return 0;
  const s = [...samples].sort((a, b) => a - b);
  const idx = Math.min(s.length - 1, Math.max(0, Math.ceil((p / 100) * s.length) - 1));
  return s[idx];
}
export function summarize(samples) {
  const sum = samples.reduce((a, b) => a + b, 0);
  return {
    n: samples.length,
    mean: +(sum / (samples.length || 1)).toFixed(3),
    p50: percentile(samples, 50),
    p95: percentile(samples, 95),
    p99: percentile(samples, 99),
    max: Math.max(...samples, 0),
  };
}
export const fmt = (n) => Number(n).toLocaleString("en-KE");
