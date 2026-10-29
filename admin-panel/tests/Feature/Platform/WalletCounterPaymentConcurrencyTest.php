<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Models\Commerce\VendorProduct;
use App\Models\Commerce\VendorStockBalance;
use App\Models\Commerce\WalletCounterPayment;
use App\Models\Money\WalletAccount;
use App\Models\Money\WalletSetting;
use App\Models\User;
use App\Services\Commerce\VendorOrderReconciliation;
use App\Services\Commerce\VendorOrders;
use App\Services\Commerce\WalletCounterPayments;
use App\Services\Money\WalletSettings;
use App\Services\Platform\Ledger\LedgerService;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Tests\Support\DemoSchool;
use Tests\TestCase;

class WalletCounterPaymentConcurrencyTest extends TestCase
{
    private array $data;

    private User $parent;

    private User $operator;

    private WalletAccount $wallet;

    protected function setUp(): void
    {
        parent::setUp();
        Artisan::call('migrate:fresh', ['--force' => true]);
        $this->seedLikeInstaller();
        DemoSchool::build();
        putenv('BILLING_FIXTURE_ACTION=prepare');
        $this->data = require base_path('e2e/support/vendor-access-fixture.php');
        app(CurrentSchool::class)->set($this->data['school']);
        $finance = User::where('email', $this->data['finance'])->firstOrFail();
        app(ModuleGate::class)->change('wallet_payments', true, $finance);
        $this->parent = User::findOrFail($this->data['parents'][0]);
        $this->operator = User::findOrFail($this->data['operator_id']);
        $this->wallet = WalletAccount::sole();
        $this->cap(25000);
    }

    public static function tearDownAfterClass(): void
    {
        RefreshDatabaseState::$migrated = false;
        parent::tearDownAfterClass();
    }

    private function instruction(int $quantity = 1): array
    {
        $product = VendorProduct::sole();
        $quote = app(VendorOrders::class)->quote($this->parent, ['vendor_id' => $this->data['vendor'], 'wallet_id' => $this->wallet->id, 'lines' => [['product_id' => $product->id, 'quantity' => $quantity]]]);

        return app(WalletCounterPayments::class)->qr($this->parent, ['operation_id' => (string) Str::uuid(), 'quote' => $quote['quote'], 'confirmed' => true]);
    }

    private function cap(int $amount): void
    {
        $setting = WalletSetting::sole();
        $finance = User::where('email', $this->data['finance'])->firstOrFail();
        app(WalletSettings::class)->save($finance, ['operation_id' => (string) Str::uuid(), 'revision' => $setting->revision, 'currency' => 'ZAR', 'daily_spend_cents' => $amount, 'child_limits_required' => false]);
    }

    public function test_competing_instructions_recheck_the_daily_cap_even_with_funds_remaining(): void
    {
        $this->cap(6250);
        $result = $this->parallel([(string) Str::uuid(), (string) Str::uuid()], [$this->instruction(), $this->instruction()]);
        $this->assertCount(1, array_filter($result, fn ($r) => ($r['status'] ?? '') === 'paid'));
        $this->assertCount(1, array_filter($result, fn ($r) => ($r['refused'] ?? null) === 422));
        $this->assertSame(3750, app(LedgerService::class)->balance($this->wallet->ledger_account_id)->cents);
        $this->healthy();
    }

    private function healthy(): void
    {
        $this->assertSame([], app(LedgerService::class)->projectionDifferences());
        $this->assertTrue(VendorOrderReconciliation::healthy(app(VendorOrderReconciliation::class)->inspect()));
    }

    public function test_same_operation_replays_one_original_payment_and_handover(): void
    {
        $intent = $this->instruction();
        $key = (string) Str::uuid();
        $result = $this->parallel([$key, $key], [$intent, $intent]);
        $this->assertSame($result[0]['id'], $result[1]['id']);
        $this->assertNotNull($result[0]['id']);
        $this->assertEqualsCanonicalizing([true, false], array_column($result, 'replayed'));
        $this->assertSame(1, WalletCounterPayment::count());
        $this->assertSame(3750, app(LedgerService::class)->balance($this->wallet->ledger_account_id)->cents);
        $this->assertSame(5, VendorStockBalance::sole()->on_hand);
        $this->healthy();
    }

