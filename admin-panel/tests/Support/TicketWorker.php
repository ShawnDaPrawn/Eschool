<?php

declare(strict_types=1);
use App\Models\User;
use App\Services\Commerce\TicketCounters;
use App\Services\Commerce\TicketHolders;
use App\Services\Commerce\VendorOrders;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Contracts\Console\Kernel;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (! app()->environment('testing') || ! str_ends_with((string) config('database.connections.mysql.database'), '_test')) {
    throw new RuntimeException('Synthetic admission workers only');
}
[$script,$school,$actorId,$mode,$payload,$barrier,$slot] = $argv;
app(CurrentSchool::class)->set((int) $school);
file_put_contents($barrier.'.ready.'.$slot, 'ready');
$deadline = microtime(true) + 15;
while (! is_file($barrier.'.go')) {
    if (microtime(true) > $deadline) {
        throw new RuntimeException('Admission barrier timed out');
    }usleep(10000);
}
try {
    $actor = User::findOrFail((int) $actorId);
    $data = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
    $result = match ($mode) {
        'purchase' => ['id' => app(VendorOrders::class)->purchase($actor, $data)->id],
        'transfer' => app(TicketHolders::class)->transfer($actor, $data['ticket_id'], $data),
        'redeem' => app(TicketCounters::class)->redeem($actor, $data['vendor_id'], $data),
        'resolve' => app(TicketCounters::class)->resolve($actor, $data['vendor_id'], $data['operation_id']),
    };
    echo json_encode($result, JSON_THROW_ON_ERROR);

} catch (HttpExceptionInterface$e) {
    echo json_encode(['refused' => $e->getStatusCode()], JSON_THROW_ON_ERROR);
}
