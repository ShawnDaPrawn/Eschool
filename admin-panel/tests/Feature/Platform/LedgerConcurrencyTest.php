<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Services\Platform\Ledger\AccountType;
use App\Services\Platform\Ledger\LedgerService;
use App\Services\Platform\Ledger\Money;
use App\Services\Platform\Ledger\Posting;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LedgerConcurrencyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Separate processes require committed fixtures. Immutable production
        // history refuses rollback, so only this isolated *_test schema is
        // recreated; no test bypass is added to the posting kernel.
        Artisan::call('migrate:fresh', ['--force' => true]);
    }

    public static function tearDownAfterClass(): void
    {
        RefreshDatabaseState::$migrated = false;
        parent::tearDownAfterClass();
    }

    public function test_parallel_spends_cannot_overdraw_and_parallel_same_key_posts_once(): void
    {
        config(['modules.wallet' => ['name' => 'Wallet', 'money' => true, 'default' => true],
            'school_packages.legacy' => [...config('school_packages.legacy'), 'wallet']]);
        $ledger = app(LedgerService::class);
        $cash = $ledger->openAccount(AccountType::CashDesk, 'cash', 'ZAR');
        $wallet = $ledger->openAccount(AccountType::Wallet, 'wallet', 'ZAR');
        $ledger->post('fund:1', 'fund:1', 'Funding', [Posting::debit($cash, Money::ofCents(100)), Posting::credit($wallet, Money::ofCents(100))]);
        $results = $this->parallel($wallet, $cash, ['spend:1', 'spend:2']);
        $this->assertCount(1, array_filter($results, fn ($r) => isset($r['entry_id'])));
        $this->assertCount(1, array_filter($results, fn ($r) => isset($r['refused'])));
        $this->assertSame(25, $ledger->balance($wallet)->cents);
        $ledger->post('fund:2', 'fund:2', 'Funding', [Posting::debit($cash, Money::ofCents(100)), Posting::credit($wallet, Money::ofCents(100))]);
        $results = $this->parallel($wallet, $cash, ['same-spend', 'same-spend']);
        $this->assertArrayHasKey('entry_id', $results[0]);
        $this->assertSame($results[0]['entry_id'], $results[1]['entry_id']);
        $this->assertSame(50, $ledger->balance($wallet)->cents);
        $this->assertSame(4, DB::table('ledger_entries')->count());
        $this->assertSame([], $ledger->projectionDifferences());
    }

    /** @param list<string> $keys @return list<array<string, mixed>> */
    private function parallel(int $wallet, int $cash, array $keys): array
    {
        $barrier = sys_get_temp_dir().'/ledger-concurrency-'.bin2hex(random_bytes(12));
        $workers = [];
        try {
            foreach ($keys as $slot => $key) {
                $process = proc_open([PHP_BINARY, base_path('tests/Support/LedgerSpendWorker.php'), (string) $wallet, (string) $cash, $key, $barrier, (string) $slot],
                    [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, base_path());
                $this->assertIsResource($process);
                fclose($pipes[0]);
                $workers[] = [$process, $pipes];
            }
            $deadline = microtime(true) + 15;
            while (! is_file($barrier.'.ready.0') || ! is_file($barrier.'.ready.1')) {
                if (microtime(true) > $deadline) {
                    $this->fail('Independent ledger workers did not reach the start barrier.');
                }
                usleep(10000);
            }
            file_put_contents($barrier.'.go', 'go');
            $results = [];
            foreach ($workers as [$process, $pipes]) {
                stream_set_timeout($pipes[1], 20);
                $stdout = stream_get_contents($pipes[1]);
                $stderr = stream_get_contents($pipes[2]);
                fclose($pipes[1]);
                fclose($pipes[2]);
                $this->assertSame(0, proc_close($process), $stderr);
                $results[] = json_decode($stdout, true, flags: JSON_THROW_ON_ERROR);
            }

            return $results;
        } finally {
            foreach ($workers as [$process, $pipes]) {
                if (is_resource($process)) {
                    proc_terminate($process);
                    proc_close($process);
                }
                foreach ($pipes as $pipe) {
                    if (is_resource($pipe)) {
                        fclose($pipe);
                    }
                }
            }
            foreach (glob($barrier.'.*') ?: [] as $path) {
                unlink($path);
            }
        }
    }
}
