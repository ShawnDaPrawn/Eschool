<?php

declare(strict_types=1);
use App\Models\User;
use App\Services\Money\WalletWithdrawals;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Contracts\Console\Kernel;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (! app()->environment('testing') || ! str_ends_with((string) config('database.connections.mysql.database'), '_test')) {
    throw new RuntimeException('Withdrawal workers require an isolated test schema.');
}
[$script, $actorId, $withdrawal, $pickup, $operation, $mode, $barrier, $slot] = $argv;
app(CurrentSchool::class)->set(1);
file_put_contents($barrier.'.ready.'.$slot, 'ready');
$deadline = microtime(true) + 15;
while (! is_file($barrier.'.go')) {
    if (microtime(true) > $deadline) {
        throw new RuntimeException('Withdrawal barrier timed out');
    } usleep(10000);
}
try {
    $actor = User::findOrFail((int) $actorId);
    if ($mode === 'paid') {
        $row = app(WalletWithdrawals::class)->finish($actor, (int) $withdrawal, 'paid', ['operation_id' => $operation, 'payout_reference' => 'SYNTHETIC-ONE-PAID-BANK', 'paid_on' => now()->toDateString(), 'private_evidence' => 'Independent actual synthetic payout evidence', 'verified_actual_payout' => true, 'verified_original_holder' => true]);
    } else {
        $row = app(WalletWithdrawals::class)->request($actor, ['operation_id' => $operation, 'amount' => $mode === 'cash' ? '10.00' : ($mode === 'large' ? '600.00' : '300.00'), 'slot_id' => $mode === 'cash' ? (int) $pickup : null, 'private_bank_instructions' => $mode === 'cash' ? null : 'Private synthetic original holder bank', 'confirmed' => true]);
    }
    echo json_encode(['id' => (int) $row->id], JSON_THROW_ON_ERROR);
} catch (HttpExceptionInterface $e) {
    echo json_encode(['refused' => $e->getStatusCode()], JSON_THROW_ON_ERROR);
}
