# Secrets Rotation Runbook

> These tokens were exposed in chat + plaintext files — rotate each, in this order.
> Tier account passwords ALREADY rotated 2026-08-07 → see `infra/ota/tier_passwords.txt` (0600, local only).

## Provider tokens (manual, ~30 min total)

| Service | Where to rotate | Then update |
|---|---|---|
| **GitHub PAT** | github.com → Settings → Developer settings → PATs → revoke `ghp_WO2G…`, create new fine-grained | CI secrets, local git remotes |
| **TiDB password** | tidbcloud.com → cluster → Security → reset password for `REDACTED.root` | droplet `/opt/kicc-laravel/.env` (DB_PASSWORD), engine systemd `Environment=`, then `systemctl restart kicc-engine` + queue |
| **Cloudflare API** | dash.cloudflare.com → My Profile → API Tokens → roll `cfat_uk7…` + `cfat_m35r…` | `deploy-worker.sh` usage, platform `.env` CLOUDFLARE_API_TOKEN |
| **Cloudflare R2 keys** | R2 → Manage API tokens → rotate both keypairs | platform `.env` AWS_* |
| **DigitalOcean** | cloud.digitalocean.com → API → regenerate `dop_v1_…` | any DO scripts |
| **OpenRouter** | openrouter.ai/keys → revoke `sk-or-v1-8050…` | platform `.env`, agentic_loop config |
| **NVIDIA** | build.nvidia.com → API keys | pipeline configs |
| **Zen API** | provider console | wherever referenced |
| **Vercel** | vercel.com/account/tokens | CI / deploy hooks |
| **Supabase service key** | supabase.com → project → API → roll service key | supabase-frontend env |
| **Vast.ai** | console.vast.ai → account → API key reset | local scripts |
| **Figma** | figma.com → settings → personal access tokens | design tooling |

## Done by automation (2026-08-07)
- ✅ Tier account passwords (admin/national/county @kicc.go.ke) — strong random, stored locally
- ✅ Exhibitor password remains `exhibitor@2026` — rotate on first real exhibitor onboarding

## Never-commit rules
- No secrets in git — ever. `.gitignore` already covers `.env`, `tokens.txt`, `CREDENTIALS.md`, `infra/ota/*`.
- If a secret touches chat/logs again → rotate immediately.
