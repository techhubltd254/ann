# KICC Brand Reference — extracted live from kicc.co.ke (2026-08-03)

## Official Palette (verified in production CSS/HTML)

| Token | Hex | Usage on kicc.co.ke | Role in design system |
|---|---|---|---|
| `kicc-red` | `#901C1E` | Primary brand color (15 uses) — headers, CTAs, accents | **Primary** |
| `kicc-gold` | `#FFCD05` | Accent highlights, badges, hover states (8 uses) | **Accent** |
| `kenya-green` | `#11820B` | Secondary accents, success, sustainability (6 uses) | **Secondary** |
| `deep-teal` | `#0C5F55` | Section backgrounds | Surface variant |
| `navy-ink` | `#0A1024` | Footer / dark sections | Dark surface |
| `link-blue` | `#1890D7` | Links, info | Info |
| `ink` | `#000000` | Body text on light | Text |
| `paper` | `#FFFFFF` | Base background (32 uses) | Background |
| `sand` | `#FAF8F3` / `#F1ECE3` | Warm section backgrounds | Alt background |

> Figma prototype (`FIGMA_PROMPT.txt`) aligns: `#901C1E` red / `#0B1E57` navy / `#FFCD05` gold, Montserrat. Use `#0A1024` (real site) for dark surfaces; keep `#0B1E57` only as a Figma legacy token.
> Destructive red stays reserved for destructive actions only (UX spec) — do NOT use `kicc-red` for delete buttons; use a distinct `#B3261E`.

## Typography
- kicc.co.ke: system sans via Astra/Elementor. Figma spec: **Montserrat**. Platform default: **Inter/Manrope** for UI density, Montserrat for display headings.

## Venues (Rooms & Spaces — canonical venue list, 10 venues)
Matches `venues` table (10 rows) in TiDB ✅
1. Tsavo Hall · 2. Amphitheater · 3. Aberdares · 4. Lenana Hills · 5. Shimba Hills Room
6. Courtyard · 7. Lawn · 8. Upper COMESA · 9. Lower COMESA · 10. Raised Tree Area
(+ View Tower/Helipad as a service — helipad billing via eCitizen)

## Services taxonomy (M-I-C-E)
Meetings · Incentives · Conferences · Exhibitions · Audio-visual Equipment · Catering ·
Event Planning & Coordination · Technical Support · Wi-Fi · Security/Fire · Parking ·
Accessibility · Tourist Information · Office Space

## Contact canonicals
- Phone: (+254) 20 3261000 · info@kicc.co.ke · sales@kicc.co.ke · complaints@kicc.co.ke · HR@kicc.co.ke
- Harambee Avenue, Nairobi · P.O. Box 30746-00100 · Open 06:00–18:00 daily
- Social: facebook/KICCNairobiKenya · X @kicc_kenya · IG @kicc_kenya · LinkedIn · YouTube · WhatsApp channel
- Partners/certifications: ICCA, ISO 27001, AIPC, UNWTO, MPI, SGS

## ⚠️ Content mistakes found on live kicc.co.ke (do not replicate)
1. Footer shows **Ugandan phone number** `+256 414-233-628` next to `020-3261000`.
2. About-menu "KICC Board" description contains gibberish: *"hjbghjbjk jioojoij"*.
3. Several nav links point to the **UAT CMS** `cmsuat.icta.go.ke/kicc.com/...` instead of production.
4. "News" submenu links to a malformed URL (`http://Corporate%20Service%20Delivery%20Charter-English`).
5. Homepage "Our Statistics" renders **0 Years / 0+ / 0+ / 0+** (counter JS broken).
6. Phone inconsistency: `(+254) 20 3261000` vs `020- 3261000` — pick one canonical format.

## National Government data reference (for NATIONAL admin tier)
- Useful-links set on kicc.co.ke: Magical Kenya, Ministry of Tourism (tourism.go.ke), Kenya Airways,
  The Presidency (president.go.ke), Kenya Police, eCitizen — mirrors `ministries` (10) + `agencies` (10) TiDB tables.
- Blueprint integrations: Huduma/eCitizen, KRA/iTax, NTSA, KWS.
