# KICC droplet deployment — repository overlay and controlled cutover

This deploys the tested **publishing core**, not full parity with the old platform. No live droplet or TiDB connection has been tested by this package. Use a NEW database on the existing TiDB cluster; do not overwrite the original ann database. TiDB API public/private keys are not MySQL credentials.

## Fill these values
- REPO_URL: https://github.com/techhubltd254/ann.git (already identified; verify origin).
- BRANCH: the release branch printed by inject-repo.sh.
- DOMAIN: kicctest.org, or your chosen HTTPS domain.
- DB_HOST, prefixed DB_USERNAME, DB_PASSWORD: TiDB Console → Connect → MySQL.
- DB_DATABASE: a new database, e.g. kicc_experience. Create it using your SQL console: `CREATE DATABASE kicc_experience CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;`.
- ADMIN_EMAIL: the new administrator; password is prompted securely.
- OLD_VHOST: the existing domain's Nginx configuration path; preserve its working TLS certificate paths.

TiDB Cloud Starter/Essential requires TLS. Ubuntu's CA bundle is `/etc/ssl/certs/ca-certificates.crt`; the supplied PDO configuration enables certificate verification. TiDB >=6.6 supports the ordinary/self foreign keys used here; >=8.5 is recommended. Primary keys are explicit; indexed strings fit utf8mb4 limits; no FULLTEXT/SPATIAL indexes are used. Modern TiDB support still needs actual connection verification.

## 1. Inject and push from your DEVELOPMENT checkout (not the running web root)
```sh
unzip KICC-ANN-DROPLET-TIDB.zip -d kicc-delivery
bash kicc-delivery/repo-overlay/deploy/inject-repo.sh /path/to/ann
cd /path/to/ann
git status --short
git commit -m "Release KICC experience publishing rebuild"
git push -u origin "$(git branch --show-current)"
```
The injector requires a clean checkout and ann origin, creates a new branch, retains originals in a private sibling backup, and stages the replacement application. It neither pushes automatically nor touches the droplet. Existing .env, vendor, uploads and Git history are preserved. The new deploy does NOT reuse the old application's .env.

## 2. Pull the release into a separate droplet source directory
```sh
REPO_URL=https://github.com/techhubltd254/ann.git
BRANCH=PASTE_RELEASE_BRANCH
sudo mkdir -p /srv
sudo git clone --branch "$BRANCH" --single-branch "$REPO_URL" /srv/ann-release-src
cd /srv/ann-release-src
# Later deployments: git pull --ff-only, from this same release branch.
```
Use existing secure Git SSH/deploy-key authentication. Do not embed a token in the URL. Leave the old live application directory and database untouched.

## 3. Runtime and completed environment
The existing Laravel droplet should already have PHP. Required: PHP >=8.4.1, php8.4-fpm, PDO MySQL, mbstring, OpenSSL, fileinfo, DOM/XML, Composer, Nginx, rsync and curl. If the droplet's configured apt repositories provide PHP 8.4:
```sh
sudo apt-get update
sudo apt-get install -y nginx php8.4-fpm php8.4-mysql php8.4-mbstring php8.4-xml php8.4-curl php8.4-zip composer rsync curl ca-certificates
sudo install -d -m 700 /root/kicc-deploy
sudo cp .env.example /root/kicc-deploy/production.env
sudo chmod 600 /root/kicc-deploy/production.env
sudo nano /root/kicc-deploy/production.env
```
Set APP_ENV=production, APP_DEBUG=false, APP_URL=https://DOMAIN, DB_CONNECTION=mysql, DB_PORT=4000 and your SQL connection fields. DB_DATABASE must be the new database. Keep MYSQL_ATTR_SSL_CA=/etc/ssl/certs/ca-certificates.crt and verification=true. Use a single-quoted dotenv password if it contains interpolation characters. APP_KEY may start blank; the script generates it only once.

Default media storage is private droplet disk, allowing initial operation without R2 configuration. R2 can be selected later through the documented runtime keys; remote R2 operation remains unverified. Do not put live keys in Git. Complete TRUSTED_PROXIES for the actual restricted ingress; do not use wildcard trust on a publicly reachable origin.

## 4. Prepare release: install, env/key, migrations/admin, storage, permissions
```sh
sudo bash deploy/deploy.sh /srv/ann-release-src /var/www/kicc-experience /root/kicc-deploy/production.env YOUR_ADMIN_EMAIL
```
The script runs composer install --no-dev, copies the first shared env, validates SQL/TLS and refuses the legacy schema, generates a blank key only, runs migrate --force, seeds source references, prompts for the first admin password, runs storage:link, sets ownership/permissions, caches config/routes/views, and smoke-tests the new release at loopback /release-health. Existing admin passwords are preserved on subsequent deployments. No migrate:fresh is used. No production domain configuration is changed by the script.

Source venue/product/ministry/agency records remain draft until owner review. Use the new admin to verify details, publish the record and publish owner media. No copied production user or footage is assumed.

## 5. PHP-FPM and existing Nginx vhost (keep working TLS)
```sh
sudo cp deploy/php-fpm-kicc.conf /etc/php/8.4/fpm/pool.d/kicc-experience.conf
sudo php-fpm8.4 -t
sudo systemctl reload php8.4-fpm
```
Back up the existing domain vhost before editing:
```sh
OLD_VHOST=/etc/nginx/sites-available/YOUR_EXISTING_SITE
BACKUP="$OLD_VHOST.before-kicc-$(date -u +%Y%m%d-%H%M%S)"
sudo cp -a "$OLD_VHOST" "$BACKUP"
sudo nano "$OLD_VHOST"
```
In the existing HTTPS server, preserve server_name, certificates, proxy/TLS settings and ACME challenge configuration. Change the Laravel root and PHP socket to:
```nginx
root /var/www/kicc-experience/current/public;
fastcgi_pass unix:/run/php/kicc-experience.sock;
fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
```
Keep `try_files $uri $uri/ /index.php?$query_string;` and hidden-file denial. A complete NEW-vhost example is in deploy/nginx-kicc.conf; it has DOMAIN/certificate placeholders. Do not install a duplicate server block for an already configured domain.
```sh
sudo nginx -t
sudo systemctl reload nginx
```
If nginx -t fails, restore the vhost backup; do not reload.

## 6. Re-cache and verify publicly
```sh
cd /var/www/kicc-experience/current
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache
sudo -u www-data php artisan view:cache
DOMAIN=kicctest.org
curl --fail "https://$DOMAIN/release-health"
curl --fail --head "https://$DOMAIN/"
```
Health must return status ok. Check /login, /admin after login, a published record, a media upload and public playback. The old site can be restored independently because its original directory/database were not changed.

## Rollback
First cutover: restore BACKUP to OLD_VHOST, run nginx -t, then reload Nginx. This returns the domain to the old application and its untouched database.
```sh
sudo cp -a "$BACKUP" "$OLD_VHOST"
sudo nginx -t && sudo systemctl reload nginx
```
For a later new-app release:
```sh
sudo bash /srv/ann-release-src/deploy/rollback.sh /var/www/kicc-experience
```
Rollback does not drop tables or revert data. Never run migrate:fresh or copy this schema over the original production database.

## Deadline / validation boundary
This is prepared for a short deployment window, but actual timing depends on working SQL credentials, Git access, PHP/FPM availability and existing TLS. Local checks exercise MySQL-compatible migrations, PHP tests, shell syntax, Nginx/FPM configuration and cache commands. They are NOT an actual TiDB/droplet deployment certification.
