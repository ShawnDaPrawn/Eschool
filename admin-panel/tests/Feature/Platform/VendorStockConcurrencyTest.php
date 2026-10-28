<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Models\Commerce\VendorStockBalance;
use App\Models\Commerce\VendorStockMovement;
use App\Models\User;
use App\Services\Commerce\VendorCatalogueReconciliation;
use App\Services\Commerce\VendorCatalogues;
use App\Services\Money\WalletSettings;
use App\Services\Platform\Modules\ModuleGate;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Tests\Support\DemoSchool;
use Tests\TestCase;

class VendorStockConcurrencyTest extends TestCase
{
    private User $admin;

    private int $product;

    protected function setUp(): void
    {
        parent::setUp();
        Artisan::call('migrate:fresh', ['--force' => true]);
        $this->seedLikeInstaller();
        DemoSchool::build();
        $this->admin = $this->superAdmin();
        app(ModuleGate::class)->change('wallet', true, $this->admin);
        app(WalletSettings::class)->save($this->admin, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'currency' => 'ZAR', 'daily_spend_cents' => 5000, 'child_limits_required' => true]);
        app(ModuleGate::class)->change('vendors', true, $this->admin);
        $s = app(VendorCatalogues::class);
        $category = $s->category($this->admin, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'key' => 'food', 'name' => 'Food', 'status' => 'active']);
        $vendor = $s->vendor($this->admin, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'category_id' => $category->id, 'name' => 'Synthetic merchant', 'kind' => 'school', 'status' => 'draft']);
        $product = $s->product($this->admin, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'vendor_id' => $vendor->id, 'category_id' => $category->id, 'sku' => 'SYNTHETIC-01', 'name' => 'Counted product', 'unit_label' => 'each', 'price' => '10.00', 'vat_kind' => 'none', 'vat_rate_bps' => 0, 'tracks_stock' => true, 'status' => 'draft']);
        $this->product = $product->id;
        $s->stock($this->admin, ['operation_id' => (string) Str::uuid(), 'product_id' => $product->id, 'source_reference' => 'OPENING-COUNT', 'kind' => 'receipt', 'units_delta' => 10, 'private_reason' => 'Synthetic actual counted opening stock']);
    }

    public static function tearDownAfterClass(): void
    {
        RefreshDatabaseState::$migrated = false;
        parent::tearDownAfterClass();
    }

    public function test_competing_removals_cannot_make_physical_stock_negative(): void
    {
        $results = $this->parallel([(string) Str::uuid(), (string) Str::uuid()]);
        $this->assertCount(1, array_filter($results, fn ($r) => isset($r['id'])));
        $this->assertCount(1, array_filter($results, fn ($r) => ($r['refused'] ?? null) === 409));
        $this->assertSame(3, VendorStockBalance::sole()->on_hand);
        $this->assertTrue(VendorCatalogueReconciliation::healthy(app(VendorCatalogueReconciliation::class)->inspect()));
    }

    public function test_concurrent_same_uuid_adds_counted_delivery_only_once(): void
    {
        $key = (string) Str::uuid();
        $results = $this->parallel([$key, $key], 'same');
        $this->assertArrayHasKey('id', $results[0]);
        $this->assertSame($results[0], $results[1]);
        $this->assertSame(12, VendorStockBalance::sole()->on_hand);
        $this->assertSame(2, VendorStockMovement::count());
    }

    public function test_one_physical_delivery_cannot_be_recorded_under_two_operation_keys(): void
    {
        $results = $this->parallel([(string) Str::uuid(), (string) Str::uuid()], 'same');
        $this->assertCount(1, array_filter($results, fn ($r) => isset($r['id'])));
        $this->assertCount(1, array_filter($results, fn ($r) => ($r['refused'] ?? null) === 409));
        $this->assertSame(12, VendorStockBalance::sole()->on_hand);
    }

    private function parallel(array $keys, string $mode = 'remove'): array
    {
        $barrier = sys_get_temp_dir().'/vendor-stock-'.bin2hex(random_bytes(12));
        $workers = [];
        try {
            foreach ($keys as $slot => $key) {
                $process = proc_open([PHP_BINARY, base_path('tests/Support/VendorStockWorker.php'), (string) $this->admin->id, (string) $this->product, $key, $mode, $barrier, (string) $slot], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, base_path());
                $this->assertIsResource($process);
                fclose($pipes[0]);
                $workers[] = [$process, $pipes];
            }
            $deadline = microtime(true) + 15;
            while (! is_file($barrier.'.ready.0') || ! is_file($barrier.'.ready.1')) {
                if (microtime(true) > $deadline) {
                    $this->fail('Stock workers did not reach the barrier');
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
