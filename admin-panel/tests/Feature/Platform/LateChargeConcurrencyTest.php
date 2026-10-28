<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Models\Money\FeeLateCharge;
use App\Models\User;
use App\Services\Money\FeeAllocations;
use App\Services\Money\FeeLateChargePolicies;
use App\Services\Money\FeeLateCharges;
use App\Services\Money\FeePaymentReconciliation;
use App\Services\Money\FeePayments;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Tests\Support\DemoSchool;
use Tests\TestCase;

class LateChargeConcurrencyTest extends TestCase
{
    private array $data;

    private User $finance;

    protected function setUp(): void
    {
        parent::setUp();
        Artisan::call('migrate:fresh', ['--force' => true]);
        $this->seedLikeInstaller();
        DemoSchool::build();
        putenv('BILLING_FIXTURE_ACTION=prepare');
        $this->data = require base_path('e2e/support/late-charges-fixture.php');
        app(CurrentSchool::class)->set($this->data['school']);
        $this->finance = User::where('email', $this->data['finance'])->firstOrFail();
        app(FeeLateChargePolicies::class)->save($this->finance, $this->rule());
    }

    protected function tearDown(): void
    {
        RefreshDatabaseState::$migrated = false;
        parent::tearDown();
    }

    private function rule(array $change = []): array
    {
        return array_replace(['operation_id' => (string) Str::uuid(), 'revision' => 0, 'session_year_id' => $this->data['year'], 'currency' => 'ZAR', 'enabled' => true, 'scope' => 'annual_only', 'basis' => 'flat', 'flat_amount' => '0.50', 'rate_percent' => null, 'maximum_gross' => '0.25', 'grace_days' => 1, 'due_days' => 10, 'tax_treatment' => 'no_vat', 'vat_rate' => '0.00', 'private_reason' => 'Explicit synthetic school one-off late rule'], $change);
    }

    private function intent(): array
    {
        $input = ['operation_id' => (string) Str::uuid(), 'original_invoice_line_id' => $this->data['late_line'], 'holder_user_id' => $this->data['parents'][0], 'reason' => 'Reviewed original overdue debt and separate receipt credit'];
        $p = app(FeeLateCharges::class)->preview($this->finance, $input);

        return $input + ['fingerprint' => $p['fingerprint'], 'confirmed' => true];
    }

    public function test_distinct_requests_cannot_charge_one_original_payer_debt_twice(): void
    {
        $r = $this->parallel([$this->intent(), $this->intent()], 'late');
        $this->assertCount(1, array_filter($r, fn ($v) => isset($v['id'])));
        $this->assertCount(1, array_filter($r, fn ($v) => ($v['refused'] ?? null) === 409));
        $this->assertSame(1, FeeLateCharge::count());
        $this->assertSame(25, (int) FeeLateCharge::sum('amount_cents'));
        $this->assertTrue(FeePaymentReconciliation::healthy(app(FeePaymentReconciliation::class)->inspect()));
    }

    public function test_duplicate_original_late_request_has_one_invoice_and_one_identity(): void
    {
        $input = $this->intent();
        $r = $this->parallel([$input, $input], 'late');
        $this->assertSame($r[0]['id'], $r[1]['id']);
        $this->assertSame(1, FeeLateCharge::count());
        $this->assertTrue(FeePaymentReconciliation::healthy(app(FeePaymentReconciliation::class)->inspect()));
    }

    public function test_disabling_policy_and_posting_share_one_rule_lock(): void
    {
        $input = $this->intent();
        $r = $this->parallel([['mode' => 'late', 'data' => $input], ['mode' => 'policy', 'data' => $this->rule(['revision' => 1, 'enabled' => false])]], 'late');
        $this->assertArrayHasKey('id', $r[1]);
        $this->assertSame(isset($r[0]['id']) ? 1 : 0, FeeLateCharge::count());
        if (! isset($r[0]['id'])) {
            $this->assertSame(422, $r[0]['refused']);
        }$this->assertTrue(FeePaymentReconciliation::healthy(app(FeePaymentReconciliation::class)->inspect()));
    }

    public function test_payment_allocation_and_late_charge_recheck_original_debt_under_one_lock(): void
    {
        $input = $this->intent();
        $payment = app(FeePayments::class)->record($this->finance, ['operation_id' => (string) Str::uuid(), 'fee_account_id' => $this->data['account'], 'holder_user_id' => $this->data['parents'][0], 'pinned_learner_id' => null, 'method' => 'eft', 'source_reference' => 'SYNTHETIC-LATE-ALLOCATION-'.Str::uuid(), 'amount' => '1.00', 'received_on' => now('Africa/Johannesburg')->toDateString(), 'confirmed' => true]);
        $p = app(FeeAllocations::class)->preview($this->finance, $payment->id);
        $allocate = ['operation_id' => (string) Str::uuid(), 'payment_id' => $payment->id, 'fingerprint' => $p['fingerprint'], 'confirmed' => true];
        $r = $this->parallel([['mode' => 'late', 'data' => $input], ['mode' => 'allocate', 'data' => $allocate]], 'late');
        $this->assertCount(1, array_filter($r, fn ($v) => isset($v['id'])));
        $this->assertTrue(FeePaymentReconciliation::healthy(app(FeePaymentReconciliation::class)->inspect()));
    }

    private function parallel(array $payloads, string $mode): array
    {
        $barrier = sys_get_temp_dir().'/late-charge-'.bin2hex(random_bytes(12));
        $workers = [];
        try {
            foreach ($payloads as $slot => $payload) {
                $process = proc_open([PHP_BINARY, base_path('tests/Support/LateChargeWorker.php'), (string) $this->data['school'], (string) ($payload['actor_id'] ?? $this->finance->id), ($payload['mode'] ?? $mode), json_encode($payload['data'] ?? $payload, JSON_THROW_ON_ERROR), $barrier, (string) $slot], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, base_path());
                $this->assertIsResource($process);
                fclose($pipes[0]);
                $workers[] = [$process, $pipes];
            }
            $deadline = microtime(true) + 15;
            while (! is_file($barrier.'.ready.0') || ! is_file($barrier.'.ready.1')) {
                if (microtime(true) > $deadline) {
                    $this->fail('Late-charge workers did not reach the barrier');
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
