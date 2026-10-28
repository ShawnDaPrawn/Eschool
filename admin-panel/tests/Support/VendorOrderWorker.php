<?php

declare(strict_types=1);

use App\Models\Commerce\VendorOrder;
use App\Models\User;
use App\Services\Commerce\VendorOrders;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Contracts\Console\Kernel;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (! app()->environment('testing') || ! str_ends_with((string) config('database.connections.mysql.database'), '_test')) {
    throw new RuntimeException('Order workers require an isolated test schema.');
}
[$script, $school, $actorId, $vendor, $orderId, $quote, $operation, $mode, $barrier, $slot] = $argv;
app(CurrentSchool::class)->set((int) $school);
file_put_contents($barrier.'.ready.'.$slot, 'ready');
$deadline = microtime(true) + 15;
while (! is_file($barrier.'.go')) {
    if (microtime(true) > $deadline) {
        throw new RuntimeException('Order barrier timed out');
    }usleep(10000);
}
try {
    $actor = User::findOrFail((int) $actorId);
    $service = app(VendorOrders::class);
    $row = match ($mode) {
        'collect' => $service->collect($actor, (int) $vendor, ['operation_id' => $operation, 'order_number' => VendorOrder::findOrFail((int) $orderId)->order_number, 'confirmed' => true]),
        'cancel' => $service->cancel($actor, (int) $orderId, ['operation_id' => $operation, 'private_reason' => 'Synthetic original buyer cancellation before collection', 'confirmed' => true]),
        default => $service->purchase($actor, ['operation_id' => $operation, 'quote' => $quote, 'confirmed' => true]),
    };
    echo json_encode(['id' => (int) $row->id], JSON_THROW_ON_ERROR);
} catch (HttpExceptionInterface $e) {
    echo json_encode(['refused' => $e->getStatusCode()], JSON_THROW_ON_ERROR);
}
