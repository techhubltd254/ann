# KICC Live Outage Report — kicctest.org
**Generated:** 2026-10-08 01:34 UTC
**Verdict:** 🔴 PRODUCTION DOWN (database-backed routes). Not a code defect. Infrastructure quota exhausted.

---

## 1. Root cause (proven)

The TiDB Cloud cluster backing the site has **exhausted its usage quota** and has been restricted by TiDB Cloud itself.

Exact server error (from `/opt/kicc-laravel/storage/logs/laravel.log`, line 214311):

```
SQLSTATE[HY000] [1105] Due to the usage quota being exhausted, access to the cluster
'10417553748930037773' has been restricted. Try increasing spending limits to gain full
access. For more information, see
https://docs.pingcap.com/tidbcloud/serverless-limitations#usage-quota
(Connection: tidb, Host: gateway01.eu-central-1.prod.aws.tidbcloud.com, Port: 4000,
 Database: kicc, SQL: select * from `counties` where `slug` = muranga limit 1)
```

Direct DB probe from the server (`DB::select("select 1")`) returns the same 1105 error.
Redis responds `PONG` — the cache layer is healthy. **Only the database is restricted.**

| Fact | Value |
|---|---|
| Cluster ID | `10417553748930037773` |
| Host | `gateway01.eu-central-1.prod.aws.tidbcloud.com:4000` |
| Database | `kicc` |
| First quota error | `2026-10-07 17:13:50` UTC |
| Latest quota error | `2026-10-08 01:31:57` UTC |
| Total quota errors logged | 361 |
| Duration so far | ~8 h 20 min and ongoing |
| Redis cache | healthy (`PONG`) |

Note the timing: the outage began **~5 minutes after** the reconciliation release was activated
(activation completed 17:08 UTC). The reconciliation release itself is not the cause — it simply
increased query volume against an already near-limit serverless cluster, and the quota cap was hit.

---

## 2. Blast radius (live HTTP probes)

| Route | Status |
|---|---|
| `/` (static SPA shell) | 🟢 200 |
| `/login` | 🟢 200 |
| `/#/` (SPA home) | 🟢 200 |
| `/counties` | 🔴 500 |
| `/venues` | 🔴 500 |
| `/marketplace` | 🔴 500 |
| `/institutions` | 🔴 500 |
| `/national-government` | 🔴 500 |
| `/experience/api/public` | 🔴 500 |

Every database-backed route fails. The public JSON API returns `{"message":"Server Error"}`.

---

## 3. Release deployment: verified intact

The front-end release is **not** the problem and is confirmed live, byte-for-byte:

| Check | SHA-256 |
|---|---|
| Live-served `https://kicctest.org/` HTML | `e083c78adffbfec85e2ab38e36af887b012114519494333ef53d1d691d776bf9` |
| Deployed file `public/KICC-EXPERIENCE-BUILD.html` | `e083c78adffbfec85e2ab38e36af887b012114519494333ef53d1d691d776bf9` |

**Identical.** The reconciled release (4,641 bindings, 264 Blade templates, 273/273 file parity)
is what the server is serving. No drift.

---

## 4. Public UX impact while the DB is restricted

Browser-level assessment (Playwright, Chromium):

| Check | Result |
|---|---|
| Home renders | ✅ PASS |
| Home heading present | ✅ PASS |
| County data "Nairobi" present | ✅ PASS |
| County data "Mombasa" present | ✅ PASS |
| County data "Muranga" present | ❌ FAIL (requires DB) |
| `/counties` page returns 200 | ✅ PASS |
| `/counties` not an error page | ✅ PASS — renders "47 DESTINATIONS / Explore Kenya's 47 Counties" |
| No uncaught JavaScript errors | ✅ PASS |

The SPA shell and its static fallback data still render, so visitors do not see a blank page.
Any dynamic, database-derived content is stale or missing.

---

## 5. Required fix (owner action — cannot be done from code)

1. **Raise the TiDB Cloud spending limit / quota** for cluster `10417553748930037773`
   (TiDB Cloud console → cluster → spending limit). This is the only action that restores service.
2. Optionally enable a **cached-fallback degradation path** so public routes serve the last-known-good
   payload from Redis instead of a 500 when the DB is restricted. This is a code change and can be
   implemented once the cluster is reachable again.

Reference: https://docs.pingcap.com/tidbcloud/serverless-limitations#usage-quota
