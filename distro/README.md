# KICC Platform — Distribution Package

Self-contained offline-first deployment for any organisation (county, national govt, KICC).

## Layout

```
server/
  kicc-engine.jar            Kotlin/Spring engine (fat jar, Java 21)
  start-county-server.sh     One-command county server
mobile/
  kicc-mobile.apk            Self-contained Android app (release build, no dev server needed)
docs/
  county-server-guide.md     (below)
  prod-go-live.md            Production hardening checklist
  security-audit-2026-07-31.md
```

## Quickstart (county server)

1. Requirements: a PC / small VPS with **Java 21** (`java -version`), network reachable by the county's phones.
2. Copy `server/` to the machine. Run:
   ```bash
   COUNTY_SLUG=muranga ./start-county-server.sh
   ```
   (defaults: `kilifi`, port 8091). On first boot it seeds `county@kicc.go.ke` as COUNTY tier with your slug,
   plus `exhibitor@kicc.go.ke`. A random DB-file key and JWT secret are generated and written to the shell env.
3. Sign in with the mobile app (see below), **change the seed passwords immediately**
   (Admin → Users), then create your officers.
4. Open ports: `8091` (API). TLS in front of it in production (see `prod-go-live.md`).

## Mobile install

1. Copy `mobile/kicc-mobile.apk` to each phone (or share the file). Tap to install
   (allow "install unknown apps" once).
2. Open KICC Mobile → first launch may ask for fingerprint/face unlock (data is encrypted on the device).
3. Default server URL is baked in for the demo. For a real county server:
   Dashboard → Security → **Server: …** → enter `http://<server-ip>:8091` → Save (signs you out; re-sign in).
   Every phone can point at its own county server — the same APK works for KICC, any county, or the national tier.

## Data at rest

- Mobile: SQLCipher-encrypted SQLite; DB key in the OS keychain (Android Keystore), biometric-gated when enrolled.
- Server: H2 database file AES-encrypted (`CIPHER=AES`, key from `KICC_DB_FILE_KEY`).
- Both are empty of content until the first pull — offline-first, no data leaves the county unless it syncs.

## Mother server (KICC)

Run the same jar with the default seed mode to get all four tiers
(`KICC_SEED_MODE=all`, seeds `admin@kicc.go.ke/Admin@2026`, `county@kicc.go.ke` …). KICC is the authoritative
"mother" server; county servers are independent and connect to whichever server the phone points at.
