<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Models\Money\WalletDeskTopup;
use App\Models\Platform\GuardianLink;
use App\Models\Platform\LearnerApproval;
use App\Models\User;
use App\Services\Money\WalletDeskReconciliation;
use App\Services\Money\WalletDeskTopups;
use App\Services\Money\WalletFundingService;
use App\Services\Money\WalletSettings;
use App\Services\Platform\Modules\ModuleGate;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\DemoSchool;
use Tests\TestCase;

class WalletDeskConcurrencyTest extends TestCase
{
    private User $admin;

    private int $owner;

    protected function setUp(): void
    {
        parent::setUp();
        Artisan::call('migrate:fresh', ['--force' => true]);
        $this->seedLikeInstaller();
        $school = DemoSchool::build();
        $this->admin = $this->superAdmin();
        $this->owner = $school->ids['asha_user'];
        $parent = User::findOrFail($school->ids['farid_user']);
        $parent->forceFill(['email_verified_at' => now()])->save();
        GuardianLink::updateOrCreate(['learner_id' => $school->ids['asha'], 'guardian_user_id' => $parent->id], ['status' => 'active', 'can_approve' => true, 'can_see_fees' => false, 'relationship' => 'guardian']);
        LearnerApproval::firstOrCreate(['learner_id' => $school->ids['asha'], 'guardian_user_id' => $parent->id], ['consent_version' => config('identity.consent_version'), 'consented_at' => now()]);
        app(ModuleGate::class)->change('wallet', true, $this->admin);
        app(WalletSettings::class)->save($this->admin, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'currency' => 'ZAR', 'daily_spend_cents' => 5000, 'child_limits_required' => true]);
        app(WalletFundingService::class)->policy($this->admin, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'cash_allowed' => true, 'eft_allowed' => true]);
        app(WalletFundingService::class)->record($this->admin, ['operation_id' => (string) Str::uuid(), 'method' => 'cash', 'source_reference' => 'SYNTHETIC-DESK-CONCURRENCY', 'amount' => '100.00', 'received_on' => now()->toDateString(), 'school_owned' => true, 'evidence' => 'Synthetic committed concurrent backing']);
        app(WalletDeskTopups::class)->policy($this->admin, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'threshold_amount' => '25.00']);
    }

    public static function tearDownAfterClass(): void
    {
        RefreshDatabaseState::$migrated = false;
        parent::tearDownAfterClass();
    }

    public function test_independent_process_reservations_cannot_overdraw_the_master_wallet(): void
    {
        $results = $this->parallel([(string) Str::uuid(), (string) Str::uuid()]);
        $this->assertCount(1, array_filter($results, fn ($r) => isset($r['id'])));
        $this->assertCount(1, array_filter($results, fn ($r) => ($r['refused'] ?? null) === 422));
        $this->assertSame(1, WalletDeskTopup::count());
        $this->assertSame(4000, app(WalletFundingService::class)->balance($this->admin)['balance_cents']);
        $this->assertTrue(WalletDeskReconciliation::healthy(app(WalletDeskReconciliation::class)->inspect()));
    }

    public function test_independent_process_same_uuid_reservations_return_one_original_receipt(): void
    {
        $key = (string) Str::uuid();
        $results = $this->parallel([$key, $key]);
        $this->assertArrayHasKey('id', $results[0]);
        $this->assertSame($results[0], $results[1]);
        $this->assertSame(1, WalletDeskTopup::count());
        $this->assertSame(2, DB::table('ledger_entries')->count());
        $this->assertSame(4000, app(WalletFundingService::class)->balance($this->admin)['balance_cents']);
        $this->assertTrue(WalletDeskReconciliation::healthy(app(WalletDeskReconciliation::class)->inspect()));
    }

    private function parallel(array $keys): array
    {
        $barrier = sys_get_temp_dir().'/wallet-desk-'.bin2hex(random_bytes(12));
        $workers = [];
        try {
            foreach ($keys as $slot => $key) {
                $process = proc_open([PHP_BINARY, base_path('tests/Support/WalletDeskWorker.php'), (string) $this->admin->id, (string) $this->owner, $key, $barrier, (string) $slot], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, base_path());
                $this->assertIsResource($process);
                fclose($pipes[0]);
                $workers[] = [$process, $pipes];
            }
            $deadline = microtime(true) + 15;
            while (! is_file($barrier.'.ready.0') || ! is_file($barrier.'.ready.1')) {
                if (microtime(true) > $deadline) {
                    $this->fail('Desk workers did not reach the barrier');
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
