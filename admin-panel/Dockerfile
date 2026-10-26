# PHP2Shell (CVE-2026-109618 / CVE-2026-109619): first fixed PHP 8.3 release.
FROM php:8.5.12-apache-bookworm

ENV COMPOSER_ALLOW_SUPERUSER=1

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git unzip libsodium-dev libpng-dev libjpeg62-turbo-dev libfreetype6-dev libzip-dev libicu-dev libonig-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql mbstring exif gd zip sodium bcmath intl opcache pcntl \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . .

RUN cp storage/app/public/.htaccess /opt/public-storage.htaccess \
    && composer install --no-dev --no-scripts --no-interaction --optimize-autoloader \
    && rm -rf /root/.composer \
    && mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R ug+rwx storage bootstrap/cache \
    && sed -i 's/Listen 80/Listen 8080/' /etc/apache2/ports.conf \
    && cp docker/apache-site.conf /etc/apache2/sites-available/000-default.conf \
    && cp docker/php.ini /usr/local/etc/php/conf.d/navigateed.ini \
    && chmod 755 docker/entrypoint.sh

# The release this image was built from (the git tag or commit), shown in Error
# Reporting and the logs. The deploy workflows pass it; local builds say "dev".
ARG APP_RELEASE=dev
ENV APP_RELEASE=${APP_RELEASE}
LABEL org.opencontainers.image.revision=${APP_RELEASE}

# Matches no origin until docker/entrypoint.sh sets the web portals' origins
# for docker/storage-cors.conf (Apache needs the variable to start).
ENV STORAGE_CORS_ORIGINS="(?!)"

# CONTAINER_ROLE picks what the container runs: web (default), worker or
# scheduler. See docker/entrypoint.sh.
EXPOSE 8080

ENTRYPOINT ["/var/www/html/docker/entrypoint.sh"]