    public function test_distinct_operations_cannot_spend_one_instruction_twice(): void
    {
        $intent = $this->instruction();
        $result = $this->parallel([(string) Str::uuid(), (string) Str::uuid()], [$intent, $intent]);
        $this->assertCount(1, array_filter($result, fn ($r) => ($r['status'] ?? '') === 'paid'));
        $this->assertCount(1, array_filter($result, fn ($r) => ($r['refused'] ?? null) === 409));
        $this->assertSame(1, WalletCounterPayment::count());
        $this->healthy();
    }

    public function test_competing_live_instructions_cannot_overdraw_one_wallet(): void
    {
        $result = $this->parallel([(string) Str::uuid(), (string) Str::uuid()], [$this->instruction(3), $this->instruction(3)]);
        $this->assertCount(1, array_filter($result, fn ($r) => ($r['status'] ?? '') === 'paid'));
        $this->assertCount(1, array_filter($result, fn ($r) => ($r['refused'] ?? null) === 422));
        $this->assertSame(1250, app(LedgerService::class)->balance($this->wallet->ledger_account_id)->cents);
        $this->assertSame(3, VendorStockBalance::sole()->on_hand);
        $this->healthy();
    }

    public function test_cancellation_and_payment_have_one_terminal_outcome(): void
    {
        $intent = $this->instruction();
        $result = $this->parallel([(string) Str::uuid(), (string) Str::uuid()], [$intent, $intent], ['cancel', 'pay']);
        $this->assertCount(1, array_filter($result, fn ($r) => isset($r['status'])));
        $this->assertCount(1, array_filter($result, fn ($r) => ($r['refused'] ?? null) === 409));
        $paid = WalletCounterPayment::exists();
        $this->assertSame($paid ? 3750 : 5000, app(LedgerService::class)->balance($this->wallet->ledger_account_id)->cents);
        $this->assertSame($paid ? 5 : 6, VendorStockBalance::sole()->on_hand);
        $this->healthy();
    }

    public function test_unknown_recovery_prevents_a_late_request_or_returns_the_original(): void
    {
        $intent = $this->instruction();
        $key = (string) Str::uuid();
        $result = $this->parallel([$key, $key], [$intent, $intent], ['resolve', 'pay']);
        $paid = WalletCounterPayment::exists();
        $this->assertSame($paid ? 'committed' : 'abandoned', $result[0]['status']);
        $this->assertSame($paid ? 'paid' : 409, $result[1]['status'] ?? $result[1]['refused']);
        $this->assertSame($paid ? 3750 : 5000, app(LedgerService::class)->balance($this->wallet->ledger_account_id)->cents);
        $this->healthy();
    }

    private function parallel(array $keys, array $intents, array $modes = ['pay', 'pay']): array
    {
        $barrier = sys_get_temp_dir().'/counter-payment-'.bin2hex(random_bytes(12));
        $workers = [];
        try {
            foreach ($keys as $slot => $key) {
                $intent = $intents[$slot];
                $actor = $modes[$slot] === 'cancel' ? $this->parent : $this->operator;
                $process = proc_open([PHP_BINARY, base_path('tests/Support/WalletCounterPaymentWorker.php'), (string) $this->data['school'], (string) $actor->id, (string) $this->data['vendor'], $intent['id'], substr($intent['qr'], strlen('navigateed:wallet-pay:')), $key, $modes[$slot], $barrier, (string) $slot], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, base_path());
                $this->assertIsResource($process);
                fclose($pipes[0]);
                $workers[] = [$process, $pipes];
            }
            $deadline = microtime(true) + 15;
            while (! is_file($barrier.'.ready.0') || ! is_file($barrier.'.ready.1')) {
                if (microtime(true) > $deadline) {
                    $this->fail('Counter payment workers did not reach the barrier');
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
