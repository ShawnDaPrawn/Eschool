<?php

declare(strict_types=1);

use App\Models\User;
use App\Services\Commerce\VendorCatalogues;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Contracts\Console\Kernel;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (! app()->environment('testing') || ! str_ends_with((string) config('database.connections.mysql.database'), '_test')) {
    throw new RuntimeException('Stock workers require an isolated test schema.');
}
[$script, $actorId, $product, $operation, $mode, $barrier, $slot] = $argv;
app(CurrentSchool::class)->set(1);
file_put_contents($barrier.'.ready.'.$slot, 'ready');
$deadline = microtime(true) + 15;
while (! is_file($barrier.'.go')) {
    if (microtime(true) > $deadline) {
        throw new RuntimeException('Stock barrier timed out');
    } usleep(10000);
}
try {
    $row = app(VendorCatalogues::class)->stock(User::findOrFail((int) $actorId), ['operation_id' => $operation, 'product_id' => (int) $product, 'source_reference' => $mode === 'same' ? 'ONE-SYNTHETIC-DELIVERY' : 'REMOVAL-'.$slot, 'kind' => $mode === 'same' ? 'receipt' : 'waste', 'units_delta' => $mode === 'same' ? 2 : -7, 'private_reason' => 'Synthetic independently counted stock change']);
    echo json_encode(['id' => (int) $row->id], JSON_THROW_ON_ERROR);
} catch (HttpExceptionInterface $e) {
    echo json_encode(['refused' => $e->getStatusCode()], JSON_THROW_ON_ERROR);
}
