How to run `admin-panel/` in production: server requirements, Composer, the web root, file permissions, the storage link, queues, the scheduler, caching, HTTPS and proxies, security headers, logs and backups. It describes what the code needs and does; for a first install see [Installation-Wizard](Installation-Wizard), and for the end-user hosting notes see [Prerequisite](https://shawndaprawn.github.io/Eschool/admin-panel/prerequisite/) and [Installation Steps](https://shawndaprawn.github.io/Eschool/admin-panel/installation-steps/).

## About the README deployment scripts

`admin-panel/README.md` mentions `./complete_upload.sh`, `./backup_old_code.sh` and a `DEPLOYMENT_GUIDE.md`. None of them exist. The only shell script in the repo is `admin-panel/docker/entrypoint.sh`, which the Cloud Run image runs on startup. The README's "Manual Deployment" steps are also partly wrong for this code base (`php artisan db:seed` seeds nothing). Use this page instead.

## NavigateEd production

This project's panel runs on Cloud Run in `africa-south1` (Johannesburg), Firebase project `navigateed-d8c06`, on the billing account named Firebase Payment. The public URL is https://navigateed-admin-972068206844.africa-south1.run.app/ (`/login`, `/home`, `/up`). Both Flutter apps point `baseUrl` at that host.

| Piece | Value |
|---|---|
| Service | `navigateed-admin` |
| Image | `admin-panel/Dockerfile` (PHP 8.3, Apache, port 8080) |
| Database | Cloud SQL MySQL 8.0 `navigateed-mysql`, `db-f1-micro`, 10 GB HDD, zonal, database `navigateed` |
| Uploads | Cloud Storage bucket `navigateed-d8c06-admin`, mounted at `storage/app` |
| Sessions | `SESSION_DRIVER=database` |
| Logs | `LOG_CHANNEL=stderr` (Cloud Run logs) |
| Timezone | `Africa/Johannesburg` |

Cloud SQL stays on while the panel is idle, so this is the paid part (the smallest shared-core machine in Johannesburg). Cloud Run itself scales to zero. The container entrypoint migrates, seeds the installer data once, replaces the seeded `superadmin@gmail.com` / `superadmin` login with `info@tokensoft.tech`, and writes `storage/installed`. The admin password is the Secret Manager secret `navigateed-admin-password`, not a value in git. `APP_KEY` and the database password are the secrets `navigateed-app-key` and `navigateed-db-password`.

Daily chat cleanup and Sanctum pruning (`routes/console.php`) do not run until something calls `php artisan schedule:run`. There is no Cloud Scheduler job yet.

`bootstrap/app.php` trusts proxy headers so HTTPS behind Cloud Run stays HTTPS, and trusts the `APP_URL` host. A production `APP_URL` that does not match the browser host returns 400.

## Server requirements

| Component | Requirement | Source |
|---|---|---|
| PHP | 8.3 or newer | `composer.json` (`"php": "^8.3.0"`, platform `8.3.0`), installer check `80300`, CI uses 8.3 |
| Database | MySQL 5.7+ or MariaDB | The migrations use MySQL-specific SQL; CI runs MariaDB 10.11; README says MySQL 5.7+ |
| Web server | Apache with `mod_rewrite`, or Nginx with PHP-FPM | `.htaccess` files in the project root, `public/` and `storage/app/public/` |
| Composer | 2.x | `composer.lock` |
| Node.js | Not needed | Assets are prebuilt in `public/assets`; see [Admin-Panel-Overview](Admin-Panel-Overview#front-end-build-vite-mix) |
| Cron | Needed for the scheduler | `routes/console.php` |
| Outbound HTTPS | FCM (`fcm.googleapis.com`, Google OAuth), payment gateways, Google reCAPTCHA, the SMTP server, and GitHub during `composer install` (the Paystack package is a VCS dependency) | |

`composer.json` sets `"platform-check": false`, so Composer and Laravel will not stop you if an extension is missing; the failure shows up later at runtime. PHP extensions:

| Extension | Why |
|---|---|
| `pdo_mysql` | Database (installer check) |
| `mbstring`, `tokenizer`, `openssl`, `ctype`, `filter`, `hash`, `session`, `json`, `pcre` | Laravel and its dependencies (installer checks the first four plus `json`) |
| `fileinfo` | Upload MIME detection in `RejectDangerousUploads`, Flysystem (installer check) |
| `curl` | FCM pushes (`sendFCMCurl`), Flutterwave, Stripe SDK (installer check) |
| `gd` | Intervention Image GD driver (`UploadService`, `ImageService`, `resizeImage()`), PhpSpreadsheet. Not checked by the installer. |
| `zip` | `ZipArchive` in [System-Update](System-Update), PhpSpreadsheet imports. Not checked by the installer. |
| `dom`, `libxml`, `xml`, `simplexml`, `xmlreader`, `xmlwriter` | DomPDF, `enshrined/svg-sanitize`, PhpSpreadsheet. Not checked by the installer. |
| `iconv`, `zlib` | PhpSpreadsheet, ZipStream. Not checked by the installer. |

CI installs `mbstring, intl, gd, zip, pdo_mysql, bcmath, exif, fileinfo` on top of the runner's defaults.

PHP settings to review: `upload_max_filesize` and `post_max_size` must allow your largest upload, including System Update packages (Laravel's `ValidatePostSize` answers 413 when a request exceeds `post_max_size`); `symlink()` must be allowed for `storage:link`.

## Composer dependencies

```bash
composer install --optimize-autoloader
```

The container image runs `composer install --no-dev`. `barryvdh/laravel-debugbar` is a `require-dev` package, and its provider is no longer listed in `config/app.php`, so a `--no-dev` install can boot. Package discovery still registers Debugbar when the dev package is installed locally. Keep `APP_DEBUG=false` in production.

## Web root

Point the web server's document root at `admin-panel/public`. Everything outside `public/` (`.env`, `storage/`, `vendor/`, `bootstrap/cache/config.php`) must not be reachable over HTTP.

The user docs describe uploading the whole folder into `public_html` on shared hosting. That works because of the [root .htaccess](https://github.com/ShawnDaPrawn/Eschool/blob/main/admin-panel/.htaccess):

- `RewriteRule ^(.*)$ public/$1 [L]` sends every request into `public/`;
- a `FilesMatch` denies dotfiles, `composer.json`/`.lock`, `package.json`, `artisan`, `phpunit.xml` and `vite.config.js` even when `mod_rewrite` is off;
- `RedirectMatch 404 /\.(git|env)` hides `.git` and `.env`.

This only protects anything while Apache honours `.htaccess` (`AllowOverride`) and `mod_rewrite` is on; without the rewrite, `storage/logs/laravel.log` and the rest of the tree are served as plain files. Prefer a document root of `public/` wherever the host allows it.

`public/.htaccess` is Laravel's standard front-controller file (passes the `Authorization` header through, strips trailing slashes, routes everything else to `index.php`).

### Nginx

No Nginx configuration ships with the repository. A minimal example, equivalent to the `.htaccess` files:

```nginx
server {
    listen 443 ssl;
    server_name school.example.org;
    root /var/www/eschool/admin-panel/public;
    index index.php;

    client_max_body_size 64m;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # Uploaded files: never execute anything under /storage (mirrors storage/app/public/.htaccess)
    location ^~ /storage/ {
        add_header X-Content-Type-Options nosniff always;
        location ~* \.(svg|svgz|html?|xml)$ {
            add_header X-Content-Type-Options nosniff always;
            add_header Content-Security-Policy "default-src 'none'; img-src 'self' data:; style-src 'unsafe-inline'; sandbox" always;
        }
        location ~* \.(php\d?|pht|phtml|phar|phps|shtml|cgi|pl|py)$ { deny all; }
        try_files $uri =404;
    }

    location ~ /\.(?!well-known) { deny all; }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
    }
}
```

Add a separate `default_server` block that rejects unknown host names (see [HTTPS, proxies and hosts](#https-proxies-and-hosts)).

## File permissions

The web server (PHP) user needs to write to:

| Path | Why |
|---|---|
| `storage/` (all of it: `app`, `app/public`, `framework/cache`, `framework/sessions`, `framework/views`, `logs`) | Uploads, private files, sessions, file cache, compiled views, logs, the `storage/installed` marker |
| `bootstrap/cache/` | `packages.php`, `services.php` and, when you cache them, `config.php`, `routes-v7.php`, `events.php` |
| `.env` | Only while the installer runs (it writes `DB_*`, `APP_URL` and `APP_KEY`). The admin settings pages no longer write `.env`, so it can be made read-only afterwards. |
| `public/` | Only to create the `public/storage` link (the installer and `/storage-link` do this). |
| The whole application directory | Only if you use the in-app [System-Update](System-Update): it creates `update/tmp/`, moves `version_info.php` and `source_code.zip` into the project root and extracts the package over it. If you deploy with git instead, keep the code read-only for the web user. |

The installer's folder step checks the mode bits of `storage/framework`, `storage/logs` and `bootstrap/cache` (at least `755`), not real writability, so a green tick does not prove the web user can write there.

A common layout is code owned by a deploy user, with the web server group allowed to write to the runtime directories:

```bash
cd /var/www/eschool/admin-panel
sudo chown -R deploy:www-data .
sudo find storage bootstrap/cache -type d -exec chmod 2775 {} \;
sudo find storage bootstrap/cache -type f -exec chmod 664 {} \;
chmod 640 .env
```

## Storage link and uploaded files

Uploads go to the `public` disk, `storage/app/public`, and are served at `/storage/...` through the symlink `public/storage` (`config/filesystems.php` `links`).

```bash
php artisan storage:link
```

- The installer runs `storage:link --force` in its Keys step.
- On hosts without SSH, a signed-in Super Admin can open `/storage-link` (route in `routes/web.php`, middleware `auth` and `role:Super Admin`). The user FAQ's fix for broken images is to delete `public/storage` and open that URL.
- `public/storage` is in `.gitignore`; recreate it on every new checkout.
- `storage/app/public/.htaccess` (Apache only) turns off PHP, denies script extensions and dotfiles, sends `nosniff`, and serves SVG, HTML and XML with a sandboxing `Content-Security-Policy`. On Nginx you must configure the same yourself (example above). The first line of defence is the `RejectDangerousUploads` middleware on the `web` and `api` groups, which refuses script and web-page uploads and sanitises SVGs.
- Private files live on the `local` disk, `storage/app`, for example the Firebase service account at `storage/app/firebase/service_account.json`. They must stay outside the web root.

## Queue workers

None are needed. `.env.example` sets `QUEUE_CONNECTION=sync`, the app defines no jobs (there is no `app/Jobs`, nothing implements `ShouldQueue`), and email is sent with `Mail::send` during the request (`MailService`, `StudentsImport`). FCM pushes are sent with cURL during the request too. The `jobs` and `failed_jobs` tables exist only because the default migrations create them. A slow SMTP server or a large push therefore slows down the request that triggers it.

## Scheduler

Two tasks are scheduled in `routes/console.php`, so add the standard Laravel cron entry for the web server user:

```cron
* * * * * cd /var/www/eschool/admin-panel && php artisan schedule:run >> /dev/null 2>&1
```

| Task | Cron | Effect |
|---|---|---|
| `app:cleanup-old-chat-messages` | `0 0 * * *`, `withoutOverlapping()` | Deletes chat messages (and their files) older than the `automatically_messages_removed_days` setting (default 30) |
| `sanctum:prune-expired --hours=24` | `0 0 * * *` | Deletes mobile-app tokens that expired more than 24 hours ago |

Both run at midnight in the application timezone, which is the `time_zone` setting (applied on boot by `SettingsConfigService`), falling back to `TIMEZONE` in `.env`. Check with `php artisan schedule:list`. Without the cron entry chat messages are never cleaned up and expired tokens accumulate (they are still rejected). Details in [Scheduled-Tasks-and-Commands](Scheduled-Tasks-and-Commands).

## Caching

Config, route, view and event caching all work:

```bash
php artisan optimize        # config:cache, event:cache, route:cache, view:cache
php artisan optimize:clear  # remove them again
```

- Admin settings (school name, logos, timezone, mail, payment gateways) are read from the database and applied on every boot by `SettingsConfigService`, so they take effect immediately even with a cached config. No code outside `config/` calls `env()`; `tests/Feature/SettingsConfigTest.php` enforces that.
- Re-run `php artisan optimize` after editing `.env`, after deploying new code and after a System Update. `InstallationSeeder` (run by the installer, by System Update and by `/seeder_install`) and the installer's finish step clear the config, route, view and application caches.
- CI checks that the caches build: `php artisan config:cache && php artisan route:cache && php artisan view:cache && php artisan optimize:clear`.
- `bootstrap/cache/config.php` contains every secret from `.env` and, because `config:cache` boots the app, the database values `SettingsConfigService` applied at that moment (SMTP password, Paystack secret key). Keep it out of the web root and out of backups that leave the server unencrypted.
- The admin header's "Cache Clear" button calls `GET /clear` (any signed-in user), which runs `view:clear`, `route:clear`, `config:clear` and `cache:clear`.
- Spatie caches roles and permissions for 24 hours in the default cache store. The package resets that cache when roles or permissions are changed through its models; after editing the tables by hand, run `php artisan permission:cache-reset`.
- With OPcache enabled (`validate_timestamps=0`), reload PHP-FPM after each deployment.

## HTTPS, proxies and hosts

- Set `APP_URL=https://...`. `SESSION_SECURE_COOKIE` then defaults to true (`config/session.php`).
- `SecurityHeaders` sends `Strict-Transport-Security: max-age=31536000` only when `$request->isSecure()`.
- The release builds of the mobile apps require an HTTPS server URL; see [Mobile-App-Configuration](Mobile-App-Configuration).
- Payment gateway webhooks (`POST /webhook/razorpay`, `/webhook/stripe`, `/webhook/paystack`, `/webhook/flutterwave`) must be reachable from the internet; they are exempt from CSRF. See [Payments](Payments).

Behind a load balancer or reverse proxy that terminates TLS: the global stack contains the framework's `Illuminate\Http\Middleware\TrustProxies`, and the app neither calls `$middleware->trustProxies()` nor has a `config/trustedproxy.php`, so no proxy is trusted. `X-Forwarded-Proto` is then ignored: `isSecure()` is false, `url()`, `asset()` and `route()` produce `http://` links (mixed-content warnings, wrong links in emails), and HSTS is not sent. The class `app/Http/Middleware/TrustProxies.php` is not registered, so editing it has no effect. To fix it, add to the `withMiddleware` callback in `bootstrap/app.php`:

```php
$middleware->trustProxies(at: ['10.0.0.0/8']); // your proxy addresses, or '*' if the app is only reachable through the proxy
```

Host header checking is also off: `TrustHosts` is only added when `$middleware->trustHosts()` is called, and the app's `app/Http/Middleware/TrustHosts.php` is not registered. Links built from the request, such as password reset links, use whatever `Host` the client sent. Configure the web server to answer only for your real host names (a catch-all `default_server` that returns 444 on Nginx, or a `ServerName`-only virtual host on Apache), or call `$middleware->trustHosts(at: ['school.example.org'])` in `bootstrap/app.php`.

## Security headers

| Header | Set by | Value |
|---|---|---|
| `X-Frame-Options` | `SecurityHeaders` (global) | `SAMEORIGIN` |
| `X-Content-Type-Options` | `SecurityHeaders`; `storage/app/public/.htaccess` | `nosniff` |
| `Referrer-Policy` | `SecurityHeaders` | `strict-origin-when-cross-origin` |
| `Strict-Transport-Security` | `SecurityHeaders`, HTTPS requests only | `max-age=31536000` |
| `Content-Security-Policy` | `storage/app/public/.htaccess`, SVG/HTML/XML uploads only | `default-src 'none'; img-src 'self' data:; style-src 'unsafe-inline'; sandbox` |

Gaps to cover at the web server if you need them:

- `SecurityHeaders` adds headers after the controller returns, so responses sent by `ResponseService::successResponse()`/`errorResponse()` (which `exit()`), that is most API responses, do not get them.
- Static files under `public/` (CSS, JS, images, `/storage` uploads) are served by the web server without passing through Laravel.
- There is no Content-Security-Policy for the application pages.

## Logs

- `LOG_CHANNEL=stack` writes to the `single` channel, one ever-growing file: `storage/logs/laravel.log`. Use `LOG_CHANNEL=daily` (14 files kept, defined in `config/logging.php`) or logrotate.
- `.env.example` sets `LOG_LEVEL=error`. Keep it at `warning` or higher: `MailService::send()` logs the full email payload at `info` when sending fails, and for credential emails that payload includes the generated plaintext passwords.
- `ResponseService::logErrorResponse()` and `logCurlRequest()` log request parameters and headers at `error` level with keys matching `pass`, `token`, `secret`, `key`, `signature`, `authorization`, `cookie` or `otp` replaced by `[redacted]`.
- The installer has no log; its errors are shown on the page. Exceptions during the wizard before a key exists (`MissingAppKeyException`) appear only in `laravel.log`.
- For live tailing during support, `php artisan pail` is available when dev packages are installed.

## Backups

The application has no backup feature or command. A search for "backup" in `app/`, `routes/`, `config/`, `database/` and the views finds only the warning on the System Update page asking admins to back up first. (The "backups" in the commit "Harden both apps" refers to Android app-data backups in the mobile apps.)

Back up at least:

| What | Why |
|---|---|
| The MySQL database | All school data, settings, roles, tokens |
| `storage/app/public` | Uploaded photos, documents, assignments, chat files, logos |
| `storage/app` (private part, e.g. `firebase/service_account.json`) | FCM credentials |
| `.env` | Database credentials and `APP_KEY` (losing the key only signs everyone out of the panel; no database column is encrypted with it, and Sanctum tokens are hashed independently of it) |
| `storage/installed` | Without it the restored site redirects to `/install` |

Example nightly job (not part of the repo):

```bash
mysqldump --single-transaction --routines -u eschool -p"$DB_PASSWORD" eschool | gzip > /backups/eschool-$(date +%F).sql.gz
tar -czf /backups/eschool-storage-$(date +%F).tar.gz -C /var/www/eschool/admin-panel storage/app .env storage/installed
```

## Maintenance URLs

For hosts without SSH, `routes/web.php` exposes:

| URL | Middleware | Runs |
|---|---|---|
| `GET /clear` | `auth` | `view:clear`, `route:clear`, `config:clear`, `cache:clear` |
| `GET /storage-link` | `auth`, `role:Super Admin` | `storage:link` |
| `GET /migrate` | `auth`, `role:Super Admin` | the four clears, then `migrate --force` |
| `GET /seeder_install` | `auth`, `role:Super Admin` | the four clears, then `db:seed --class=InstallationSeeder --force` |

`SecurityTest` checks that guests are redirected to login from all four, that a Teacher gets 403 on the three Super Admin ones, and that a signed-in user can use `/clear`.

## Deploying an update

With SSH and git (the equivalent of what [System-Update](System-Update) does with a zip):

```bash
cd /var/www/eschool/admin-panel
php artisan down
git pull
composer install --optimize-autoloader
php artisan migrate --force
php artisan db:seed --class=InstallationSeeder --force
php artisan db:seed --class=EmailSeeder --force
php artisan optimize
php artisan up
```

`php artisan down` makes `public/index.php` serve the maintenance page (`storage/framework/maintenance.php`); the health route `GET /up` stays available for load balancers.

## Checklist

- [ ] PHP 8.3 with the extensions above, MySQL/MariaDB database created
- [ ] `composer install --optimize-autoloader` (the container uses `--no-dev`; local installs can include dev packages)
- [ ] `.env` from `.env.example`, `php artisan key:generate`, `APP_URL` with `https://`, `APP_DEBUG=false`
- [ ] Installed through `/install` or the command-line steps in [Installation-Wizard](Installation-Wizard#installing-without-the-wizard); `storage/installed` exists
- [ ] Super Admin password changed from the seeded `superadmin`
- [ ] Document root is `public/`; `public/storage` link exists
- [ ] `storage/` and `bootstrap/cache/` writable by the web user
- [ ] Cron entry for `schedule:run`
- [ ] Trusted proxies and host names configured if behind a proxy
- [ ] `php artisan optimize`
- [ ] Database and `storage/app` backups scheduled

## See also

- [Installation-Wizard](Installation-Wizard)
- [Configuration](Configuration)
- [Scheduled-Tasks-and-Commands](Scheduled-Tasks-and-Commands)
- [System-Update](System-Update)
- [Security-Model](Security-Model)
- [Troubleshooting-and-FAQ](Troubleshooting-and-FAQ)
