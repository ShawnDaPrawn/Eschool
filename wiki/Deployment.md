How to run `admin-panel/` in production: NavigateEd's Cloud Run setup (the container roles, staging and production, the deploy pipeline), and for a self-hosted server the requirements, Composer, the web root, file permissions, the storage link, the queue worker, the scheduler, caching, HTTPS and proxies, security headers, logs and backups. It describes what the code needs and does; for a first install see [Installation-Wizard](Installation-Wizard), and for the end-user hosting notes see [Prerequisite](https://shawndaprawn.github.io/Eschool/admin-panel/prerequisite/) and [Installation Steps](https://shawndaprawn.github.io/Eschool/admin-panel/installation-steps/).

## About the README deployment scripts

`admin-panel/README.md` mentions `./complete_upload.sh`, `./backup_old_code.sh` and a `DEPLOYMENT_GUIDE.md`. None of them exist, and its "Manual Deployment" steps are partly wrong for this code base (`php artisan db:seed` seeds nothing). Use this page instead. The deployment scripts that do exist are `admin-panel/docker/entrypoint.sh` (what the container runs on start) and the scripts in [`infra/`](https://github.com/ShawnDaPrawn/Eschool/tree/main/infra) (cloud provisioning, smoke tests, Firebase Hosting).

## NavigateEd on Google Cloud

NavigateEd runs on Cloud Run in `africa-south1` (Johannesburg), one Google Cloud project per environment: staging (`navigateed-staging`) and production (`navigateed-d8c06`, which is also the Firebase project). Both are created and kept up to date by [`infra/gcp/provision.sh`](https://github.com/ShawnDaPrawn/Eschool/blob/main/infra/gcp/provision.sh), with settings per environment in `infra/gcp/env/`. The operational detail (what exists, sizes, permissions, the product owner's one-time setup) is in [`engineering/runbooks/environments.md`](https://github.com/ShawnDaPrawn/Eschool/blob/main/engineering/runbooks/environments.md).

**Status.** The provisioning script and the deploy workflows were written in Phase 0 without cloud credentials and have not run yet. Until the product owner runs them, production is as it was: the panel alone on Cloud Run at https://navigateed-admin-972068206844.africa-south1.run.app/, Cloud SQL `navigateed-mysql` on `db-f1-micro`, uploads in `navigateed-d8c06-admin`, and nothing running the queue or the schedule.

| Piece | Staging | Production |
|---|---|---|
| `navigateed-admin` (Cloud Run service, web role) | public, 0 to 4 instances | public, 1 to 10 instances |
| `navigateed-worker` (Cloud Run service, worker role) | internal ingress, 1 to 2 instances, CPU always allocated | internal ingress, 1 to 3 instances, CPU always allocated |
| `navigateed-scheduler` (Cloud Run Job, scheduler role) | started every minute by Cloud Scheduler | same |
| Cloud SQL `navigateed-mysql`, MySQL 8.0 | `db-g1-small`, daily backups | `db-custom-2-7680` with high availability, daily backups (14 kept), 7-day point-in-time recovery |
| Uploads | `gs://navigateed-staging-admin`, mounted at `storage/app` in all three roles | `gs://navigateed-d8c06-admin`, versioned |
| Secrets | Secret Manager: `navigateed-app-key`, `navigateed-db-password`, `navigateed-admin-password` (mounted as `APP_KEY`, `DB_PASSWORD`, `ADMIN_PASSWORD`) | same names |
| Logs | `LOG_CHANNEL=json` to Cloud Logging, Error Reporting | same |
| Deployed by | every merge to `main` (once `STAGING_DEPLOY_ENABLED` is `true`) | a `v*` tag, after the product owner approves |

All three roles run the same image with the same environment: `APP_ENV=production` (staging too, so it behaves the same), `LOG_CHANNEL=json`, `DB_SOCKET=/cloudsql/<project>:africa-south1:navigateed-mysql`, `CACHE_DRIVER=database`, `SESSION_DRIVER=database` and `QUEUE_CONNECTION=database`, so rate limits, the scheduler's overlap locks and the queue are shared across instances. Each role runs under its own service account with only the access it needs.

`config/trustedproxy.php` trusts Google's front end on Cloud Run (it detects `K_SERVICE`), so HTTPS stays HTTPS and rate limits see each visitor's real address. Once the panel is installed, `bootstrap/app.php` trusts the `APP_URL` host, `localhost`, `127.0.0.1` and `TRUSTED_HOSTS`. A production `APP_URL` that does not match the browser host returns 400; see [HTTPS, proxies and hosts](#https-proxies-and-hosts). When a custom domain is added, the provisioning script keeps the `run.app` address in `TRUSTED_HOSTS`.

The request limits (see [Security-Model](Security-Model#request-limits)) stop one client from overloading the service, but not a flood from many addresses. For that, and for the custom domain `admin.navigateed.co.za` (Cloud Run domain mappings are not offered in `africa-south1`), put the panel behind a global external Application Load Balancer with a serverless network endpoint group and a Cloud Armor policy (rate-based rules, and Adaptive Protection if available), and set the Cloud Run ingress to "internal and Cloud Load Balancing". The maximum instance count caps the bill during a flood.

### Container roles

[`admin-panel/docker/entrypoint.sh`](https://github.com/ShawnDaPrawn/Eschool/blob/main/admin-panel/docker/entrypoint.sh) prepares the storage directories and the `public/storage` link, caches the config, then runs one of these, chosen by `CONTAINER_ROLE`:

| `CONTAINER_ROLE` | Runs | Where |
|---|---|---|
| `web` (default) | Migrations (`docker/migrate.php`: `migrate --force` under a MySQL `GET_LOCK`, so instances that start together migrate one at a time), admin provisioning (`docker/provision-admin.php`), `InstallationSeeder` and `EmailSeeder` (new roles, permissions and e-mail templates reach an existing install), config cache again, then Apache on port 8080 | `navigateed-admin` |
| `worker` | `php artisan queue:work --tries=3 --timeout=120 --sleep=3 --max-time=3600` as `www-data`, restarted whenever it exits (hourly by `--max-time`, after 5 seconds on a failure). On SIGTERM it finishes the job in hand and stops. A tiny `php -S` on `$PORT` answers Cloud Run's probes. No migrations | `navigateed-worker` |
| `scheduler` | One `php artisan schedule:run`, then exit | `navigateed-scheduler`, started every minute by Cloud Scheduler |
| `scheduler-loop` | `php artisan schedule:work` (the scheduler in the foreground) | Docker Compose |
| `artisan` | One Artisan command given as the container's arguments, then exit | One-off maintenance as an execution of the scheduler job: `gcloud run jobs execute navigateed-scheduler --wait --update-env-vars=CONTAINER_ROLE=artisan --args=queue:retry,all` |

Only the web role migrates and seeds. The image includes the `pcntl` extension, which `queue:work` needs for `--timeout` and a clean stop, and sets `zend.exception_ignore_args=On` so exception traces never carry function arguments. The build argument `APP_RELEASE` (the tag or commit) is shown in the logs and Error Reporting. [`docker-compose.yml`](https://github.com/ShawnDaPrawn/Eschool/blob/main/docker-compose.yml) runs the same roles locally; see [Getting-Started](Getting-Started#option-c-docker-compose-the-cloud-run-setup-on-your-machine).

On the first start against an empty database, the web role migrates, seeds the installer data, replaces the seeded `superadmin@gmail.com` / `superadmin` login with `ADMIN_EMAIL` (default `info@tokensoft.tech`) and `ADMIN_PASSWORD`, and writes `storage/installed`. It replaces that login again on any start where it is still in place (for example after the database was recreated while the `.admin-provisioned` marker on the bucket stayed), and refuses to start if `ADMIN_PASSWORD` is then empty.

### Deploys

`.github/workflows/deploy-staging.yml` (every push to `main`) and `deploy-production.yml` (a `v*` tag, approved in the `production` GitHub environment) call `deploy-environment.yml`, which authenticates through Workload Identity Federation (no stored key), backs up the database (production), builds and pushes the image, deploys `navigateed-admin`, then `navigateed-worker`, updates the scheduler job, builds the Flutter web portals, deploys Firebase Hosting, and runs `infra/deploy/smoke.sh`. Cloud Run sends traffic to a new panel revision only once it is ready, so the migrations finish while the previous revision still serves; migrations are additive, so that is safe. Configuration (environment, secrets, instance counts) is set by the provisioning script, not by a deploy. Steps and troubleshooting: [`engineering/runbooks/deploy.md`](https://github.com/ShawnDaPrawn/Eschool/blob/main/engineering/runbooks/deploy.md); releases: [`release.md`](https://github.com/ShawnDaPrawn/Eschool/blob/main/engineering/runbooks/release.md); rollback: [`rollback.md`](https://github.com/ShawnDaPrawn/Eschool/blob/main/engineering/runbooks/rollback.md).

### Monitoring

The provisioning script creates uptime checks every 5 minutes on `/login` (panel), `/up` (API) and `/up/worker` (the worker: 503 once a due job has waited more than 10 minutes), and alerts by e-mail for each check, for error log lines above a threshold, for any "Queued job failed" line, for failing scheduler runs and for a worker with no instance. Error Reporting groups the exceptions from the JSON logs. What to do when one fires: [`engineering/runbooks/incident.md`](https://github.com/ShawnDaPrawn/Eschool/blob/main/engineering/runbooks/incident.md).

## Server requirements

For a self-hosted server. The container image covers all of this on Cloud Run.

| Component | Requirement | Source |
|---|---|---|
| PHP | 8.3 or newer | `composer.json` (`"php": "^8.3.0"`, platform `8.3.0`), installer check `80300`, CI uses 8.3 |
| Database | MySQL 8 (MySQL 5.7+ or MariaDB also run the migrations) | CI runs MariaDB 10.11; Cloud SQL and Docker Compose run MySQL 8.0 |
| Web server | Apache with `mod_rewrite`, or Nginx with PHP-FPM | `.htaccess` files in the project root, `public/` and `storage/app/public/` |
| Composer | 2.x | `composer.lock` |
| Node.js | Not needed | Assets are prebuilt in `public/assets`; see [Admin-Panel-Overview](Admin-Panel-Overview#front-end-build-vite) |
| A process manager | Needed for the queue worker | See [Queue worker](#queue-worker) |
| Cron | Needed for the scheduler | `routes/console.php` |
| Outbound HTTPS | FCM (`fcm.googleapis.com`, Google OAuth), Stripe, Google reCAPTCHA, the mail server or provider | |

`composer.json` sets `"platform-check": false`, so Composer and Laravel will not stop you if an extension is missing; the failure shows up later at runtime. PHP extensions:

| Extension | Why |
|---|---|
| `pdo_mysql` | Database (installer check) |
| `mbstring`, `tokenizer`, `openssl`, `ctype`, `filter`, `hash`, `session`, `json`, `pcre` | Laravel and its dependencies (installer checks the first four plus `json`) |
| `fileinfo` | Upload MIME detection in `RejectDangerousUploads`, Flysystem (installer check) |
| `curl` | FCM pushes (`sendFCMCurl`), Stripe SDK (installer check) |
| `gd` | Intervention Image GD driver (`UploadService`, `ImageService`, `resizeImage()`), PhpSpreadsheet. Not checked by the installer. |
| `zip` | PhpSpreadsheet imports. Not checked by the installer. |
| `pcntl` | `queue:work`: job timeouts (`--timeout`) and a clean stop on SIGTERM. Not checked by the installer. |
| `dom`, `libxml`, `xml`, `simplexml`, `xmlreader`, `xmlwriter` | DomPDF, `enshrined/svg-sanitize`, PhpSpreadsheet. Not checked by the installer. |
| `iconv`, `zlib` | PhpSpreadsheet, ZipStream. Not checked by the installer. |

CI installs `mbstring, intl, gd, zip, pdo_mysql, bcmath, exif, fileinfo` on top of the runner's defaults; the image adds `opcache` and `pcntl`.

PHP settings to review: `upload_max_filesize` and `post_max_size` must allow your largest upload (Laravel's `ValidatePostSize` answers 413 when a request exceeds `post_max_size`; the image allows 32 MB); `symlink()` must be allowed for `storage:link`; `zend.exception_ignore_args=On` keeps arguments out of exception traces.

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

The web server (PHP) user, and the user the queue worker and scheduler run as, need to write to:

| Path | Why |
|---|---|
| `storage/` (all of it: `app`, `app/public`, `framework/cache`, `framework/sessions`, `framework/views`, `logs`) | Uploads, private files, sessions, file cache, compiled views, logs, the `storage/installed` marker |
| `bootstrap/cache/` | `packages.php`, `services.php` and, when you cache them, `config.php`, `routes-v7.php`, `events.php` |
| `.env` | Only while the installer runs (it writes `DB_*`, `APP_URL` and `APP_KEY`). The admin settings pages never write `.env`, so it can be made read-only afterwards. |
| `public/` | Only to create the `public/storage` link (the installer and `php artisan storage:link` do this). |

Keep the code itself read-only for the web user. The installer's folder step checks the mode bits of `storage/framework`, `storage/logs` and `bootstrap/cache` (at least `755`), not real writability, so a green tick does not prove the web user can write there.

A common layout is code owned by a deploy user, with the web server group allowed to write to the runtime directories:

```bash
cd /var/www/eschool/admin-panel
sudo chown -R deploy:www-data .
sudo find storage bootstrap/cache -type d -exec chmod 2775 {} \;
sudo find storage bootstrap/cache -type f -exec chmod 664 {} \;
chmod 640 .env
```

In the container the entrypoint makes `storage/` and `bootstrap/cache/` writable for `www-data`, and the worker and scheduler run as `www-data` too.

## Storage link and uploaded files

Uploads go to the `public` disk, `storage/app/public`, and are served at `/storage/...` through the symlink `public/storage` (`config/filesystems.php` `links`).

```bash
php artisan storage:link
```

- The installer runs `storage:link --force` in its Keys step, and the container's entrypoint recreates the link on every start.
- The old `/storage-link` URL was removed in Phase 0; on a host without SSH, the installer's link is the one to rely on.
- `public/storage` is in `.gitignore`; recreate it on every new checkout.
- `storage/app/public/.htaccess` (Apache only) turns off PHP, denies script extensions and dotfiles, sends `nosniff`, and serves SVG, HTML and XML with a sandboxing `Content-Security-Policy`. On Nginx you must configure the same yourself (example above). The first line of defence is the `RejectDangerousUploads` middleware on the `web` and `api` groups, which refuses script and web-page uploads and sanitises SVGs.
- Private files live on the `local` disk, `storage/app`, for example the Firebase service account at `storage/app/firebase/service_account.json`. They must stay outside the web root. On Cloud Run, `storage/app` is the Cloud Storage bucket.

## Queue worker

From Phase 0 the queue carries work off the request path (mail and pushes as queued jobs), so production needs a worker. Use the database queue:

```dotenv
QUEUE_CONNECTION=database
```

On Cloud Run the `navigateed-worker` service runs it (the worker role above). On your own server, run it under a process manager that restarts it, for example Supervisor:

```ini
[program:eschool-worker]
command=php /var/www/eschool/admin-panel/artisan queue:work --tries=3 --timeout=120 --sleep=3 --max-time=3600
user=www-data
autostart=true
autorestart=true
stopwaitsecs=150
stdout_logfile=/var/log/eschool-worker.log
redirect_stderr=true
```

- `--max-time=3600` makes the worker exit every hour (the process manager starts a fresh one, freeing memory); `--timeout=120` stops a job that runs longer, which needs the `pcntl` extension.
- The database queue's `retry_after` is 180 seconds (`config/queue.php`), longer than `--timeout`, so a job still running is never handed to a second worker. Keep that order if you change either.
- After deploying new code, run `php artisan queue:restart` so running workers pick it up (a Cloud Run deploy starts new worker instances instead).
- A job that fails three times goes to the `failed_jobs` table and writes one "Queued job failed" error line with the job's name and id. `php artisan queue:failed` lists them and `php artisan queue:retry all` retries them once the cause is fixed.
- `GET /up/worker` answers 503 once a due job has waited longer than `QUEUE_HEALTH_MAX_WAIT` seconds (default 600); point an uptime check at it.

With `QUEUE_CONNECTION=sync` (the `.env.example` default, fine for development) jobs run inside the request that queues them and no worker is needed.

## Scheduler

The schedule lives only in `routes/console.php`. Something must call `php artisan schedule:run` every minute:

- **Cloud Run**: the `navigateed-scheduler` job (scheduler role) is started every minute by the Cloud Scheduler job `navigateed-scheduler-every-minute`. Each run is a fresh container, so `withoutOverlapping()` relies on the shared database cache (`CACHE_DRIVER=database`).
- **Your own server**: the standard Laravel cron entry for the web server user:

```cron
* * * * * cd /var/www/eschool/admin-panel && php artisan schedule:run >> /dev/null 2>&1
```

| Task | Cron | Effect |
|---|---|---|
| `app:cleanup-old-chat-messages` | `0 0 * * *`, `withoutOverlapping()` | Deletes chat messages (and their files) older than the `automatically_messages_removed_days` setting (default 30) |
| `sanctum:prune-expired --hours=24` | `0 0 * * *` | Deletes mobile-app tokens that expired more than 24 hours ago |

Both run at midnight in the application timezone, which is the `time_zone` setting (applied on boot by `SettingsConfigService`), falling back to `TIMEZONE` in `.env`. Check with `php artisan schedule:list`. Without the scheduler, chat messages are never cleaned up and expired tokens accumulate (they are still rejected). Details in [Scheduled-Tasks-and-Commands](Scheduled-Tasks-and-Commands).

## Caching

Config, route, view and event caching all work:

```bash
php artisan optimize        # config:cache, event:cache, route:cache, view:cache
php artisan optimize:clear  # remove them again
```

- Admin settings (school name, logos, timezone, mail, payment) are read from the database and applied on every boot by `SettingsConfigService`, so they take effect immediately even with a cached config. No code outside `config/` calls `env()`; `tests/Feature/SettingsConfigTest.php` enforces that.
- Re-run `php artisan optimize` after editing `.env` and after deploying new code. `InstallationSeeder` (run by the installer, and by the container's web role on every start) and the installer's finish step clear the config, route, view and application caches; the entrypoint caches the config again afterwards.
- CI checks that the caches build: `php artisan config:cache && php artisan route:cache && php artisan view:cache && php artisan optimize:clear`.
- `bootstrap/cache/config.php` contains every secret from `.env` and, because `config:cache` boots the app, the database values `SettingsConfigService` applied at that moment (for example the SMTP password). Keep it out of the web root and out of backups that leave the server unencrypted.
- The admin header's "Cache Clear" button posts to `POST /clear` (Super Admin only), which runs `view:clear`, `route:clear`, `config:clear` and `cache:clear`.
- Spatie caches roles and permissions for 24 hours in the default cache store. The package resets that cache when roles or permissions are changed through its models; after editing the tables by hand, run `php artisan permission:cache-reset`. With more than one server (or on Cloud Run), use a shared default store (`CACHE_DRIVER=database`) so a reset reaches every instance.
- With OPcache enabled (`validate_timestamps=0`, as in the image), reload PHP-FPM after each deployment.

## HTTPS, proxies and hosts

- Set `APP_URL=https://...`. `SESSION_SECURE_COOKIE` then defaults to true (`config/session.php`).
- `SecurityHeaders` sends `Strict-Transport-Security: max-age=31536000` only when `$request->isSecure()`.
- The release builds of the mobile apps require an HTTPS server URL. The apps take it from the `API_BASE_URL` build setting (`--dart-define`, or `config/production.json` and `config/staging.json`), so point a build at this server there; see [Mobile-App-Configuration](Mobile-App-Configuration#build-settings).
- The Stripe webhook (`POST /webhook/stripe`) must be reachable from the internet; it is exempt from CSRF. See [Payments](Payments). From Phase 0 the mail provider's bounce and complaint webhook joins it (ADR 0004).

Behind a load balancer or reverse proxy that terminates TLS, set `TRUSTED_PROXIES` in `.env` to the proxy's addresses or ranges, comma-separated (read by [config/trustedproxy.php](https://github.com/ShawnDaPrawn/Eschool/blob/main/admin-panel/config/trustedproxy.php)). Use `*` only if visitors cannot reach the server except through the proxy. Without it, `X-Forwarded-Proto` is ignored: `isSecure()` is false, `url()`, `asset()` and `route()` produce `http://` links, HSTS is not sent, and every visitor counts as the proxy's address in the rate limits. On Cloud Run (`K_SERVICE` set) the default is `*`, because only Google's front end can reach the container. From trusted proxies only `X-Forwarded-For` and `X-Forwarded-Proto` are used; `X-Forwarded-Host`, `-Port` and `-Prefix` are ignored, because Cloud Run passes a visitor's own copies through. The classes `app/Http/Middleware/TrustProxies.php` and `TrustHosts.php` are not registered, so editing them has no effect.

Host names are checked too (`$middleware->trustHosts()`), so links built from the request can't be pointed at another site. With `APP_ENV=production`, a request whose `Host` is not one of these gets 400 Bad Request:

- the `APP_URL` host and its subdomains;
- `localhost` and `127.0.0.1`;
- each name in `TRUSTED_HOSTS` (`config('app.trusted_hosts')`, comma separated), for a school reached under more than one name or by IP address.

The check starts once `storage/installed` exists: before that, `APP_URL` is still `.env.example`'s `http://localhost`, so the install wizard answers on whatever address the school opens it on, and its last step writes the real `APP_URL`. `APP_ENV=local` and the test suite skip the check. Password reset links are built from `APP_URL` whatever host a request names, so `APP_URL` must be the address people use.

With more than one server, point `CACHE_LIMITER` at a cache store they share (`database` or `redis`) so rate limits and the wrong-password lockout count across all of them.

## Security headers

| Header | Set by | Value |
|---|---|---|
| `X-Frame-Options` | `SecurityHeaders` (global) | `SAMEORIGIN` |
| `X-Content-Type-Options` | `SecurityHeaders`; `storage/app/public/.htaccess` | `nosniff` |
| `Referrer-Policy` | `SecurityHeaders` | `strict-origin-when-cross-origin` |
| `Strict-Transport-Security` | `SecurityHeaders`, HTTPS requests only | `max-age=31536000` |
| `X-Request-Id` | `AssignRequestId` (outermost global middleware) | the request's id, also on every log line of the request |
| `Content-Security-Policy` | `storage/app/public/.htaccess`, SVG/HTML/XML uploads only | `default-src 'none'; img-src 'self' data:; style-src 'unsafe-inline'; sandbox` |

Since Phase 0, `ResponseService` returns its responses instead of ending the request, so API responses pass through the response middleware and get these headers too. Gaps to cover at the web server if you need them:

- Static files under `public/` (CSS, JS, images, `/storage` uploads) are served by the web server without passing through Laravel.
- A Content-Security-Policy for the application pages arrives in Phase 0 (report-only first); see [Security-Model](Security-Model).

## Logs

- **Cloud Run**: `LOG_CHANNEL=json` writes one JSON object per line to stderr, which Cloud Logging stores and Error Reporting reads. Every line has `severity`, `message`, `time` (UTC), `request_id`, `user_id`, `school_id` (null until Phase 1), `route` and `method`; lines written by a queued job add `job` and `job_id`, and keep the `request_id` of the request that queued it. Errors with an exception carry a stack trace (without function arguments) in Error Reporting's format. Find a user's request by the `X-Request-Id` header they saw: `jsonPayload.request_id="<id>"`.
- **No personal data beyond ids.** The json channel keeps log context only when it cannot hold personal data (numbers, booleans, values under keys ending in `id`, and short strings under a few descriptive keys such as `status`, `type` or `job`); every other string becomes `[omitted]`, so a request body passed as context never reaches the log. E-mail addresses, South African ID numbers and phone numbers in messages are masked. Details: [Configuration](Configuration#logging).
- **Self-hosted**: `LOG_CHANNEL=stack` writes to the `daily` channel, `storage/logs/laravel-YYYY-MM-DD.log`, 14 days kept. `LOG_CHANNEL=json` works anywhere and writes to stderr.
- `.env.example` sets `LOG_LEVEL=error`; Cloud Run runs `info`. `MailService::send()` logs failed emails at `info`, without their body and password fields.
- Until mail is set up (on the email settings page, or `MAIL_MAILER`), the default mailer is `log`, which writes every email to the log in full, including the passwords in welcome emails. Set up mail before adding staff, students or parents.
- `ResponseService::logErrorResponse()` and `logCurlRequest()` log request parameters and headers at `error` level with keys matching `pass`, `token`, `secret`, `key`, `signature`, `authorization`, `cookie` or `otp` replaced by `[redacted]`, each value cut at 1000 bytes and the whole entry at 10 KB. Under the json channel the parameters passed as context are reduced to ids; the curl line puts them in the message text, which is a known gap.
- The installer has no log; its errors are shown on the page. Exceptions during the wizard before a key exists (`MissingAppKeyException`) appear only in `laravel.log`.
- For live tailing during support, `php artisan pail` is available when dev packages are installed; on Cloud Run use Cloud Logging's live tail.

## Backups

**Cloud Run.** Cloud SQL takes a daily automated backup (01:00 UTC; 14 kept in production, 7 in staging), production keeps 7 days of binary logs for point-in-time recovery, the production deploy takes an on-demand backup before every release, and the production uploads bucket keeps replaced or deleted files for 30 days. Restoring, and the restore drill (yesterday's production backup restored into staging, then the panel signs in), are in [`engineering/runbooks/restore.md`](https://github.com/ShawnDaPrawn/Eschool/blob/main/engineering/runbooks/restore.md). The drill has not been run yet.

**Self-hosted.** The application has no backup feature or command. Back up at least:

| What | Why |
|---|---|
| The MySQL database | All school data, settings, roles, tokens |
| `storage/app/public` | Uploaded photos, documents, assignments, chat files, logos |
| `storage/app` (private part, e.g. `firebase/service_account.json`) | FCM credentials |
| `.env` | Database credentials and `APP_KEY` (losing the key only signs everyone out of the panel; no database column is encrypted with it, and Sanctum tokens are hashed independently of it) |
| `storage/installed` | Without it a restored site with an empty database redirects to `/install`. A database that already has users counts as installed, so the installer stays closed either way |

Example nightly job (not part of the repo):

```bash
mysqldump --single-transaction --routines -u eschool -p"$DB_PASSWORD" eschool | gzip > /backups/eschool-$(date +%F).sql.gz
tar -czf /backups/eschool-storage-$(date +%F).tar.gz -C /var/www/eschool/admin-panel storage/app .env storage/installed
```

## Maintenance URL

| URL | Middleware | Runs |
|---|---|---|
| `POST /clear` (the Cache Clear button) | `auth`, `role:Super Admin` | `view:clear`, `route:clear`, `config:clear`, `cache:clear` |

The in-panel System Update and the GET maintenance URLs (`/storage-link`, `/migrate`, `/seeder_install`) were removed in Phase 0. Their work happens at deploy time: the container's web role links storage, migrates and runs the installer seeders on every start; on your own server, run the commands in [Deploying an update](#deploying-an-update).

## Deploying an update

**Cloud Run**: merge to `main` for staging; tag a release for production ([`engineering/runbooks/release.md`](https://github.com/ShawnDaPrawn/Eschool/blob/main/engineering/runbooks/release.md)).

**Your own server**, with SSH and git:

```bash
cd /var/www/eschool/admin-panel
php artisan down
git pull
composer install --optimize-autoloader
php artisan migrate --force
php artisan db:seed --class=InstallationSeeder --force
php artisan db:seed --class=EmailSeeder --force
php artisan optimize
php artisan queue:restart
php artisan up
```

`php artisan down` makes `public/index.php` serve the maintenance page (`storage/framework/maintenance.php`); the health route `GET /up` stays available for load balancers.

## Checklist

- [ ] PHP 8.3 with the extensions above (including `pcntl`), MySQL database created
- [ ] `composer install --optimize-autoloader` (the container uses `--no-dev`; local installs can include dev packages)
- [ ] `.env` from `.env.example`, `php artisan key:generate`, `APP_URL` with `https://`, `APP_DEBUG=false`
- [ ] Installed through `/install` or the command-line steps in [Installation-Wizard](Installation-Wizard#installing-without-the-wizard); `storage/installed` exists
- [ ] Super Admin password changed from the seeded `superadmin` (the panel insists at the first sign-in; the container replaces the login itself)
- [ ] Document root is `public/`; `public/storage` link exists
- [ ] `storage/` and `bootstrap/cache/` writable by the web user
- [ ] `QUEUE_CONNECTION=database` and a queue worker under a process manager
- [ ] Cron entry (or Cloud Scheduler) for `schedule:run`
- [ ] `TRUSTED_PROXIES` set if behind a proxy (not needed on Cloud Run), and `CACHE_DRIVER`/`CACHE_LIMITER` pointing at a shared store if there is more than one server
- [ ] `php artisan optimize`
- [ ] Database and `storage/app` backups scheduled, and a restore tried once
- [ ] Uptime checks on `/login`, `/up` and `/up/worker`

## See also

- [Installation-Wizard](Installation-Wizard)
- [Configuration](Configuration)
- [Scheduled-Tasks-and-Commands](Scheduled-Tasks-and-Commands)
- [Getting-Started](Getting-Started)
- [Security-Model](Security-Model)
- [Troubleshooting-and-FAQ](Troubleshooting-and-FAQ)
- [Engineering runbooks](https://github.com/ShawnDaPrawn/Eschool/tree/main/engineering/runbooks)
