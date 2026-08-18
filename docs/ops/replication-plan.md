# DB Replication Plan (TiDB Cloud Serverless)

> Status: **Plan** (not yet implemented) · Applies to: `kicc` DB on TiDB Cloud serverless
> (gateway01.eu-central-1.prod.aws.tidbcloud.com:4000)

## Constraint

TiDB Cloud **serverless** has no classic MySQL primary/replica topology and no
per-node read replicas. Reads and writes hit the same elastic cluster. The
supported replication primitive is **Changefeed (TiCDC)** — a continuous
change stream of committed transactions that can be delivered to:

- another TiDB/MySQL-compatible database,
- Kafka / Pulsar / Redpanda,
- object storage (S3/R2) as CSV/JSON (usable for a data lake / batch reporting).

## Current state (why a replica is optional today)

The platform is early-stage: the largest table is ~2.2k rows (~0.1 MB data).
Reporting is light and already mitigated by:

- `CachePublicResponse` middleware → Redis response cache for public reads
  (counties, exhibitions, venues, booths).
- `analytics_rollups` table fed by `analytics:trends` (ComputeTrends) nightly.
- Redis as session/cache store.

A read replica would add cost and ops burden with no measurable benefit at this
data volume. **Recommendation: defer implementation until a trigger is hit.**

## Trigger conditions (implement when ANY is true)

- Largest table > 5M rows OR total DB > 10 GB.
- `pulse` shows sustained DB query latency p95 > 300ms for > 1 week.
- Heavy ad-hoc/reporting queries (full scans over orders/products) begin to
  contend with request traffic (watch TiDB Cloud "SQL" + "Key Visualizer").
- Any analytics workload is added that scans more than ~1M rows per run.

## Option A — Reporting replica via Changefeed (preferred)

1. Provision a second TiDB Cloud serverless cluster (or a small managed MySQL,
   e.g. PlanetScale/DO MySQL) as the **reporting sink**.
2. In TiDB Cloud console → cluster → **Changefeed** → add a new changefeed with
   sink = reporting DB. Filter to hot read/reporting tables only:
   `orders, order_items, products, bookings, tickets, payments, settlement_*,
   analytics_events, page_views, analytics_rollups, county_*`.
   (Exclude transient/pulse/queue tables.)
3. Changefeed does the initial snapshot then streams increments (TiCDC guarantees
   at-least-once, second-level RPO).
4. Add a `mysql_reporting` connection in `config/database.php` pointing at the
   sink, and route **read-only, non-critical** queries to it:
   `DB::connection('mysql_reporting')->select(...)`.

### Laravel wiring (when Option A is enabled)

Add to `config/database.php` connections:

```php
'reporting' => [
    'driver' => 'mysql',
    'host' => env('REPORTING_DB_HOST'),
    'port' => env('REPORTING_DB_PORT', '4000'),
    'database' => env('REPORTING_DB_DATABASE', 'kicc_reporting'),
    'username' => env('REPORTING_DB_USERNAME'),
    'password' => env('REPORTING_DB_PASSWORD'),
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix' => '',
    'strict' => true,
    'engine' => null,
],
```

## Option B — Batch reporting extract (no real-time)

Use the existing hourly/daily mydumper backup → R2 (see `dr-runbook.md`) as the
data source:

- Nightly: restore `db-backups/daily/*.tar` into a scratch DB, run rollups, then
  drop it. Sufficient for daily dashboards (matches `analytics:trends` cadence).
- Zero ongoing sync infra; only as good as the last backup (≤ 24h stale).

## Option C — Sharded connections (already scaffolded)

`config/database.php` already defines `shard_0..shard_3` connections. These are
NOT replicas — they target separate `kicc_shard_N` databases. They exist for the
100K-concurrent horizontal-scaling blueprint. Do not confuse with a read replica.

## Decision (checklist on next planning review)

- [ ] Data volume below triggers → **keep current architecture**, re-evaluate
      at next review.
- [ ] If reporting scans grow → implement **Option A** (Changefeed → reporting
      sink), wire `reporting` connection, route read-only queries there.
- [ ] If only batch dashboards needed → **Option B** (extract from R2 backups).

## Monitoring hooks

- TiDB Cloud → **SQL** tab: filter by `SELECT` statements with high scan rows.
- `dba:index-audit` (weekly, Sun 05:00) already reports table sizes — a table
  crossing 5M rows appears there first.
- `analytics_rollups` growth rate in `pulse` / Grafana.