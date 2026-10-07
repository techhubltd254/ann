# KICC clean Laravel + Blade rebuild

This is a **runnable greenfield source project with a tested publishing core**. It is NOT a complete replacement for every original platform feature. Read COVERAGE.md, INVENTORY.md and verification before deployment. The legacy source and live site have not been replaced or pushed.

## Stack
PHP 8.3+ (verified on 8.4.26), Laravel 13, Blade, approved KICC HTML/CSS, vanilla JavaScript. No React/Inertia/Vite compilation is required for the new interface. SQLite for reproducible local execution; Laravel MySQL driver and SSL CA setting provided for a **new** TiDB database. Cloudflare R2 via Flysystem S3 adapter; credentials only in runtime environment.

## Run locally
```sh
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
php artisan kicc:admin your-email@example.com
php artisan serve --host=127.0.0.1 --port=8100
```
The administrator command asks for a hidden password of at least 14 characters. No default administrator is seeded. Use a unique password. Required PHP extensions: PDO/SQLite, mbstring, OpenSSL, fileinfo, XML/DOM; GD for the image-upload tests. For MySQL/TiDB install PDO MySQL. Composer.lock pins dependency versions.

## Verify
```sh
php artisan test
php artisan view:cache
node --check public/js/rebuild.js
```
The delivery includes test logs and browser evidence. Tests use an in-memory SQLite database. No production credentials, database, uploaded footage, vendor or node_modules are included.

## Publishing workflow
1. Sign in at /login; an administrator reaches /admin.
2. Open the specific content module; imported original rosters and sample seed listings are drafts for owner review.
3. Edit names, descriptions, relationships and structured original details. Set record status to Published when verified.
4. Upload owner JPG/PNG/WebP or MP4/WebM/MOV footage and set media status. Both the parent record and media must be published.
5. Public Blade pages query the same database. Draft media is private; deleting media removes the storage object and row.
6. Native MOV playback depends on codec. 360 video is flat. Uploading video does NOT automatically reconstruct 3D or run AI vision.

Metadata is stored in the database. localStorage stores only theme preference. URL.createObjectURL is temporary upload preview, never hosting.

## TiDB / R2 and production prerequisites
Use a NEW database such as kicc_experience, a least-privilege DB user, TLS and MYSQL_ATTR_SSL_CA. Never execute migrate:fresh against production or point this schema at the old live database. Migrate original rows through a separately reviewed export/import mapping; preserved source seed files are not a production database export.

For R2 set KICC_MEDIA_DISK=r2, R2_ACCESS_KEY_ID, R2_SECRET_ACCESS_KEY, R2_BUCKET and R2_ENDPOINT in runtime environment. Use a private bucket. Media requests authorize parent/asset publication before issuing a short-lived signed URL. Previously issued URLs can remain usable until expiry; no immediate CDN revocation is claimed. Remote R2 operation has not been certified.

Set APP_ENV=production, APP_DEBUG=false, correct APP_URL, HTTPS/session cookie settings, and trusted proxy IP ranges for your actual ingress. TRUSTED_PROXIES=* was used only in sandbox runtime; do not use it on a publicly reachable origin without restricting ingress. Disable direct serving of storage/app/private.

Host public/ only; keep .env/storage private. PHP/web-server upload limits must match KICC_MAX_UPLOAD_KB. Cloudflare request limits may require the pending direct multipart uploader for large files. Native enquiry forms save records; they do not send email or confirm payment/bookings. Audit events are persisted but not tamper-proof.

## Original content and conflicts
Original county/sector JSON, scraped references and seasonal calendar are preserved in database/data. Safely extracted static PHP seed arrays and original CMS page wording are retained. Dynamic future exhibition dates, random stock and fabricated reviews are not converted into factual live data. Conflicting venue capacities from the prototype and two seeders are preserved for review rather than chosen silently. Original views/text/menus and every source path are inventoried, not executed as old views.

The current private production users/records/media were not copied. Rotate credentials previously shared in conversation before any production integration. This bundle includes no credentials or QA account.
