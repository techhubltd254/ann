# KICC Engines — Deployment Runbook (Laravel Cloud)

**Kit:** kicc-pipeline-kit v4-joiner (117 files) · **Date:** 2026-09-17
**Covers:** General Ledger + Mother-Pool engines, 50 parent pipelines, 152 subsector configs, Mother-Pipeline Joiner — on the `ann` Laravel 13 / TiDB platform deployed via Laravel Cloud.

**Grounding note:** every artisan command, migration filename, config key and env var in this runbook was grepped from the kit itself before writing (verification table in §8). Nothing here is generic boilerplate; where the kit has no path (rollback of written financial data, scheduler entries), that is stated explicitly instead of invented.

---

## 1. Pre-deploy gates (ALL must pass before any deploy)

| Gate | Action | Pass condition |
|---|---|---|
| **G1 — Credentials** | The TiDB password pasted into chat earlier is exposed. Rotate it in the TiDB Cloud console **before** this deploy. | New credentials stored only in Laravel Cloud Secrets Manager (see §3). Kit contains no credentials (verified). |
| **G2 — Syntax** | `find app config tests database -name "*.php" -exec php -l {} \;` | 0 failures (117/117 clean at kit build time). |
| **G3 — Core selftests (no DB needed)** | `php tests/selftest_engines_core.php` <br> `php tests/validate_subsector_configs.php` <br> `php tests/selftest_joiner_core.php` | Exit 0 on all three. Expected: 14/14 engine checks; 4,259 config checks; 25 joiner checks over 152 subsectors ("ALL JOINER CORE TESTS PASSED"). |
| **G4 — Registry counts** | Output of the joiner selftest header | `Discovered subsector pipelines: 152` and partition `active(131) + locked(21) + skipped(0) == 152`. If counts differ, a config file is missing — stop. |
| **G5 — Git** | Commit the kit onto the repo's default branch; tag it (e.g. `kicc-engines-v4`). | Clean diff; tag pushed — the tag is your rollback anchor (§6). |

---

## 2. Environment & config checklist

