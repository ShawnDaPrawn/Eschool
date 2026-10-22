<?php

declare(strict_types=1);

/*
 * Prints the regular expression of the origins allowed to fetch uploaded
 * files under /storage: config/cors.php's origins (APP_URL and the web
 * portals, CORS_ALLOWED_ORIGINS). docker/entrypoint.sh exports it as
 * STORAGE_CORS_ORIGINS for docker/storage-cors.conf. Reads the cached config
 * when there is one; needs no database.
 */

use App\Services\Platform\StorageCors;
use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

echo StorageCors::fromConfig();
