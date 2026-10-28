<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Models\Money\CashHandover;
use App\Models\Money\CashHandoverOutcome;
use App\Models\User;
use App\Services\Money\CashCustody;
use App\Services\Money\WalletWithdrawals;
use App\Services\Platform\Ledger\LedgerService;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\DemoSchool;
use Tests\TestCase;

class CashHandoverConcurrencyTest extends TestCase
{
    private array $data;

    private User $finance;

    private User $recipient;

    protected function setUp(): void
    {
        parent::setUp();
        Artisan::call('migrate:fresh', ['--force' => true]);
        $this->seedLikeInstaller();
        DemoSchool::build();
        putenv('BILLING_FIXTURE_ACTION=prepare');
        $this->data = require base_path('e2e/support/cash-handover-fixture.php');
        app(CurrentSchool::class)->set($this->data['school']);
        $this->finance = User::where('email', $this->data['finance'])->firstOrFail();
        $this->recipient = User::findOrFail($this->data['recipient_id']);
    }

    public static function tearDownAfterClass(): void
    {
        RefreshDatabaseState::$migrated = false;
        parent::tearDownAfterClass();
    }

    private function proposal(): array
    {
        return ['operation_id' => (string) Str::uuid(), 'recipient_id' => $this->recipient->id, 'currency' => 'ZAR', 'amount' => '15.00', 'private_evidence' => 'Actual synthetic original cash prepared for physical handover', 'confirmed' => true];
    }

    private function outcome(int $id, string $status = 'accepted'): array
    {
        return ['operation_id' => (string) Str::uuid(), 'handover_id' => $id, 'status' => $status, 'private_evidence' => 'Independently verified actual original cash outcome evidence', 'confirmed' => true];
    }

    private function healthy(): void
    {
        $this->assertSame([], app(LedgerService::class)->projectionDifferences());
        $s = app(CashCustody::class);
        $this->assertSame(2000, $s->available($this->finance->id, 'ZAR')['book_cents'] + $s->available($this->recipient->id, 'ZAR')['book_cents']);
    }

    public function test_competing_proposals_cannot_reserve_another_operators_cash_or_overdraw(): void
    {
        $results = $this->parallel([$this->proposal(), $this->proposal()], 'propose');
        $this->assertCount(1, array_filter($results, fn ($r) => isset($r['id'])));
        $this->assertCount(1, array_filter($results, fn ($r) => ($r['refused'] ?? null) === 422));
        $this->assertSame(1, CashHandover::count());
        $this->assertSame(500, app(CashCustody::class)->available($this->finance->id, 'ZAR')['available_cents']);
        $this->healthy();
    }

    public function test_replayed_proposals_reserve_once_and_confirmations_move_custody_once(): void
    {
        $op = $this->proposal();
        $results = $this->parallel([$op, $op], 'propose');
        $this->assertSame($results[0], $results[1]);
        $this->assertSame(1, CashHandover::count());
        $op = $this->outcome($results[0]['id']);
        $work = ['actor_id' => $this->recipient->id, 'mode' => 'finish', 'data' => $op];
        $results = $this->parallel([$work, $work], 'finish');
        $this->assertSame($results[0], $results[1]);
        $this->assertSame(1, CashHandoverOutcome::count());
        $this->assertSame(1500, app(CashCustody::class)->available($this->recipient->id, 'ZAR')['available_cents']);
        $this->healthy();
    }

    public function test_acceptance_and_sender_cancellation_retain_exactly_one_outcome(): void
    {
        $row = app(CashCustody::class)->propose($this->finance, $this->proposal());
        $results = $this->parallel([
            ['actor_id' => $this->recipient->id, 'mode' => 'finish', 'data' => $this->outcome($row->id)],
            ['mode' => 'finish', 'data' => $this->outcome($row->id, 'cancelled')],
        ], 'finish');
        $this->assertCount(1, array_filter($results, fn ($r) => isset($r['id'])));
        $this->assertCount(1, array_filter($results, fn ($r) => ($r['refused'] ?? null) === 409));
        $this->assertSame(1, CashHandoverOutcome::count());
        $this->assertSame(0, app(CashCustody::class)->available($this->finance->id, 'ZAR')['reserved_cents']);
        $this->healthy();
    }

    public function test_cash_payout_and_handover_share_one_custody_lock(): void
    {
        $w = app(WalletWithdrawals::class);
        $w->policy($this->finance, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'cash_threshold_amount' => '250.00', 'cash_at_boundary' => false, 'accepting_requests' => true]);
        $start = now('Africa/Johannesburg')->addDay()->startOfDay()->addHours(10);
        $slot = $w->publishSlot($this->finance, ['operation_id' => (string) Str::uuid(), 'starts_at' => $start->format('Y-m-d\TH:i'), 'ends_at' => $start->copy()->addHour()->format('Y-m-d\TH:i'), 'capacity' => 1]);
        $parent = User::findOrFail($this->data['parents'][0]);
        $row = $w->request($parent, ['operation_id' => (string) Str::uuid(), 'amount' => '10.00', 'slot_id' => $slot->id, 'confirmed' => true]);
        $w->approve($this->finance, $row->id, ['operation_id' => (string) Str::uuid(), 'verified_original_holder' => true]);
        $pay = ['operation_id' => (string) Str::uuid(), 'withdrawal_id' => $row->id, 'payout_reference' => 'SYNTHETIC-ORIGINAL-COMPETING-CASH', 'paid_on' => now()->toDateString(), 'private_evidence' => 'Independently verified original actual parent cash pickup', 'verified_actual_payout' => true, 'verified_original_holder' => true];
        $results = $this->parallel([['mode' => 'payout', 'data' => $pay], ['mode' => 'propose', 'data' => $this->proposal()]], 'propose');
        $this->assertCount(1, array_filter($results, fn ($r) => isset($r['id'])));
        $this->assertCount(1, array_filter($results, fn ($r) => ($r['refused'] ?? null) === 422));
        $this->assertSame(1, CashHandover::count() + DB::table('wallet_withdrawal_actions')->where('school_id', $this->data['school'])->where('status', 'paid')->count());
        $a = app(CashCustody::class)->available($this->finance->id, 'ZAR');
        $this->assertGreaterThanOrEqual(0, $a['available_cents']);
        $this->assertSame([], app(LedgerService::class)->projectionDifferences());
    }

    private function parallel(array $payloads, string $mode): array
    {
        $barrier = sys_get_temp_dir().'/cash-custody-'.bin2hex(random_bytes(12));
        $workers = [];
        try {
            foreach ($payloads as $slot => $payload) {
                $process = proc_open([PHP_BINARY, base_path('tests/Support/CashHandoverWorker.php'), (string) $this->data['school'], (string) ($payload['actor_id'] ?? $this->finance->id), ($payload['mode'] ?? $mode), json_encode($payload['data'] ?? $payload, JSON_THROW_ON_ERROR), $barrier, (string) $slot], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, base_path());
                $this->assertIsResource($process);
                fclose($pipes[0]);
                $workers[] = [$process, $pipes];
            }
            $deadline = microtime(true) + 15;
            while (! is_file($barrier.'.ready.0') || ! is_file($barrier.'.ready.1')) {
                if (microtime(true) > $deadline) {
                    $this->fail('Cash custody workers did not reach the barrier');
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
