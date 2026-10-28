<?php

declare(strict_types=1);

use App\Models\User;
use App\Services\Commerce\WalletCounterPayments;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Contracts\Console\Kernel;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (! app()->environment('testing') || ! str_ends_with((string) config('database.connections.mysql.database'), '_test')) {
    throw new RuntimeException('Counter payment workers require an isolated test schema.');
}
[$script, $school, $actorId, $vendor, $intent, $token, $operation, $mode, $barrier, $slot] = $argv;
app(CurrentSchool::class)->set((int) $school);
file_put_contents($barrier.'.ready.'.$slot, 'ready');
$deadline = microtime(true) + 15;
while (! is_file($barrier.'.go')) {
    if (microtime(true) > $deadline) {
        throw new RuntimeException('Counter payment barrier timed out');
    }
    usleep(10000);
}
try {
    $actor = User::findOrFail((int) $actorId);
    $service = app(WalletCounterPayments::class);
    $row = match ($mode) {
        'cancel' => $service->cancel($actor, $intent),
        'resolve' => $service->resolve($actor, (int) $vendor, $operation),
        default => $service->pay($actor, (int) $vendor, ['operation_id' => $operation, 'intent_id' => $intent, 'qr_token' => $token, 'confirmed' => true]),
    };
    echo json_encode(['status' => $row['status'], 'id' => $row['id'] ?? null, 'replayed' => $row['replayed'] ?? null], JSON_THROW_ON_ERROR);
} catch (HttpExceptionInterface $e) {
    echo json_encode(['refused' => $e->getStatusCode()], JSON_THROW_ON_ERROR);
}
