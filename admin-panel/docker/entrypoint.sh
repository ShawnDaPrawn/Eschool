#!/bin/bash
set -euo pipefail
cd /var/www/html

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
php artisan migrate --force
php docker/provision-admin.php

exec apache2-foreground
