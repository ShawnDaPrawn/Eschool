<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Models\Commerce\VendorOrder;
use App\Models\Commerce\VendorOrderAction;
use App\Models\Commerce\VendorStockBalance;
use App\Models\Money\WalletAccount;
use App\Models\User;
use App\Services\Commerce\VendorCatalogues;
use App\Services\Commerce\VendorOrderReconciliation;
use App\Services\Commerce\VendorOrders;
use App\Services\Money\WalletDeposits;
use App\Services\Platform\Ledger\LedgerService;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Tests\Support\DemoSchool;
use Tests\TestCase;

class VendorOrderConcurrencyTest extends TestCase
{
    private array $data;

    private User $finance;

    private array $parents = [];

    private array $wallets = [];

    private array $quotes = [];

    private int $vendor;

    private int $product;

    private int $order = 0;

    protected function setUp(): void
    {
        parent::setUp();
        Artisan::call('migrate:fresh', ['--force' => true]);
        $this->seedLikeInstaller();
        DemoSchool::build();
        putenv('BILLING_FIXTURE_ACTION=prepare');
        $this->data = require base_path('e2e/support/vendor-catalogues-fixture.php');
        app(CurrentSchool::class)->set($this->data['school']);
        $this->finance = User::where('email', $this->data['finance'])->firstOrFail();
        app(ModuleGate::class)->change('wallet', true, $this->finance);
        app(WalletDeposits::class)->policy($this->finance, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'cash_allowed' => true, 'eft_allowed' => true]);
        foreach ($this->data['parents'] as $index => $id) {
            $parent = User::findOrFail($id);
            $this->parents[] = $parent;
            $claim = app(WalletDeposits::class)->claim($parent, ['operation_id' => (string) Str::uuid(), 'amount' => '20.00', 'method' => 'eft', 'source_reference' => 'SYNTHETIC-PARENT-'.$index, 'paid_on' => now()->toDateString(), 'evidence' => 'Synthetic original parent race backing', 'confirmed' => true]);
            app(WalletDeposits::class)->verify($this->finance, $claim->id, ['operation_id' => (string) Str::uuid(), 'amount' => '20.00', 'method' => 'eft', 'source_reference' => 'SYNTHETIC-VERIFIED-'.$index, 'received_on' => now()->toDateString(), 'evidence' => 'Independent actual synthetic parent backing', 'verified_original_holder' => true, 'verified_actual_receipt' => true]);
            $this->wallets[] = WalletAccount::where('user_id', $parent->id)->sole();
        }
        $catalogues = app(VendorCatalogues::class);
        $v = $catalogues->vendor($this->finance, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'category_id' => $this->data['vendor_category'], 'name' => 'Synthetic order counter', 'kind' => 'school', 'status' => 'active', 'legal_name' => 'Synthetic school counter', 'vat_registered' => false, 'refund_bearer' => 'school', 'chargeback_bearer' => 'school', 'settlement_method' => 'school_internal', 'settlement_cycle' => 'manual', 'settlement_delay_days' => 0, 'settlement_terms' => 'Retain original order proceeds pending collection and review.']);
        $this->vendor = $v->id;
        $p = $catalogues->product($this->finance, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'vendor_id' => $v->id, 'category_id' => $v->category_id, 'sku' => 'RACE-01', 'name' => 'Counted synthetic race goods', 'unit_label' => 'each', 'price' => '15.00', 'vat_kind' => 'none', 'vat_rate_bps' => 0, 'tracks_stock' => true, 'status' => 'active']);
        $this->product = $p->id;
        $catalogues->stock($this->finance, ['operation_id' => (string) Str::uuid(), 'product_id' => $p->id, 'source_reference' => 'SYNTHETIC-RACE-OPENING', 'kind' => 'receipt', 'units_delta' => 10, 'private_reason' => 'Actual synthetic counted race goods']);
    }

    public static function tearDownAfterClass(): void
    {
        RefreshDatabaseState::$migrated = false;
        parent::tearDownAfterClass();
    }

    private function quote(User $actor, WalletAccount $wallet): string
    {
        return app(VendorOrders::class)->quote($actor, ['vendor_id' => $this->vendor, 'wallet_id' => $wallet->id, 'lines' => [['product_id' => $this->product, 'quantity' => 1]]])['quote'];
    }

    private function healthy(): void
    {
        $this->assertTrue(VendorOrderReconciliation::healthy(app(VendorOrderReconciliation::class)->inspect()));
        $this->assertSame([], app(LedgerService::class)->projectionDifferences());
    }

    public function test_competing_orders_cannot_overdraw_one_wallet(): void
    {
        $this->parents[1] = $this->parents[0];
        $this->quotes = [$this->quote($this->parents[0], $this->wallets[0]), $this->quote($this->parents[0], $this->wallets[0])];
        $result = $this->parallel([(string) Str::uuid(), (string) Str::uuid()]);
        $this->assertCount(1, array_filter($result, fn ($r) => isset($r['id'])));
        $this->assertCount(1, array_filter($result, fn ($r) => ($r['refused'] ?? null) === 422));
        $this->assertSame(500, app(LedgerService::class)->balance($this->wallets[0]->ledger_account_id)->cents);
        $this->assertSame(9, VendorStockBalance::sole()->on_hand);
        $this->healthy();
    }

    public function test_two_funded_buyers_cannot_purchase_one_last_counted_item(): void
    {
        app(VendorCatalogues::class)->stock($this->finance, ['operation_id' => (string) Str::uuid(), 'product_id' => $this->product, 'source_reference' => 'SYNTHETIC-COUNTED-WASTE', 'kind' => 'waste', 'units_delta' => -9, 'private_reason' => 'Synthetic physical stock reduced to one last item']);
        $this->quotes = [$this->quote($this->parents[0], $this->wallets[0]), $this->quote($this->parents[1], $this->wallets[1])];
        $result = $this->parallel([(string) Str::uuid(), (string) Str::uuid()]);
        $this->assertCount(1, array_filter($result, fn ($r) => isset($r['id'])));
        $this->assertCount(1, array_filter($result, fn ($r) => ($r['refused'] ?? null) === 409));
        $this->assertSame(0, VendorStockBalance::sole()->on_hand);
        $this->healthy();
    }

    public function test_same_operation_creates_one_order_receipt_and_one_stock_decrement(): void
    {
        $this->parents[1] = $this->parents[0];
        $quote = $this->quote($this->parents[0], $this->wallets[0]);
        $this->quotes = [$quote, $quote];
        $key = (string) Str::uuid();
        $result = $this->parallel([$key, $key]);
        $this->assertArrayHasKey('id', $result[0]);
        $this->assertSame($result[0], $result[1]);
        $this->assertSame(1, VendorOrder::count());
        $this->assertSame(9, VendorStockBalance::sole()->on_hand);
        $this->healthy();
    }

    public function test_collection_and_cancellation_cannot_both_win(): void
    {
        $quote = $this->quote($this->parents[0], $this->wallets[0]);
        $order = app(VendorOrders::class)->purchase($this->parents[0], ['operation_id' => (string) Str::uuid(), 'quote' => $quote, 'confirmed' => true]);
        $this->order = $order->id;
        $this->parents[1] = $this->parents[0];
        $this->quotes = ['', ''];
        $result = $this->parallel([(string) Str::uuid(), (string) Str::uuid()], 'terminal');
        $this->assertCount(1, array_filter($result, fn ($r) => isset($r['id'])));
        $this->assertCount(1, array_filter($result, fn ($r) => ($r['refused'] ?? null) === 409));
        $this->assertSame(1, VendorOrderAction::count());
        $cancelled = app(VendorOrders::class)->state($order) === 'cancelled';
        $this->assertSame($cancelled ? 2000 : 500, app(LedgerService::class)->balance($this->wallets[0]->ledger_account_id)->cents);
        $this->assertSame($cancelled ? 10 : 9, VendorStockBalance::sole()->on_hand);
        $this->healthy();
    }

    private function parallel(array $keys, string $mode = 'purchase'): array
    {
        $barrier = sys_get_temp_dir().'/vendor-order-'.bin2hex(random_bytes(12));
        $workers = [];
        try {
            foreach ($keys as $slot => $key) {
                $process = proc_open([PHP_BINARY, base_path('tests/Support/VendorOrderWorker.php'), (string) $this->data['school'], (string) ($mode === 'terminal' && $slot === 0 ? $this->finance->id : $this->parents[$slot]->id), (string) $this->vendor, (string) $this->order, $this->quotes[$slot], $key, $mode === 'terminal' ? ($slot === 0 ? 'collect' : 'cancel') : 'purchase', $barrier, (string) $slot], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, base_path());
                $this->assertIsResource($process);
                fclose($pipes[0]);
                $workers[] = [$process, $pipes];
            }
            $deadline = microtime(true) + 15;
            while (! is_file($barrier.'.ready.0') || ! is_file($barrier.'.ready.1')) {
                if (microtime(true) > $deadline) {
                    $this->fail('Order workers did not reach the barrier');
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
