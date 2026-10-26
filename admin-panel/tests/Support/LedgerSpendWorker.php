<?php

declare(strict_types=1);
use App\Services\Platform\Ledger\LedgerService;
use App\Services\Platform\Ledger\Money;
use App\Services\Platform\Ledger\Posting;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Contracts\Console\Kernel;

// Independent connection/process for the real concurrent-spend/replay test.
// This is CLI test support, never an HTTP endpoint or production command.
require getenv('LEDGER_TEST_BOOTSTRAP') ?: dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (! app()->environment('testing') || ! str_ends_with((string) config('database.connections.mysql.database'), '_test')) {
    throw new RuntimeException('Ledger worker is restricted to test databases.');
}
config(['modules.wallet' => ['name' => 'Wallet', 'money' => true, 'default' => true],
    'school_packages.legacy' => [...config('school_packages.legacy'), 'wallet']]);
[$script, $wallet, $cash, $key, $barrier, $slot] = $argv;
app(CurrentSchool::class)->set(1);
file_put_contents($barrier.'.ready.'.$slot, 'ready');
$deadline = microtime(true) + 15;
while (! is_file($barrier.'.go')) {
    if (microtime(true) > $deadline) {
        throw new RuntimeException('Concurrent ledger start barrier timed out.');
    }
    usleep(10000);
}
try {
    $id = app(LedgerService::class)->post($key, $key, 'Concurrent spend', [
        Posting::debit((int) $wallet, Money::ofCents(75, 'ZAR')),
        Posting::credit((int) $cash, Money::ofCents(75, 'ZAR')),
    ]);
    echo json_encode(['entry_id' => $id], JSON_THROW_ON_ERROR);
} catch (DomainException $error) {
    echo json_encode(['refused' => $error->getMessage()], JSON_THROW_ON_ERROR);
}
