<?php

declare(strict_types=1);
use App\Models\User;
use App\Services\Money\WalletDeposits;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Contracts\Console\Kernel;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (! app()->environment('testing') || ! str_ends_with((string) config('database.connections.mysql.database'), '_test')) {
    throw new RuntimeException('Deposit workers require an isolated test schema.');
}
[$script, $actorId, $claim, $operation, $barrier, $slot] = $argv;
app(CurrentSchool::class)->set(1);
file_put_contents($barrier.'.ready.'.$slot, 'ready');
$deadline = microtime(true) + 15;
while (! is_file($barrier.'.go')) {
    if (microtime(true) > $deadline) {
        throw new RuntimeException('Deposit barrier timed out');
    } usleep(10000);
}
try {
    $actor = User::findOrFail((int) $actorId);
    $row = app(WalletDeposits::class)->verify($actor, (int) $claim, ['operation_id' => $operation, 'amount' => '20.00', 'method' => 'eft', 'source_reference' => 'SYNTHETIC-ONE-ACTUAL-RECEIPT', 'received_on' => now()->toDateString(), 'evidence' => 'Independent synthetic concurrent Finance proof', 'verified_original_holder' => true, 'verified_actual_receipt' => true]);
    echo json_encode(['id' => (int) $row->id], JSON_THROW_ON_ERROR);
} catch (HttpExceptionInterface $e) {
    echo json_encode(['refused' => $e->getStatusCode()], JSON_THROW_ON_ERROR);
}