**Secrets (Laravel Cloud Secrets Manager** — encrypted, org-level, linkable to environments per [Laravel Cloud Secrets docs](https://laravel.com/cloud/docs/secrets)**):**

| Variable | Value | Notes |
|---|---|---|
| `DB_CONNECTION` | `mysql` | TiDB speaks the MySQL protocol |
| `DB_HOST` / `DB_PORT` | your TiDB host / `4000` | from Secrets Manager, never in git |
| `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | rotated values | rotated per G1 |
| `DB_CHARSET` / `DB_COLLATION` | `utf8mb4` / `utf8mb4_unicode_ci` | |
| `APP_ENV`, `APP_KEY`, cache/queue vars | as per your existing `ann` deploy | unchanged by this kit |

**Honesty note on the kit's env knobs:** `.env.example.additions` lists `KICC_HOLDBACK_RATE`, `KICC_EQUALISATION_RATE`, `KICC_DRY_RUN_DEFAULT`, but the engine code does **not** read them — holdback (10%) and equalisation (0.5%) are constants in `app/Kicc/Support/Economics.php`, and the only `config()` reads in the kit are `kicc-county-classification.*`. Treat those env vars as documentation-only until you wire them; do not expect them to change behaviour.

**Config files shipped (all loaded by Laravel automatically):** `config/kicc-pipelines.php` (50 parents), `config/kicc-engine-bindings.php` (canonical GL/pool wiring), `config/kicc-county-classification.php` (RPS/FNS weights), `config/kicc/subsectors/` (12 sector files + `index.php` loader → 152 entries). After deploy, `php artisan config:clear` (Laravel Cloud runs config caching during build — clear once if you edited configs post-build).

---

## 3. Deploy mechanics on Laravel Cloud

- Push to deploy / the **Deploy** button from the Environment or Deployments page triggers a build then a zero-downtime release; custom **deploy hooks** run commands against the deployed environment [Laravel Cloud Deployments docs](https://laravel.com/cloud/docs/deployments).
- **Build commands:** standard Laravel build (composer install, npm build, config cache) — nothing kit-specific required.
- **Deploy hook — add exactly one migration command:**
  ```
  php artisan migrate --force
  ```
  (`--force` is required in production; interactive prompts fail in deploy hooks.)
- **Queues/scheduler:** grep of the kit shows **no queue jobs, no scheduled tasks** — all KICC commands are on-demand artisan commands. Nothing to configure in Laravel Cloud's queue/scheduler settings for this deploy. If you later schedule monthly `kicc:dry-run-distribution`, add it in the Cloud scheduler then — do not assume it exists today.

---

## 4. Migration plan (13 migrations, all idempotent)

Run via the deploy hook (`php artisan migrate --force`). Every migration is guarded with `Schema::hasTable()` / `updateOrInsert` — re-running is a no-op, and existing live tables are never altered or dropped.

| # | Migration | Creates | `down()` behaviour |
|---|---|---|---|
| 1 | `2026_09_17_000001_restore_dropped_tables_safely.php` | 6 restored ecommerce tables (product_questions, wishlists, wishlist_items, rfqs, rfq_items, rfq_quotes) | **DROPS them — never run on live data** |
| 2 | `..._000002_create_core_ledger_and_mother_pool.php` | 13 core tables (GL, escrow, pools, quality, classifications, registry) | drops core tables |
| 3–11 | `..._000003` → `..._000011` | 99 pipeline tables across 9 sector clusters | drops cluster tables |
| 12 | `..._000012_upgrade_engines.php` | `gl_periods` + seeds default chart of accounts | drops `gl_periods` |
| 13 | `..._000013_create_joiner_tables.php` | `joiner_runs`, `joiner_postings`, `joiner_reconciliations` | drops joiner tables |

**Expand/contract character:** this deploy is **expand-only** — 115+3 new tables, zero alterations, zero drops, zero renames. Old code continues to run against the old schema during and after the release, which is what makes the Laravel Cloud zero-downtime deploy safe here.

**`migrate:rollback` limits — read before ever typing it:**
1. The `down()` methods drop tables. After any real traffic has written GL/pool rows, rolling back **destroys financial data**, not just schema.
2. Migration 1's `down()` would drop the restored ecommerce tables — the exact tables lost in the 2026-09-13 TiDB incident. **Never batch-rollback past migration 1.**
3. If the deployments table on TiDB was ever wiped (as happened 2026-09-13), `migrate` re-runs "new" migrations from scratch — the `hasTable` guards make that safe (existing tables are skipped, not recreated), which is the designed protection against a repeat of that incident.

---

## 5. Post-deploy selftests (with expected output)

Run in Laravel Cloud shell (or SSH/CLI into the environment) **immediately after** the release:

```bash
# 1. registry sync
php artisan kicc:register-pipelines
#   expected: "Registered/updated 50 pipelines."

# 2. engine end-to-end (adds SELFTEST rows; safe on prod, tagged for cleanup)
php artisan kicc:selftest-engines
#   expected, all PASS: hold→capture lifecycle; 2 splits; hold→refund;
#   trial balance balanced; pool totals 134.25 / holdback 15.00 / equalisation 0.75;
#   dry-run does not settle; settlement succeeds; exact-cent tie;
#   second settle is a no-op (already_settled, still 2 rows); period close OK
#   final line: "ALL ENGINE SELF-TESTS PASSED"

# 3. joiner end-to-end over all 152 subsectors
php artisan kicc:selftest-joiner
#   expected, all PASS: dry-run over full registry; reconciliation balanced;
#   committed run posts; repeat run is already_posted; postings rows == plan;
#   trial balance balanced after posting; pool contributions recorded
#   final line: "ALL JOINER SELF-TESTS PASSED"

# 4. clean up the SELFTEST rows
php artisan kicc:selftest-engines --cleanup
php artisan kicc:selftest-joiner --cleanup
#   expected: "cleaned N SELFTEST rows" / "cleaned N SELFTEST-JOINER rows"
```

**If any selftest FAILs:** the command throws at the first failed check (by design). Capture the check name, do **not** commit any real traffic, go to §6.

---

## 6. Rollback decision tree

```
Problem detected after deploy
│
├─ A. Selftest FAIL, no real transactions yet
│    → Code rollback: redeploy the previous successful build
│      (Laravel Cloud Deployments page — pick the last good deployment;
       see https://laravel.com/cloud/docs/deployments)
│    → Schema: new tables are empty → safe to leave in place, OR
│      php artisan migrate:rollback-batch   (drops ONLY this batch's tables)
│      — never rollback past migration 1 (§4 limit #2)
│
├─ B. Selftest FAIL after real transactions have started
│    → FREEZE money flows (do not run --commit commands)
│    → Code rollback via previous build (safe: schema is expand-only,
│      old code runs fine alongside the new tables)
│    → Data: forward-fix only — do NOT drop GL/pool tables containing
│      financial rows. Reconcile with kicc:dry-run-distribution reports.
│
├─ C. Reconciliation variance reported (joiner variance_report non-empty)
│    → Do NOT rollback code (the joiner did its job — it refused a bad batch)
│    → Inspect joiner_reconciliations rows for the batch_id, fix the
│      offending pipeline config, re-run; batch_id changes, no double-posting
│
└─ D. Data-loss signature (tables missing again, like 2026-09-13)
     → Re-run php artisan migrate --force — hasTable guards recreate only
       what is truly missing; existing tables untouched
     → Restore ecommerce data from TiDB backup; then G3 selftests again
```

**Data-rollback caveat (explicit):** there is **no** data-rollback path in this kit. `ledger_transactions`, `pool_contributions`, `joiner_postings` etc. are append-only financial records; the only safe response to bad data is a forward fix (reversing journal via `LedgerService::post()` with mirrored debit/credit lines, then a corrected re-run). Rollback of code ≠ rollback of data.

---

## 7. Smoke tests & monitoring after go-live

```bash
# money-flow smoke (dry-run — posts nothing):
php artisan kicc:dry-run-distribution          # pool distribution preview, multiplier forced 1.0
php artisan kicc:joiner:run                    # full-registry plan, conservative rates
php artisan kicc:joiner:run --sector=tourism   # single-sector slice
php artisan kicc:joiner:run --rate=optimistic  # upper take-rate bounds

# first real committed orchestration (after governance review):
php artisan kicc:joiner:run --commit --pool=<id>
```

Expected smoke result: status `dry_run`/`posted`, `Reconciliation: BALANCED — no variances`, unified ledger `BALANCED`. Monitor: `joiner_runs.status` (`balanced` vs `variance_detected`), `joiner_reconciliations` row count (should stay 0 in normal operation), `mother_pools.distribution_status`, and `gl_periods` closes via `GLReportingService::closePeriod()` at month-end.

---

## 8. Verification log (what was grepped from the kit before writing this runbook)

- **Artisan commands (5, all real):** `kicc:register-pipelines`, `kicc:dry-run-distribution`, `kicc:selftest-engines`, `kicc:joiner:run`, `kicc:selftest-joiner` — signatures confirmed in `app/Console/Commands/`.
- **Migrations (13):** `2026_09_17_000001` → `..._000013` — filenames confirmed in `database/migrations/`.
- **Config files (16):** `kicc-pipelines.php`, `kicc-engine-bindings.php`, `kicc-county-classification.php`, `config/kicc/subsectors/` (12 sector files + `index.php`).
- **Env vars:** only `KICC_HOLDBACK_RATE`, `KICC_EQUALISATION_RATE`, `KICC_DRY_RUN_DEFAULT` (documentation-only — see §2) plus the `DB_*` set; no `env()` reads inside kit code.
- **Queue/scheduler entries:** **none** in the kit (grep clean) — nothing to configure on Laravel Cloud for queues or cron in this deploy.
- **Test files (3):** `tests/selftest_engines_core.php`, `tests/validate_subsector_configs.php`, `tests/selftest_joiner_core.php` — all exit 0 at kit build time.

**Laravel Cloud behaviour relied on:** push-to-deploy + Deploy button, build vs deploy hooks, zero-downtime releases ([Deployments docs](https://laravel.com/cloud/docs/deployments)); encrypted org-level Secrets Manager ([Secrets docs](https://laravel.com/cloud/docs/secrets)); managed queue workers/scheduler exist but are **not required** by this kit ([queue autoscaling announcement](https://laravel.com/blog/managed-queues-autoscaling-queue-workers-on-laravel-cloud)).

**Unconfirmed items (be honest in the post-mortem):** the DB-layer selftests (§5) were validated in the sandbox only at the pure-logic level; transaction/lock behaviour on TiDB is confirmed only when §5 runs green on your Laravel Cloud environment.
