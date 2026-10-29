<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Models\Money\FeeAccount;
use App\Models\Money\FeeCreditRelease;
use App\Models\Money\FeePayerCorrection;
use App\Models\User;
use App\Services\Money\FeeAllocations;
use App\Services\Money\FeePayerCorrections;
use App\Services\Money\FeePaymentReconciliation;
use App\Services\Money\FeePayments;
use App\Services\Money\FeeStatements;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\DemoSchool;
use Tests\TestCase;

class PayerCorrectionConcurrencyTest extends TestCase
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
        $this->data = require base_path('e2e/support/payer-corrections-fixture.php');
        app(CurrentSchool::class)->set($this->data['school']);
        $this->finance = User::where('email', $this->data['finance'])->firstOrFail();
    }

    protected function tearDown(): void
    {
        RefreshDatabaseState::$migrated = false;
        parent::tearDown();
    }

    private function intent(): array
    {
        $input = ['operation_id' => (string) Str::uuid(), 'invoice_line_id' => $this->data['correction_line'], 'holder_user_id' => $this->data['parents'][0], 'new_holder_user_id' => $this->data['recipient'], 'amount' => '1.00', 'reason' => 'Synthetic reviewed original unpaid payer race'];
        $p = app(FeePayerCorrections::class)->preview($this->finance, $input);

        return $input + ['fingerprint' => $p['fingerprint'], 'confirmed' => true];
    }

    public function test_two_distinct_corrections_cannot_move_one_original_unpaid_share_twice(): void
    {
        $a = $this->intent();
        $b = $this->intent();
        $before = DB::table('ledger_accounts')->where('school_id', $this->data['school'])->pluck('balance_cents', 'id')->all();
        $r = $this->parallel([$a, $b], 'correct');
        $this->assertCount(1, array_filter($r, fn ($v) => isset($v['id'])));
        $this->assertCount(1, array_filter($r, fn ($v) => in_array($v['refused'] ?? null, [409, 422], true)));
        $this->assertSame(1, FeePayerCorrection::count());
        $this->assertSame(0, FeeCreditRelease::count());
        $this->assertSame($before, DB::table('ledger_accounts')->where('school_id', $this->data['school'])->pluck('balance_cents', 'id')->all());
        $this->assertTrue(FeePaymentReconciliation::healthy(app(FeePaymentReconciliation::class)->inspect()));
    }

    public function test_duplicate_original_correction_replays_one_credit_and_replacement_invoice(): void
    {
        $input = $this->intent();
        $r = $this->parallel([$input, $input], 'correct');
        $this->assertSame($r[0]['id'], $r[1]['id']);
        $this->assertSame(1, FeePayerCorrection::count());
        $this->assertSame(0, FeeCreditRelease::count());
        $this->assertTrue(FeePaymentReconciliation::healthy(app(FeePaymentReconciliation::class)->inspect()));
    }

    public function test_allocation_and_correction_share_the_same_original_unpaid_lock(): void
    {
        $input = $this->intent();
        $payment = app(FeePayments::class)->record($this->finance, ['operation_id' => (string) Str::uuid(), 'fee_account_id' => $this->data['account'], 'holder_user_id' => $this->data['parents'][0], 'pinned_learner_id' => null, 'method' => 'eft', 'source_reference' => 'SYNTHETIC-PAYER-RACE-'.Str::uuid(), 'amount' => '1.00', 'received_on' => $this->data['date'], 'confirmed' => true]);
        $quote = app(FeeAllocations::class)->preview($this->finance, $payment->id);
        $allocation = ['operation_id' => (string) Str::uuid(), 'payment_id' => $payment->id, 'fingerprint' => $quote['fingerprint'], 'confirmed' => true];
        $original = $payment->getRawOriginal();
        $r = $this->parallel([['mode' => 'correct', 'data' => $input], ['mode' => 'allocate', 'data' => $allocation]], 'correct');
        $this->assertCount(1, array_filter($r, fn ($v) => isset($v['id'])));
        $this->assertSame($original, $payment->fresh()->getRawOriginal());
        $this->assertSame(0, FeeCreditRelease::count());
        foreach (app(FeeStatements::class)->charges(FeeAccount::findOrFail($this->data['account'])) as $row) {
            $this->assertGreaterThanOrEqual(0, $row['outstanding_cents']);
        }
        $this->assertTrue(FeePaymentReconciliation::healthy(app(FeePaymentReconciliation::class)->inspect()));
    }

    private function parallel(array $payloads, string $mode): array
    {
        $barrier = sys_get_temp_dir().'/ticket-admission-'.bin2hex(random_bytes(12));
        $workers = [];
        try {
            foreach ($payloads as $slot => $payload) {
                $process = proc_open([PHP_BINARY, base_path('tests/Support/PayerCorrectionWorker.php'), (string) $this->data['school'], (string) ($payload['actor_id'] ?? $this->finance->id), ($payload['mode'] ?? $mode), json_encode($payload['data'] ?? $payload, JSON_THROW_ON_ERROR), $barrier, (string) $slot], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, base_path());
                $this->assertIsResource($process);
                fclose($pipes[0]);
                $workers[] = [$process, $pipes];
            }
            $deadline = microtime(true) + 15;
            while (! is_file($barrier.'.ready.0') || ! is_file($barrier.'.ready.1')) {
                if (microtime(true) > $deadline) {
                    $this->fail('Admission workers did not reach the barrier');
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
