#!/bin/bash
set -euo pipefail
cd /var/www/html

# One image, bounded runtime roles, chosen by CONTAINER_ROLE:
#   web             Apache serving the panel, the API and the public pages (the
#                   default). The only role that migrates the database and runs
#                   the installer seeders.
#   worker          php artisan queue:work, restarted whenever it exits (it stops
#                   itself after an hour); no migrations.
#   content-worker  bounded long-running private conversion queue; no migrations.
#   live-worker     dedicated database/live invalidation relay; no migrations.
#   reverb          signed private websocket service; no migrations.
#   scheduler       one php artisan schedule:run, then exit: a Cloud Run Job that
#                   Cloud Scheduler starts every minute. The schedule itself lives
#                   only in routes/console.php.
#   scheduler-loop  php artisan schedule:work, for docker compose on a laptop.
#   artisan         one php artisan command given as the container's arguments,
#                   then exit: one-off maintenance through the scheduler job, e.g.
#                   gcloud run jobs execute navigateed-scheduler --wait \
#                     --update-env-vars=CONTAINER_ROLE=artisan --args=queue:retry,all
ROLE="${CONTAINER_ROLE:-web}"
case "$ROLE" in
    web|worker|content-worker|live-worker|reverb|scheduler|scheduler-loop|artisan) ;;
    *) echo "Unknown CONTAINER_ROLE '$ROLE' (expected web, worker, content-worker, live-worker, reverb, scheduler, scheduler-loop or artisan)" >&2; exit 64 ;;
esac

mkdir -p storage/app/public storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache

if [ ! -f storage/app/public/.htaccess ]; then
    cp /opt/public-storage.htaccess storage/app/public/.htaccess
fi

ln -sfn /var/www/html/storage/app/public /var/www/html/public/storage

# The upload directory is a Cloud Storage mount. chmod covers the case where
# the mount does not accept chown for the Apache user.
chown -R www-data:www-data storage bootstrap/cache || true
chmod -R ug+rwx storage bootstrap/cache || true
chmod 777 storage/app storage/app/public || true

php artisan package:discover --ansi
php artisan config:cache

# The worker and scheduler run as the web server's user, like Apache's own
# workers, so files they write can be read and replaced by the panel. A prefix,
# not a function: the command must be the shell's direct child (or exec'd) for
# Cloud Run's SIGTERM to reach it.
AS_APP=()
if command -v setpriv >/dev/null 2>&1; then
    AS_APP=(setpriv --reuid=www-data --regid=www-data --init-groups)
fi

run_worker() {
    local queue_args=(--tries=3 --timeout=120 --sleep=3 --max-time=3600)
    if [ "$ROLE" = content-worker ]; then
        queue_args=(academic_content --queue=content --tries=3 --timeout=330 --sleep=3 --max-time=3600)
    fi
    if [ "$ROLE" = live-worker ]; then
        queue_args=(database --queue=live --tries=4 --timeout=20 --sleep=1 --max-time=3600)
    fi
    # Cloud Run sends a service traffic only on $PORT and checks that something
    # listens there; the worker has no public ingress, so this answers only
    # Cloud Run's own probes.
    "${AS_APP[@]}" php -S "0.0.0.0:${PORT:-8080}" docker/worker-health.php >/dev/null 2>&1 &
    local health=$!
    local child=0 stopping=0
    # Cloud Run sends SIGTERM before it stops an instance; queue:work finishes
    # the job in hand and exits, and the loop below does not start it again.
    trap 'stopping=1; [ "$child" -gt 0 ] && kill -TERM "$child" 2>/dev/null || true' TERM INT

    while [ "$stopping" -eq 0 ]; do
        "${AS_APP[@]}" php artisan queue:work "${queue_args[@]}" &
        child=$!
        local status=0
        wait "$child" || status=$?
        if [ "$stopping" -eq 1 ]; then
            # The trap interrupted wait; let the job in hand finish.
            wait "$child" 2>/dev/null || true
            break
        fi
        child=0
        if [ "$status" -ne 0 ]; then
            echo "queue:work exited with status $status; restarting in 5 seconds" >&2
            sleep 5 &
            wait $! || true
        fi
    done

    kill "$health" 2>/dev/null || true
}

case "$ROLE" in
    web)
        php docker/migrate.php
        php docker/provision-admin.php
        # New roles, permissions and email templates reach an existing install here
        # (the System Update page and the /seeder_install URL used to do this). Both
        # seeders are idempotent; InstallationSeeder clears the caches, so the config is
        # cached again after them.
        php artisan db:seed --class=InstallationSeeder --force
        php artisan db:seed --class=EmailSeeder --force
        php artisan config:cache
        # The origins allowed to fetch uploaded files (docker/storage-cors.conf);
        # none when they cannot be worked out, rather than no panel at all.
        STORAGE_CORS_ORIGINS="$(php docker/storage-cors-origins.php || echo '(?!)')"
        export STORAGE_CORS_ORIGINS
        exec apache2-foreground
        ;;
    worker|content-worker|live-worker)
        run_worker
        ;;
    reverb)
        exec "${AS_APP[@]}" php artisan reverb:start --host=0.0.0.0 --port="${PORT:-8080}"
        ;;
    scheduler)
        exec "${AS_APP[@]}" php artisan schedule:run
        ;;
    scheduler-loop)
        exec "${AS_APP[@]}" php artisan schedule:work
        ;;
    artisan)
        [ $# -gt 0 ] || { echo "CONTAINER_ROLE=artisan needs the command as arguments" >&2; exit 64; }
        exec "${AS_APP[@]}" php artisan --no-interaction "$@"
        ;;
esac
