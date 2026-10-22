<?php

declare(strict_types=1);

/*
 * The router `php artisan serve` uses (local development only; the container
 * serves with Apache). It is Laravel's own router, plus what
 * docker/storage-cors.conf does in the container: an uploaded file under
 * /storage is sent with Access-Control-Allow-Origin for a GET or HEAD from an
 * origin config/cors.php allows (a web portal on another local port), and
 * with Vary: Origin. Without it the portals cannot show pictures or PDFs.
 */

use App\Services\Platform\StorageCors;
use Illuminate\Contracts\Console\Kernel;

$publicPath = getcwd();
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '');
$storage = realpath($publicPath.'/storage');
$file = str_starts_with($uri, '/storage/') ? realpath($publicPath.$uri) : false;

// Only files inside the storage link, never dot files (as its .htaccess).
if ($storage !== false && $file !== false && is_file($file)
    && str_starts_with($file, $storage.DIRECTORY_SEPARATOR) && ! str_starts_with(basename($file), '.')) {
    $origin = (string) ($_SERVER['HTTP_ORIGIN'] ?? '');
    $method = (string) ($_SERVER['REQUEST_METHOD'] ?? 'GET');
    header('Vary: Origin');
    if ($origin !== '') {
        require __DIR__.'/vendor/autoload.php';
        $app = require __DIR__.'/bootstrap/app.php';
        $app->make(Kernel::class)->bootstrap();
        if (StorageCors::allows(StorageCors::fromConfig(), $origin, $method)) {
            header('Access-Control-Allow-Origin: '.$origin);
        }
    }
    header('Content-Type: '.(mime_content_type($file) ?: 'application/octet-stream'));
    header('Content-Length: '.filesize($file));
    header('X-Content-Type-Options: nosniff');
    if (strtoupper($method) !== 'HEAD') {
        readfile($file);
    }

    return true;
}

return require __DIR__.'/vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php';
