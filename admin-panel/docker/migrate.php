<?php

declare(strict_types=1);

/*
 * Runs `php artisan migrate --force` for the web role, one instance at a time.
 *
 * When Cloud Run starts several instances of a new revision together (minimum
 * instances, or a traffic spike during a deploy), each would migrate at once
 * and race on the same tables. A MySQL named lock lets the first instance
 * migrate while the others wait, then find nothing left to do. The lock is the
 * database's own, so it works on a fresh database before any cache table exists.
 */

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$lock = 'navigateed:migrate';
$waitSeconds = 600;

$connection = DB::connection();
$acquired = (int) ($connection->selectOne('SELECT GET_LOCK(?, ?) AS acquired', [$lock, $waitSeconds])->acquired ?? 0);
if ($acquired !== 1) {
    fwrite(STDERR, "Gave up after {$waitSeconds}s waiting for another instance to finish migrating\n");
    exit(1);
}

try {
    $exit = $kernel->call('migrate', ['--force' => true]);
    echo $kernel->output();
} finally {
    $connection->selectOne('SELECT RELEASE_LOCK(?) AS released', [$lock]);
}

exit($exit);
