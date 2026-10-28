<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Models\Money\WalletAccount;
use App\Models\Money\WalletWithdrawal;
use App\Models\User;
use App\Services\Money\WalletDeposits;
use App\Services\Money\WalletSettings;
use App\Services\Money\WalletWithdrawalReconciliation;
use App\Services\Money\WalletWithdrawals;
use App\Services\Platform\Ledger\LedgerService;
use App\Services\Platform\Modules\ModuleGate;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\DemoSchool;
use Tests\TestCase;

class WalletWithdrawalConcurrencyTest extends TestCase
{
    private User $admin;

    private User $parent;

    private array $withdrawals = [];

    private int $pickup;

    protected function setUp(): void
    {
        parent::setUp();
        Artisan::call('migrate:fresh', ['--force' => true]);
        $this->seedLikeInstaller();
        $school = DemoSchool::build();
        $this->admin = $this->superAdmin();
        $this->parent = User::findOrFail($school->ids['farid_user']);
        $this->parent->forceFill(['email_verified_at' => now()])->save();
        app(ModuleGate::class)->change('wallet', true, $this->admin);
        app(WalletSettings::class)->save($this->admin, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'currency' => 'ZAR', 'daily_spend_cents' => 5000, 'child_limits_required' => true]);
        app(WalletDeposits::class)->policy($this->admin, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'cash_allowed' => true, 'eft_allowed' => true]);
        $claim = app(WalletDeposits::class)->claim($this->parent, ['operation_id' => (string) Str::uuid(), 'amount' => '1000.00', 'method' => 'eft', 'source_reference' => 'SYNTHETIC-WITHDRAWAL-CLAIM', 'paid_on' => now()->toDateString(), 'evidence' => 'Private synthetic original holder proof', 'confirmed' => true]);
        app(WalletDeposits::class)->verify($this->admin, $claim->id, ['operation_id' => (string) Str::uuid(), 'amount' => '1000.00', 'method' => 'eft', 'source_reference' => 'SYNTHETIC-WITHDRAWAL-BANK', 'received_on' => now()->toDateString(), 'evidence' => 'Independent synthetic actual Finance proof', 'verified_original_holder' => true, 'verified_actual_receipt' => true]);
        app(WalletWithdrawals::class)->policy($this->admin, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'cash_threshold_amount' => '250.00', 'cash_at_boundary' => false, 'accepting_requests' => true]);
        $start = now('Africa/Johannesburg')->addDay()->startOfDay()->addHours(10);
        $this->pickup = app(WalletWithdrawals::class)->publishSlot($this->admin, ['operation_id' => (string) Str::uuid(), 'starts_at' => $start->format('Y-m-d\TH:i'), 'ends_at' => $start->copy()->addHour()->format('Y-m-d\TH:i'), 'capacity' => 1])->id;
    }

    public static function tearDownAfterClass(): void
    {
        RefreshDatabaseState::$migrated = false;
        parent::tearDownAfterClass();
    }

    private function balance(): int
    {
        return app(LedgerService::class)->balance(WalletAccount::sole()->ledger_account_id)->cents;
    }

    public function test_competing_reservations_cannot_overdraw_the_original_wallet(): void
    {
        $results = $this->parallel([(string) Str::uuid(), (string) Str::uuid()], 'large');
        $this->assertCount(1, array_filter($results, fn ($r) => isset($r['id'])));
        $this->assertCount(1, array_filter($results, fn ($r) => ($r['refused'] ?? null) === 422));
        $this->assertSame(1, WalletWithdrawal::count());
        $this->assertSame(40000, $this->balance());
        $this->assertTrue(WalletWithdrawalReconciliation::healthy(app(WalletWithdrawalReconciliation::class)->inspect()));
    }

    public function test_same_uuid_reserves_only_once_and_returns_the_same_original_request(): void
    {
        $key = (string) Str::uuid();
        $results = $this->parallel([$key, $key]);
        $this->assertArrayHasKey('id', $results[0]);
        $this->assertSame($results[0], $results[1]);
        $this->assertSame(1, WalletWithdrawal::count());
        $this->assertSame(70000, $this->balance());
    }

    public function test_one_published_cash_place_cannot_be_booked_twice(): void
    {
        $results = $this->parallel([(string) Str::uuid(), (string) Str::uuid()], 'cash');
        $this->assertCount(1, array_filter($results, fn ($r) => isset($r['id'])));
        $this->assertCount(1, array_filter($results, fn ($r) => ($r['refused'] ?? null) === 409));
        $this->assertSame(99000, $this->balance());
    }

    public function test_two_verified_payouts_cannot_consume_one_outside_receipt(): void
    {
        foreach ([1, 2] as $slot) {
            $row = app(WalletWithdrawals::class)->request($this->parent, ['operation_id' => (string) Str::uuid(), 'amount' => '300.00', 'private_bank_instructions' => 'Private synthetic original holder bank', 'confirmed' => true]);
            app(WalletWithdrawals::class)->approve($this->admin, $row->id, ['operation_id' => (string) Str::uuid(), 'verified_original_holder' => true]);
            $this->withdrawals[] = $row->id;
        }
        $results = $this->parallel([(string) Str::uuid(), (string) Str::uuid()], 'paid');
        $this->assertCount(1, array_filter($results, fn ($r) => isset($r['id'])));
        $this->assertCount(1, array_filter($results, fn ($r) => ($r['refused'] ?? null) === 409));
        $this->assertSame(1, DB::table('financial_disbursements')->count());
        $this->assertSame(40000, $this->balance());
        $this->assertTrue(WalletWithdrawalReconciliation::healthy(app(WalletWithdrawalReconciliation::class)->inspect()));
    }

    private function parallel(array $keys, string $mode = 'request'): array
    {
        $barrier = sys_get_temp_dir().'/wallet-withdrawal-'.bin2hex(random_bytes(12));
        $workers = [];
        try {
            foreach ($keys as $slot => $key) {
                $process = proc_open([PHP_BINARY, base_path('tests/Support/WalletWithdrawalWorker.php'), (string) ($mode === 'paid' ? $this->admin->id : $this->parent->id), (string) ($this->withdrawals[$slot] ?? 0), (string) $this->pickup, $key, $mode, $barrier, (string) $slot], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, base_path());
                $this->assertIsResource($process);
                fclose($pipes[0]);
                $workers[] = [$process, $pipes];
            }
            $deadline = microtime(true) + 15;
            while (! is_file($barrier.'.ready.0') || ! is_file($barrier.'.ready.1')) {
                if (microtime(true) > $deadline) {
                    $this->fail('Withdrawal workers did not reach the barrier');
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
