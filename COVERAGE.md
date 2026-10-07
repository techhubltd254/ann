# Rebuild coverage — runnable core, not complete original-platform parity

## Implemented and exercised
- Fresh Laravel 13 application and portable UUID/JSON schema; clean SQLite migrations/seed succeed.
- Approved KICC CSS sliced verbatim from the HTML design; new Blade layouts and components, not patched legacy views.
- Editorial home, county orbit, film-first galleries, National Government Portal, all configured public directories and per-record pages.
- Server authentication, session regeneration/logout, login throttling and administrator authorization.
- Database-backed content creation, edits, draft/publish/unpublish, uniqueness checks, optimistic concurrency and parent-cycle rejection.
- Private local media upload, publication gates, public playback and deletion; R2 adapter configured but remote operation NOT verified.
- Product/service/institution experience uploader, local browser preview and native video player. 360 sources use flat playback, not a spherical player.
- Enquiry persistence, administration review/status changes and persisted audit events.
- Original county/sector data, products/variants, both conflicting venue reference sources, ministries/agencies, CMS wording, services, FAQs, history and team records preserved as source material.
- Source rosters/prices/capacities not certified: imported reference listings remain draft for owner review. County/sector navigation and home container are published.

## Explicitly pending — do not replace the live site yet
- Full parity of all original 622 route contracts, 128 controllers, 195 models and role-specific admin operations.
- Old production TiDB row import/mapping; this project requires a NEW database, not in-place migrations on the old database.
- Scoped county/institution/exhibitor staff RBAC, MFA, registration and password recovery.
- Cart, checkout, stock/variants transaction logic, payment integrations, invoicing, commissions, ledger, refunds and fulfilment.
- Real R2 bucket validation, large direct/multipart uploads, Cloudflare CDN configuration and deletion verification.
- Video transcoding, adaptive HLS, AI vision/curation, crop/frame generation, room reconstruction and verified real splat rendering.
- Tourism bookings, screens/ad scheduling, live-stream operations, trade workflows, API integrations, jobs and automation engines.
- Original static images/documents/videos imported and assigned to the correct public records; unsafe public credentials files are deliberately excluded from new public hosting.
- Full approved-design page/interaction parity, offline font packaging, exhaustive accessibility and production load/security testing.
- Production deploy, rollback rehearsal and stakeholder acceptance.

All original inventory rows remain marked PENDING until feature-specific parity is demonstrated; visual/content mappings are not false completion claims.
