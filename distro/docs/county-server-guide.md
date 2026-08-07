# County Server Guide

This is the operator's manual for running a KICC county server (or the national-tier/KICC mother server).

## What you got

| File | Purpose |
|---|---|
| `kicc-engine.jar` | The whole platform API (Spring Boot fat jar, Java 21) |
| `start-county-server.sh` | One-command launcher — sets port, data dir, county slug, secrets |
| `kicc-mobile.apk` | Android app for officers (self-contained release build) |

## 1. Requirements

- Java 21 (`java -version` must print `21.x`) — Linux/macOS/Windows all fine.
- Enough RAM: 1 GB is plenty for one county.
- The machine must be reachable from the county's phones on the LAN (or VPN).
- Optional but recommended: a firewall rule limiting `8091` to the county's network.

## 2. First boot

```bash
chmod +x start-county-server.sh
COUNTY_SLUG=muranga ./start-county-server.sh
```

On first boot the server:
- creates a fresh AES-encrypted H2 database under `./data/`,
- seeds exactly two accounts (county mode):
  - `county@kicc.go.ke` — COUNTY tier, your `COUNTY_SLUG`
  - `exhibitor@kicc.go.ke` — EXHIBITOR tier
- generates random DB-file key and JWT secret (they are printed/exported in the terminal session — save them, e.g. `.env`).

> The default county password is `county@2026`. **Change it on the very first login**
> (Admin → Users → pick the account → edit via create with a new user, or ask KICC ops).

## 3. Connecting phones

1. Install `kicc-mobile.apk` on each phone.
2. Open the app. On the login screen tap **Server: http://…** and enter `http://<server-ip>:8091`
   (the script's `PORT`), e.g. `http://192.168.1.20:8091`.
3. Sign in with `county@kicc.go.ke` (or an officer account you created).
4. Dashboard → **Pull county data** to load the county's records onto the device (SQLCipher-encrypted).
   Officers can then work fully offline; pushes upload edits when the phone has connectivity.

## 4. Running it properly

```bash
# persistent background service (Linux/systemd style)
nohup ./start-county-server.sh > county-server.log 2>&1 &

# or on boot via systemd:
#   [Service] ExecStart=/path/to/start-county-server.sh User=kicc
```

Overrides (all optional):

| Env | Default | Meaning |
|---|---|---|
| `COUNTY_SLUG` | `kilifi` | county slug used in seeded county account |
| `PORT` | `8091` | HTTP port |
| `DATA_DIR` | `./data` | database directory |
| `KICC_SEED_COUNTY_PASSWORD` | `county@2026` | initial county password (change it!) |
| `KICC_DB_FILE_KEY` | random | H2 file-encryption key (must NOT change after first boot) |
| `KICC_DB_USER_PASSWORD` | `engine` | H2 user password |
| `JWT_SECRET` | random | token signing secret |

## 5. Backups

Stop or hot-copy: the engine writes `./data/backups/<date>-startup.zip` on every boot.
Back up `./data/` with the machine's backup tool; the zip includes a valid snapshot.

## 6. Mother server (KICC / national)

Same jar, seed mode `all`:

```bash
KICC_SEED_MODE=all ./start-county-server.sh   # seeds admin/national/county/exhibitor
```

KICC is authoritative for the whole platform; each county server is independent and
serves whatever phones are pointed at it. Production hardening (TLS, secrets, rate
limits) is in `prod-go-live.md`.

## 7. Troubleshooting

- `java: not found` → install Java 21 or export `JAVA_HOME` to the JRE location.
- Phone can't reach server → `adb`/ping test from the phone; check firewall; confirm `PORT`.
- `Login failed` → wrong server URL, wrong password, or the county account was deactivated.
- Change `COUNTY_SLUG` after first boot has no effect (seeding only runs on empty DB) —
  create the correct account from the app's Admin → Users instead.
