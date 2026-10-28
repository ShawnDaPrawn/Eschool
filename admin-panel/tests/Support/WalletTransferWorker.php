<?php

declare(strict_types=1);
use App\Models\User;
use App\Services\Money\WalletOperations;
use App\Services\Money\WalletTransfers;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Contracts\Console\Kernel;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (! app()->environment('testing') || ! str_ends_with((string) config('database.connections.mysql.database'), '_test')) {
    throw new RuntimeException('Transfer workers require an isolated test schema.');
}
[$script, $actorId, $from, $to, $operation, $mode, $barrier, $slot] = $argv;
app(CurrentSchool::class)->set(1);
file_put_contents($barrier.'.ready.'.$slot, 'ready');
$deadline = microtime(true) + 15;
while (! is_file($barrier.'.go')) {
    if (microtime(true) > $deadline) {
        throw new RuntimeException('Transfer barrier timed out');
    } usleep(10000);
}
try {
    $actor = User::findOrFail((int) $actorId);
    if ($mode === 'resolve') {
        $result = app(WalletOperations::class)->resolve($actor, $operation);
        echo json_encode(['status' => $result['status']], JSON_THROW_ON_ERROR);
    } else {
        $row = app(WalletTransfers::class)->transfer($actor, ['operation_id' => $operation, 'from_wallet_id' => (int) $from, 'to_wallet_id' => (int) $to, 'amount' => '30.00', 'confirmed' => true]);
        echo json_encode(['id' => (int) $row->id], JSON_THROW_ON_ERROR);
    }
} catch (HttpExceptionInterface $e) {
    echo json_encode(['refused' => $e->getStatusCode()], JSON_THROW_ON_ERROR);
}
