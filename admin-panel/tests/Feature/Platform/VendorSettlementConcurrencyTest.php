<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Models\Commerce\VendorSettlement;
use App\Models\Commerce\VendorSettlementOutcome;
use App\Models\Money\WalletAccount;
use App\Models\User;
use App\Services\Commerce\VendorCatalogues;
use App\Services\Commerce\VendorOrderReconciliation;
use App\Services\Commerce\VendorOrders;
use App\Services\Commerce\VendorSettlements;
use App\Services\Money\WalletSettings;
use App\Services\Platform\Ledger\LedgerService;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\DemoSchool;
use Tests\TestCase;

class VendorSettlementConcurrencyTest extends TestCase
{
    private array $data;

    private User $finance;

    private User $reviewer;

    private array $orders = [];

    private array $batch = [];

    protected function setUp(): void
    {
        parent::setUp();
        Artisan::call('migrate:fresh', ['--force' => true]);
        $this->seedLikeInstaller();
        DemoSchool::build();
        putenv('BILLING_FIXTURE_ACTION=prepare');
        $this->data = require base_path('e2e/support/vendor-settlements-fixture.php');
        app(CurrentSchool::class)->set($this->data['school']);
        $this->finance = User::where('email', $this->data['finance'])->firstOrFail();
        $this->reviewer = User::where('email', $this->data['reviewer'])->firstOrFail();
        app(WalletSettings::class)->save($this->finance, ['operation_id' => (string) Str::uuid(), 'revision' => 1, 'currency' => 'ZAR', 'daily_spend_cents' => 50000, 'child_limits_required' => true]);
        $c = app(VendorCatalogues::class);
        $v = $c->vendor($this->finance, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'category_id' => $this->data['vendor_category'], 'name' => 'Synthetic racing independent merchant', 'legal_name' => 'Synthetic original merchant', 'kind' => 'independent', 'status' => 'active', 'vat_registered' => false, 'refund_bearer' => 'vendor', 'chargeback_bearer' => 'vendor', 'settlement_method' => 'eft', 'settlement_cycle' => 'manual', 'settlement_delay_days' => 0, 'settlement_terms' => 'Explicit reviewed synthetic merchant settlement agreement', 'private_bank_instructions' => 'Synthetic verified original bank account']);
        $p = $c->product($this->finance, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'vendor_id' => $v->id, 'category_id' => $v->category_id, 'sku' => 'RACE-SETTLEMENT-01', 'name' => 'Synthetic race merchant goods', 'unit_label' => 'each', 'price' => '12.50', 'vat_kind' => 'none', 'vat_rate_bps' => 0, 'tracks_stock' => false, 'status' => 'active']);
        $parent = User::findOrFail($this->data['parents'][0]);
        $wallet = WalletAccount::sole();
        $orders = app(VendorOrders::class);
        for ($i = 0; $i < 2; $i++) {
            $q = $orders->quote($parent, ['vendor_id' => $v->id, 'wallet_id' => $wallet->id, 'lines' => [['product_id' => $p->id, 'quantity' => 1]]]);
            $o = $orders->purchase($parent, ['operation_id' => (string) Str::uuid(), 'quote' => $q['quote'], 'confirmed' => true]);
            $orders->collect($this->finance, $v->id, ['operation_id' => (string) Str::uuid(), 'order_number' => $o->order_number, 'confirmed' => true]);
            $this->orders[] = $o->id;
        }
        $this->batch = ['operation_id' => (string) Str::uuid(), 'vendor_id' => $v->id, 'vendor_revision' => $v->revision, 'period_from' => now()->toDateString(), 'period_to' => now()->toDateString(), 'order_ids' => [$this->orders[0]], 'confirmed_original_terms' => true];
    }

    public static function tearDownAfterClass(): void
    {
        RefreshDatabaseState::$migrated = false;
        parent::tearDownAfterClass();
    }

    private function approved(int $order): VendorSettlement
    {
        $s = app(VendorSettlements::class);
        $row = $s->prepare($this->finance, array_replace($this->batch, ['operation_id' => (string) Str::uuid(), 'order_ids' => [$order]]));
        $s->approve($this->reviewer, $row->id, ['operation_id' => (string) Str::uuid(), 'confirmed' => true]);

        return $row;
    }

    private function payout(int $id): array
    {
        return ['operation_id' => (string) Str::uuid(), 'settlement_id' => $id, 'confirmed' => true, 'paid_on' => now()->toDateString(), 'payout_reference' => 'SYNTHETIC-SINGLE-ACTUAL-SETTLEMENT', 'private_evidence' => 'Independently verified original synthetic merchant payout', 'verified_original_vendor' => true, 'verified_actual_payout' => true];
    }

    private function healthy(): void
    {
        $this->assertTrue(VendorOrderReconciliation::healthy(app(VendorOrderReconciliation::class)->inspect()), json_encode(app(VendorOrderReconciliation::class)->inspect()));
        $this->assertSame([], app(LedgerService::class)->projectionDifferences());
    }

    public function test_competing_batches_cannot_reserve_one_original_order_twice(): void
    {
        $other = array_replace($this->batch, ['operation_id' => (string) Str::uuid()]);
        $result = $this->parallel([$this->batch, $other], 'prepare');
        $this->assertCount(1, array_filter($result, fn ($r) => isset($r['id'])));
        $this->assertCount(1, array_filter($result, fn ($r) => ($r['refused'] ?? null) === 409));
        $this->assertSame(1, VendorSettlement::count());
        $this->assertSame(0, VendorSettlementOutcome::count());
        $this->healthy();
    }

    public function test_duplicate_payout_posts_one_original_batch_and_outside_identity_once(): void
    {
        $row = $this->approved($this->orders[0]);
        $pay = $this->payout($row->id);
        $result = $this->parallel([$pay, $pay], 'finish');
        $this->assertSame($result[0]['id'], $result[1]['id']);
        $this->assertSame(1, VendorSettlementOutcome::count());
        $this->assertSame(1, DB::table('financial_disbursements')->where('school_id', $this->data['school'])->count());
        $this->assertSame(8750, (int) DB::table('ledger_accounts')->where('school_id', $this->data['school'])->where('reference', 'wallet:eft:ZAR')->value('balance_cents'));
        $this->healthy();
    }

    public function test_one_actual_outside_payout_cannot_settle_two_approved_batches(): void
    {
        $first = $this->approved($this->orders[0]);
        $second = $this->approved($this->orders[1]);
        $result = $this->parallel([$this->payout($first->id), $this->payout($second->id)], 'finish');
        $this->assertCount(1, array_filter($result, fn ($r) => isset($r['id'])));
        $this->assertCount(1, array_filter($result, fn ($r) => ($r['refused'] ?? null) === 409));
        $this->assertSame(1, VendorSettlementOutcome::count());
        $this->assertSame(1, DB::table('financial_disbursements')->where('school_id', $this->data['school'])->count());
        $this->healthy();
    }

    private function parallel(array $payloads, string $mode): array
    {
        $barrier = sys_get_temp_dir().'/vendor-settlement-'.bin2hex(random_bytes(12));
        $workers = [];
        try {
            foreach ($payloads as $slot => $payload) {
                $process = proc_open([PHP_BINARY, base_path('tests/Support/VendorSettlementWorker.php'), (string) $this->data['school'], (string) $this->finance->id, $mode, json_encode($payload, JSON_THROW_ON_ERROR), $barrier, (string) $slot], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, base_path());
                $this->assertIsResource($process);
                fclose($pipes[0]);
                $workers[] = [$process, $pipes];
            }
            $deadline = microtime(true) + 15;
            while (! is_file($barrier.'.ready.0') || ! is_file($barrier.'.ready.1')) {
                if (microtime(true) > $deadline) {
                    $this->fail('Settlement workers did not reach the barrier');
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
