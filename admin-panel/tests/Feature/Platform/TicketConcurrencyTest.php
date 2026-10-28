<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Models\Commerce\Ticket;
use App\Models\Commerce\TicketAction;
use App\Models\Commerce\TicketType;
use App\Models\Commerce\Vendor;
use App\Models\Money\WalletAccount;
use App\Models\User;
use App\Services\Commerce\TicketCatalogues;
use App\Services\Commerce\TicketHolders;
use App\Services\Commerce\TicketReconciliation;
use App\Services\Commerce\VendorCatalogues;
use App\Services\Commerce\VendorOrders;
use App\Services\Money\WalletDeposits;
use App\Services\Platform\Ledger\LedgerService;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Support\DemoSchool;
use Tests\TestCase;

class TicketConcurrencyTest extends TestCase
{
    private array $data;

    private User $finance;

    private User $parent;

    private User $recipient;

    private User $operator;

    private array $quotes = [];

    private TicketType $type;

    protected function setUp(): void
    {
        parent::setUp();
        Artisan::call('migrate:fresh', ['--force' => true]);
        $this->seedLikeInstaller();
        DemoSchool::build();
        putenv('BILLING_FIXTURE_ACTION=prepare');
        $this->data = require base_path('e2e/support/tickets-fixture.php');
        app(CurrentSchool::class)->set($this->data['school']);
        $this->finance = User::where('email', $this->data['finance'])->firstOrFail();
        $this->parent = User::findOrFail($this->data['parents'][0]);
        $this->recipient = User::findOrFail($this->data['parents'][1]);
        $this->recipient->forceFill(['email_verified_at' => now()])->save();
        $this->operator = User::findOrFail($this->data['operator_id']);
        $d = app(WalletDeposits::class);
        $claim = $d->claim($this->recipient, ['operation_id' => (string) Str::uuid(), 'amount' => '10.00', 'method' => 'eft', 'source_reference' => 'SYNTHETIC-RACE-ADMISSION-CLAIM', 'paid_on' => now()->toDateString(), 'evidence' => 'Actual original synthetic admission buyer backing', 'confirmed' => true]);
        $d->verify($this->finance, $claim->id, ['operation_id' => (string) Str::uuid(), 'amount' => '10.00', 'method' => 'eft', 'source_reference' => 'SYNTHETIC-RACE-ADMISSION-VERIFIED', 'received_on' => now()->toDateString(), 'evidence' => 'Independently verified original synthetic buyer backing', 'verified_actual_receipt' => true, 'verified_original_holder' => true]);
        $vendor = Vendor::findOrFail($this->data['vendor']);
        $p = app(VendorCatalogues::class)->product($this->finance, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'vendor_id' => $vendor->id, 'category_id' => $vendor->category_id, 'sku' => 'RACE-ADMISSION', 'name' => 'Last original synthetic event seat', 'unit_label' => 'admission', 'price' => '1.00', 'vat_kind' => 'none', 'vat_rate_bps' => 0, 'tracks_stock' => false, 'status' => 'active']);
        $this->type = app(TicketCatalogues::class)->type($this->finance, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'event_id' => $this->data['event'], 'product_id' => $p->id, 'kind' => 'general', 'status' => 'active', 'transfer_rule' => 'families', 'capacity' => 1, 'private_reason' => 'Exactly one original remaining admission seat']);
        foreach ([$this->parent, $this->recipient] as $person) {
            $w = WalletAccount::where('user_id', $person->id)->sole();
            $q = app(VendorOrders::class)->quote($person, ['vendor_id' => $vendor->id, 'wallet_id' => $w->id, 'lines' => [['product_id' => $p->id, 'quantity' => 1]]]);
            $this->quotes[] = $q['quote'];
        }
    }

    public static function tearDownAfterClass(): void
    {
        RefreshDatabaseState::$migrated = false;
        parent::tearDownAfterClass();
    }

    private function healthy(): void
    {
        $this->assertTrue(TicketReconciliation::healthy(app(TicketReconciliation::class)->inspect()));
        $this->assertSame([], app(LedgerService::class)->projectionDifferences());
    }

    private function work(User $actor, string $mode, array $data): array
    {
        return ['actor_id' => $actor->id, 'mode' => $mode, 'data' => $data];
    }

    private function admission(string $key): array
    {
        return ['operation_id' => $key, 'vendor_id' => $this->data['vendor'], 'revision' => 0, 'ticket_number' => $this->data['tickets'][0]['ticket_number'], 'confirmed' => true];
    }

    public function test_two_wallets_cannot_buy_one_last_admission_seat(): void
    {
        $before = (int) DB::table('ledger_accounts')->whereIn('id', WalletAccount::select('ledger_account_id'))->sum('balance_cents');
        $r = $this->parallel([$this->work($this->parent, 'purchase', ['operation_id' => (string) Str::uuid(), 'quote' => $this->quotes[0], 'confirmed' => true]), $this->work($this->recipient, 'purchase', ['operation_id' => (string) Str::uuid(), 'quote' => $this->quotes[1], 'confirmed' => true])], 'purchase');
        $this->assertCount(1, array_filter($r, fn ($r) => isset($r['id'])));
        $this->assertCount(1, array_filter($r, fn ($r) => ($r['refused'] ?? null) === 409));
        $this->assertSame(1, Ticket::where('type_id', $this->type->id)->count());
        $this->assertSame($before - 100, (int) DB::table('ledger_accounts')->whereIn('id', WalletAccount::select('ledger_account_id'))->sum('balance_cents'));
        $this->healthy();
    }

    public function test_replayed_admission_purchase_issues_one_original_ticket_and_charge(): void
    {
        $op = ['operation_id' => (string) Str::uuid(), 'quote' => $this->quotes[0], 'confirmed' => true];
        $work = $this->work($this->parent, 'purchase', $op);
        $r = $this->parallel([$work, $work], 'purchase');
        $this->assertSame($r[0], $r[1]);
        $this->assertSame(1, Ticket::where('type_id', $this->type->id)->count());
        $this->healthy();
    }

    public function test_competing_admissions_redeem_original_ticket_once(): void
    {
        $r = $this->parallel([$this->work($this->operator, 'redeem', $this->admission((string) Str::uuid())), $this->work($this->operator, 'redeem', $this->admission((string) Str::uuid()))], 'redeem');
        $this->assertCount(1, array_filter($r, fn ($r) => isset($r['receipt'])));
        $this->assertCount(1, array_filter($r, fn ($r) => ($r['refused'] ?? null) === 409));
        $this->assertSame(1, TicketAction::where('action', 'redeemed')->count());
        $this->healthy();
    }

    public function test_transfer_and_admission_cannot_both_use_original_credential(): void
    {
        $code = app(TicketHolders::class)->receiveCode($this->recipient)['receive_code'];
        $transfer = ['ticket_id' => $this->data['tickets'][0]['id'], 'operation_id' => (string) Str::uuid(), 'revision' => 0, 'receive_code' => $code, 'recipient_id' => $this->recipient->id, 'confirmed' => true];
        $before = DB::table('ledger_entries')->count();
        $r = $this->parallel([$this->work($this->parent, 'transfer', $transfer), $this->work($this->operator, 'redeem', $this->admission((string) Str::uuid()))], 'redeem');
        $this->assertCount(1, array_filter($r, fn ($r) => ! isset($r['refused'])));
        $this->assertCount(1, array_filter($r, fn ($r) => in_array($r['refused'] ?? null, [404, 409], true)));
        $this->assertSame(1, TicketAction::count());
        $this->assertSame($before, DB::table('ledger_entries')->count());
        $this->healthy();
    }

    public function test_unknown_request_resolution_prevents_a_late_admission_commit(): void
    {
        $key = (string) Str::uuid();
        $r = $this->parallel([$this->work($this->operator, 'resolve', ['vendor_id' => $this->data['vendor'], 'operation_id' => $key]), $this->work($this->operator, 'redeem', $this->admission($key))], 'redeem');
        $this->assertContains($r[0]['status'], ['committed', 'abandoned']);
        $this->assertSame($r[0]['status'] === 'committed' ? 1 : 0, TicketAction::where('action', 'redeemed')->count());
        $this->healthy();
    }

    private function parallel(array $payloads, string $mode): array
    {
        $barrier = sys_get_temp_dir().'/ticket-admission-'.bin2hex(random_bytes(12));
        $workers = [];
        try {
            foreach ($payloads as $slot => $payload) {
                $process = proc_open([PHP_BINARY, base_path('tests/Support/TicketWorker.php'), (string) $this->data['school'], (string) ($payload['actor_id'] ?? $this->finance->id), ($payload['mode'] ?? $mode), json_encode($payload['data'] ?? $payload, JSON_THROW_ON_ERROR), $barrier, (string) $slot], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, base_path());
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
