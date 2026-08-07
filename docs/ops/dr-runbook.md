# KICC Engine — Disaster Recovery Runbook

Applies to engine (Spring Boot, :8091). DB: H2 file `./data/engine.mv.db` encrypted at rest (AES, `CIPHER=AES`).

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
