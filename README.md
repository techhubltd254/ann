# KICC ann repository / droplet deployment bundle

Use **DEPLOY.md** for the production MySQL/TiDB runtime profile and ordered release/cutover instructions. `.env.example` is now a production template with placeholders, not a ready-to-run SQLite config.

The core application and original content inventory are retained. This deploys the tested publishing core, NOT full legacy feature parity. See COVERAGE.md. No live droplet or TiDB instance has been modified or certified.

`deploy/inject-repo.sh` injects a clean development ann checkout into a new branch with a sibling backup. `deploy/deploy.sh` builds an isolated release and validates it before a manual Nginx cutover. `deploy/rollback.sh` restores an earlier new-app release; the first cutover is reversed by restoring the backed-up original vhost.

Do not run migrate:fresh against production. Keep original site/database intact. Fill the SQL connection fields from TiDB Console Connect; TiDB API keys are not SQL credentials.
