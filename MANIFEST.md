# MANIFEST — what was NOT copied into kicc-one (and where it lives)

Intentionally excluded (regeneratable artifacts, heavy media, secrets). Originals remain untouched.

| Excluded | Original location | Reason |
|---|---|---|
| Secrets (`tokens.txt`, `CREDENTIALS.md`, `.env`) | `/home/kicc/Desktop/kicc/`, project roots | Security — rotate, then store in secrets manager |
| Screen/showcase videos (~2.4 GB) | `kicc-platform/storage/app/public/screens/`, `m&k_labeled/` | Pipeline derivatives — rebuilt by `pipeline/` |
| County/venue image libraries (~1 GB) | `kicc-platform/kicc-images/`, `county profile pics labeled/`, `m&k/`, `m&k_labeled/`, `storage/app/public/counties/` | Bulk media — publish via R2, not git |
| Build artifacts (`kicc-engine.jar`, `kicc-mobile.apk`) | `kicc-platform/kicc-app/`, `server/`, `deploy-package/` | CI builds these (SHA-tagged) |
| ML model repos (TRELLIS, TRELLIS.2, img2threejs) | `kenya-3d-platform/model_holders/` | Pulled by pipeline setup script |
| `node_modules/`, `vendor/`, `.git/` | all projects | Reinstall via package managers |
| Portable toolchain (`php`, `composer.phar`, portable git) | `kicc-platform/.tools/` | Environment tooling, not product |
| Equipment PDFs/quotes | `kicc-platform/*.pdf`, `gen_*.py` | Procurement docs (non-product) |
