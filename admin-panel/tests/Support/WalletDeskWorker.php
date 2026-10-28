<?php

declare(strict_types=1);
use App\Models\User;
use App\Services\Money\WalletDeskTopups;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Contracts\Console\Kernel;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (! app()->environment('testing') || ! str_ends_with((string) config('database.connections.mysql.database'), '_test')) {
    throw new RuntimeException('Desk worker is restricted to isolated test schemas.');
}
[$script,$actorId,$ownerId,$operation,$barrier,$slot] = $argv;
app(CurrentSchool::class)->set(1);
file_put_contents($barrier.'.ready.'.$slot, 'ready');
$deadline = microtime(true) + 15;
while (! is_file($barrier.'.go')) {
    if (microtime(true) > $deadline) {
        throw new RuntimeException('Desk start barrier timed out');
    }usleep(10000);
}
try {
    $actor = User::findOrFail((int) $actorId);
    $row = app(WalletDeskTopups::class)->grant($actor, ['operation_id' => $operation, 'owner_user_id' => (int) $ownerId, 'amount' => '60.00', 'reason' => 'Synthetic concurrent school desk grant', 'confirmed' => true]);
    echo json_encode(['id' => (int) $row->id], JSON_THROW_ON_ERROR);
} catch (HttpExceptionInterface $e) {
    echo json_encode(['refused' => $e->getStatusCode()], JSON_THROW_ON_ERROR);
}
