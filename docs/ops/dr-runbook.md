# KICC — Disaster Recovery Runbook

Two runtimes with separate data planes:

1. **Engine** (Spring Boot, :8091) — H2 file `./data/engine.mv.db` encrypted at rest (AES, `CIPHER=AES`).
2. **Laravel app** (origin.kicctest.org) — TiDB Serverless on TiDB Cloud (`DB_HOST`, port 4000), dumped to Cloudflare R2.

## Laravel / TiDB → R2 backups

- **Tooling (on droplet)**: `mydumper` (TiDB-recommended, chunked parallel reads; plain `mysqldump` aborts on large tables / unsupported SAVEPOINTs), `rclone` (R2 S3-compatible).
- **Script**: `infra/backup-db.sh` → installed at `/opt/kicc-laravel/scripts/backup-db.sh` (owner `kicc`, exec).
- **Cadence** (Laravel scheduler, `routes/console.php`, runs under `kicc-scheduler.service`):
  - `backup-db.sh hourly` → `hourlyAt(30)`, retention **48** (`db-backups/hourly/`)
  - `backup-db.sh daily` → `dailyAt(02:45)`, retention **14** (`db-backups/daily/`); on the 1st of month also `monthly/`, retention **12**
- **Output**: one `.tar` of gzip-compressed per-table SQL (+ schema) per run, ~16MB, e.g. `kicc-20260818T0732Z.tar`.
- **Restore**: `rclone copy :s3:<BUCKET>/db-backups/hourly/<archive> /tmp/restore/`, `tar -xf`, then `myloader --host=... --database=... --directory=...` (or re-import each `*.sql.gz`). mydumper output has no FK-order coupling, tables import independently.
- **Verify**: `tar -tf <archive> | grep counties` → expect `kicc.counties.sql.gz`; gunzip + `INSERT INTO counties` rows present (47+ counties).
- **Notes**: MariaDB `mysqldump --single-transaction` fails on TiDB (SAVEPOINT 1305); use mydumper. `DB_*` and `CLOUDFLARE_R2_*` creds are read from `/opt/kicc-laravel/.env`. Local copies kept last 3 in `/var/backups/kicc/out`.

## Engine backups

**Backups**: H2 `BACKUP TO` zip → `./data/backups/` (env `KICC_BACKUP_DIR`, default `./data/backups`), keep last 7
  - startup (after data import) + daily 02:05 (cron) + manual `POST /api/admin/backups` (DELEGATE only, audited)

## Current safeguards
- **Backups**: H2 `BACKUP TO` zip → `./data/backups/` (env `KICC_BACKUP_DIR`, default `./data/backups`), keep last 7
  - startup (after data import) + daily 02:05 (cron) + manual `POST /api/admin/backups` (DELEGATE only, audited)
- **At-rest encryption**: DB file AES-encrypted; file key = `KICC_DB_FILE_KEY` (dev default `default-dev-file-key-2026`), user password `KICC_DB_USER_PASSWORD`
- **RPO/RTO targets (dev)**: RPO ≤ 24h (daily backup), RTO ≤ 15 min (file restore + engine start ~9s + import skip)

## Restore procedure (drill-verified 2026-07-31)
1. Stop engine: `fuser -k 8091/tcp`
2. Preserve broken data: `mv data/engine.mv.db data/engine-broken-$(date +%F).mv.db`
3. Restore: `unzip -o data/backups/20260731-manual.zip -d data/` (zip contains `engine.mv.db`)
4. Start: `./gradlew.sh bootRun` (importer skips — counties table non-empty)
5. Verify: login + `GET /api/data/counties` → 47 counties; `GET /api/health` → ok

## Manual restore drill (any environment)
```
mkdir -p /tmp/restore && unzip -o <backup.zip> -d /tmp/restore
java -cp <h2.jar> org.h2.tools.Shell \
  -url "jdbc:h2:file:/tmp/restore/engine;CIPHER=AES" \
  -user sa -password "<KICC_DB_FILE_KEY> <KICC_DB_USER_PASSWORD>" \
  -sql "select count(*) from counties;"
```
Expected: `47`. H2 jar: `~/.gradle/caches/modules-2/files-2.1/com.h2database/h2/.../h2-2.3.232.jar`.

## Production go-live checklist (before real deployment)
- [ ] Rotate `JWT_SECRET` (remove dev default `kicc-engine-phase1-change-this-secret-before-production-2026`)
- [ ] Rotate `KICC_DB_FILE_KEY` + `KICC_DB_USER_PASSWORD` (remove dev defaults)
- [ ] Set `SYNC_HMAC_SECRET`, `KICC_CORS_ORIGINS`, `H2_CONSOLE_ENABLED=false`
- [ ] Backup dir on separate volume/object storage (GCS), off-host retention ≥ 30 days
- [ ] Scheduled restore drill quarterly (document result here)
- [ ] Outsource: pen test, SQLCipher for mobile edge devices, TiDB migration (Phase 0)

## Incident log
- 2026-07-31: startup backup captured empty DB (ordering bug) — fixed; drill with manual backup verified full restore (47 counties, 72 booths, 437 attractions, 3557 sector entities)
