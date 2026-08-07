# Outsourcing decisions — 2026 review

Status: research complete (Aug 2026). Decisions pending confirmation with programme budget holders.

## 1. Hosting / database for KICC-scale tenants

The platform is a set of small JVM engines; each county gets its own embedded H2 database on its own server. The
question here is only for the **central/mother server** and for tenants who prefer a managed cloud database over their
own box.

Reference sizing used for comparison: 40 counties × ~5 field officers; ~1,500 listing rows; ~2M monthly API calls.

| Option | Cost (monthly) | Africa region | Notes |
|---|---|---|---|
| **TiDB Cloud Starter (serverless)** | $0 (free tier: 25 GiB storage, 250M row/req units) | No Africa region (closest: Mumbai, Frankfurt, Singapore) | Free tier is comfortably above our reference sizing. SQL + MySQL protocol. |
| TiDB Cloud Essential (dedicated, 2 nodes) | ≈$400 | No Africa region | |
| TiDB Cloud Dedicated (3-node) | ≈$160/node, ≈$1,400/set | No Africa region | 5+ year TCO highest. |
| TiDB Cloud Premium | from ≈$1,800 | No Africa region | |
| **AWS Aurora Serverless v2 — Cape Town `af-south-1`** | ≈$50–70 (small, scaled to zero) | **Yes — first-party residency** | Best fit where data residency matters (Kenya). |
| PlanetScale | $5 dev / $39 pro | No Africa region | MySQL-compatible; good DX; no Africa edge. |

Recommendation: **AWS Aurora Serverless v2 in Cape Town** if residency is required; otherwise **TiDB Cloud Starter
free tier** is the cheapest path that stays within sizing. Note the mother server currently runs H2 embedded and needs
no external database at this stage — this table covers *tenant options* and the future multi-tenant control plane.

## 2. External security penetration test

| Option | Cost | Notes |
|---|---|---|
| Automated continuous scanning (Intruder, Detectify) | ≈$100–500/yr | Self-service, fast, catches low-hanging fruit; not a substitute for manual review. |
| Manual pentest — international firms (NCC Group, Bishop Fox, …) | $15k–80k per engagement | Deep manual testing incl. mobile app review. |
| Kenya-based firms (Serianu, Cyberteq, …) | ≈KES 100k–600k per engagement (~$800–4,600) | Onshore, familiar with Kenyan regulation (DPA 2019), typically faster to engage; skills vary. |

Recommendation: run an **automated scan now** (continuous), and engage a **Kenyan firm for a manual engagement**
before the first county goes to production. FX reference: 1 USD ≈ KES 129 (2026 average).

## 3. Open questions for budget holders

1. Do county tenants prefer AWS (residency) or TiDB free tier (cost) for managed hosting?
2. Is the manual pentest budget held centrally (KICC) or per-org?
3. When is the first county production cutover — to time the manual engagement?

## Reference URLs

- TiDB Cloud pricing: https://www.pingcap.com/tidb-cloud-pricing/
- TiDB Cloud regions: https://docs.pingcap.com/tidbcloud/regions
- AWS Aurora pricing (Cape Town): https://aws.amazon.com/rds/aurora/pricing/
- AWS regions: https://aws.amazon.com/about-aws/global-infrastructure/regions/
- PlanetScale pricing: https://planetscale.com/pricing
- Intruder: https://www.intruder.io/pricing
- Detectify: https://detectify.com/pricing
- Kenya DPA 2019: https://www.odpc.go.ke/
