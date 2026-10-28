<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Models\Money\WalletAccount;
use App\Models\Money\WalletParentDeposit;
use App\Models\User;
use App\Services\Money\WalletDeposits;
use App\Services\Money\WalletFundingReconciliation;
use App\Services\Money\WalletSettings;
use App\Services\Platform\Ledger\LedgerService;
use App\Services\Platform\Modules\ModuleGate;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\DemoSchool;
use Tests\TestCase;

class WalletDepositConcurrencyTest extends TestCase
{
    private User $admin;

    private array $claims;

    protected function setUp(): void
    {
        parent::setUp();
        Artisan::call('migrate:fresh', ['--force' => true]);
        $this->seedLikeInstaller();
        $school = DemoSchool::build();
        $this->admin = $this->superAdmin();
        $parent = User::findOrFail($school->ids['farid_user']);
        $parent->forceFill(['email_verified_at' => now()])->save();
        app(ModuleGate::class)->change('wallet', true, $this->admin);
        app(WalletSettings::class)->save($this->admin, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'currency' => 'ZAR', 'daily_spend_cents' => 5000, 'child_limits_required' => true]);
        app(WalletDeposits::class)->policy($this->admin, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'cash_allowed' => true, 'eft_allowed' => true]);
        $this->claims = [];
        foreach ([1, 2] as $number) {
            $this->claims[] = app(WalletDeposits::class)->claim($parent, ['operation_id' => (string) Str::uuid(), 'amount' => '20.00', 'method' => 'eft', 'source_reference' => 'SYNTHETIC-CLAIM-'.$number, 'paid_on' => now()->toDateString(), 'evidence' => 'Private synthetic committed parent proof', 'confirmed' => true])->id;
        }
    }

    public static function tearDownAfterClass(): void
    {
        RefreshDatabaseState::$migrated = false;
        parent::tearDownAfterClass();
    }

    public function test_two_claims_cannot_credit_one_outside_bank_receipt_twice(): void
    {
        $results = $this->parallel([(string) Str::uuid(), (string) Str::uuid()]);
        $this->assertCount(1, array_filter($results, fn ($r) => isset($r['id'])));
        $this->assertCount(1, array_filter($results, fn ($r) => ($r['refused'] ?? null) === 409));
        $this->assertOneCredit();
    }

    public function test_same_verification_uuid_returns_one_original_credit(): void
    {
        $key = (string) Str::uuid();
        $results = $this->parallel([$key, $key], true);
        $this->assertArrayHasKey('id', $results[0]);
        $this->assertSame($results[0], $results[1]);
        $this->assertOneCredit();
    }

    private function assertOneCredit(): void
    {
        $this->assertSame(1, WalletParentDeposit::count());
        $this->assertSame(1, DB::table('financial_receipts')->count());
        $this->assertSame(1, DB::table('ledger_entries')->count());
        $this->assertSame(2000, app(LedgerService::class)->balance(WalletAccount::sole()->ledger_account_id)->cents);
        $this->assertTrue(WalletFundingReconciliation::healthy(app(WalletFundingReconciliation::class)->inspect()));
        $this->assertSame([], app(LedgerService::class)->projectionDifferences());
    }

    private function parallel(array $keys, bool $sameClaim = false): array
    {
        $barrier = sys_get_temp_dir().'/wallet-deposit-'.bin2hex(random_bytes(12));
        $workers = [];
        try {
            foreach ($keys as $slot => $key) {
                $process = proc_open([PHP_BINARY, base_path('tests/Support/WalletDepositWorker.php'), (string) $this->admin->id, (string) ($sameClaim ? $this->claims[0] : $this->claims[$slot]), $key, $barrier, (string) $slot], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, base_path());
                $this->assertIsResource($process);
                fclose($pipes[0]);
                $workers[] = [$process, $pipes];
            }
            $deadline = microtime(true) + 15;
            while (! is_file($barrier.'.ready.0') || ! is_file($barrier.'.ready.1')) {
                if (microtime(true) > $deadline) {
                    $this->fail('Deposit workers did not reach the barrier');
                }usleep(10000);
            }file_put_contents($barrier.'.go', 'go');
            $results = [];
            foreach ($workers as [$process,$pipes]) {
                stream_set_timeout($pipes[1], 25);
                $out = stream_get_contents($pipes[1]);
                $error = stream_get_contents($pipes[2]);
                fclose($pipes[1]);
                fclose($pipes[2]);
                $this->assertSame(0, proc_close($process), $error);
                $results[] = json_decode($out, true, 512, JSON_THROW_ON_ERROR);
            }

            return $results;
        } finally {
            foreach ($workers as [$process,$pipes]) {
                if (is_resource($process)) {
                    proc_terminate($process);
                    proc_close($process);
                }foreach ($pipes as $pipe) {
                    if (is_resource($pipe)) {
                        fclose($pipe);
                    }
                }
            }foreach (glob($barrier.'.*') ?: [] as $file) {
                @unlink($file);
            }
        }
    }
}
