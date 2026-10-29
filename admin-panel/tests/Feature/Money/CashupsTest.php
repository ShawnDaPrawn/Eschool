<?php

declare(strict_types=1);

namespace Tests\Feature\Money;

use App\Models\Money\CashupCount;
use App\Models\Money\WalletAccount;
use App\Models\Money\WalletParentDeposit;
use App\Models\User;
use App\Services\Commerce\VendorCatalogues;
use App\Services\Commerce\VendorOrders;
use App\Services\Commerce\VendorSettlements;
use App\Services\Money\CashCustody;
use App\Services\Money\CashFlows;
use App\Services\Money\Cashups;
use App\Services\Money\WalletDeposits;
use App\Services\Money\WalletSettings;
use App\Services\Money\WalletWithdrawals;
use App\Services\Platform\Identity\MembershipService;
use App\Services\Platform\Ledger\LedgerService;
use App\Services\Platform\Ledger\Money;
use App\Services\Platform\Ledger\Posting;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Tests\Concerns\UsesTwoSchools;
use Tests\TestCase;

class CashupsTest extends TestCase
{
    use UsesTwoSchools;

    private User $finance;

    private User $parent;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        $this->finance = $this->superAdmin();
        $this->parent = User::findOrFail($this->schoolA->ids['farid_user']);
        $this->parent->forceFill(['email_verified_at' => now()])->save();
        app(ModuleGate::class)->change('wallet', true, $this->finance);
        app(ModuleGate::class)->change('fees', false, $this->finance);
        app(WalletSettings::class)->save($this->finance, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'currency' => 'ZAR', 'daily_spend_cents' => 5000, 'child_limits_required' => true]);
        app(WalletDeposits::class)->policy($this->finance, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'cash_allowed' => true, 'eft_allowed' => true]);
        $this->deposit('cash', '20.00');
        $this->deposit('eft', '60.00');
    }

    private function deposit(string $method, string $amount, ?string $date = null): void
    {
        $date ??= now()->toDateString();
        $claim = app(WalletDeposits::class)->claim($this->parent, ['operation_id' => (string) Str::uuid(), 'amount' => $amount, 'method' => $method, 'source_reference' => 'SYNTHETIC-CLAIM-'.Str::uuid(), 'paid_on' => $date, 'evidence' => 'Synthetic original actual parent deposit evidence', 'confirmed' => true]);
        app(WalletDeposits::class)->verify($this->finance, $claim->id, ['operation_id' => (string) Str::uuid(), 'amount' => $amount, 'method' => $method, 'source_reference' => 'SYNTHETIC-VERIFIED-'.Str::uuid(), 'received_on' => $date, 'evidence' => 'Independent actual synthetic receipt held by this Finance operator', 'verified_original_holder' => true, 'verified_actual_receipt' => true]);
    }

    private function report(): array
    {
        return app(Cashups::class)->report($this->finance, (int) $this->finance->id, 'ZAR', now()->toDateString());
    }

    private function input(array $extra = []): array
    {
        $r = $this->report();

        return array_replace(['operation_id' => (string) Str::uuid(), 'currency' => 'ZAR', 'counted_on' => now()->toDateString(), 'revision' => $r['latest']?->revision ?? 0, 'source_hash' => $r['source_hash'], 'counted_amount' => '20.00', 'private_evidence' => 'Actual counted synthetic notes and coins at this desk', 'confirmed_actual_count' => true], $extra);
    }

    private function refuses(callable $work, int $status): void
    {
        try {
            $work();
            $this->fail('Expected private cash control refusal');
        } catch (ValidationException) {
            $this->assertSame(422, $status);
        } catch (HttpExceptionInterface$e) {
            $this->assertSame($status, $e->getStatusCode(), $e->getMessage());
        }
    }

    public function test_cashup_counts_real_cash_once_without_posting_money_or_counting_eft(): void
    {
        $r = $this->report();
        $this->assertTrue($r['healthy']);
        $this->assertSame(0, $r['opening_cents']);
        $this->assertSame(2000, $r['receipts_cents']);
        $this->assertSame(0, $r['payouts_cents']);
        $this->assertSame(2000, $r['closing_cents']);
        $this->assertCount(1, $r['events']);
        $before = DB::table('ledger_entries')->count();
        $input = $this->input();
        $s = app(Cashups::class);
        $row = $s->record($this->finance, $input);
        $this->assertSame($row->id, $s->record($this->finance, $input)->id);
        $this->assertSame(0, $row->variance_cents);
        $this->assertSame(1, CashupCount::count());
        $this->assertSame($before, DB::table('ledger_entries')->count());
        $this->assertFalse($this->report()['needs_review']);
    }

    public function test_difference_requires_private_explanation_without_changing_the_ledger(): void
    {
        $input = $this->input(['counted_amount' => '19.50']);
        $this->refuses(fn () => app(Cashups::class)->record($this->finance, $input), 422);
        $this->assertSame(0, CashupCount::count());
        $row = app(Cashups::class)->record($this->finance, $input + ['private_reason' => 'Synthetic fifty cents shortage awaiting physical review']);
        $this->assertSame(-50, $row->variance_cents);
        $raw = DB::table('cashup_counts')->where('id', $row->id)->first();
        $this->assertStringNotContainsString('shortage', $raw->private_reason);
        $this->assertStringNotContainsString('synthetic notes', $raw->private_evidence);
        $this->assertSame(2000, $this->report()['cash_asset_cents']);
    }

    public function test_late_actual_receipt_invalidates_original_count_and_correction_retains_both_versions(): void
    {
        $s = app(Cashups::class);
        $original = $s->record($this->finance, $this->input());
        $stale = $this->input();
        $this->deposit('cash', '10.00');
        $this->assertTrue($this->report()['needs_review']);
        $this->refuses(fn () => $s->record($this->finance, $stale), 409);
        $this->refuses(fn () => $s->record($this->finance, $this->input(['counted_amount' => '30.00'])), 422);
        $corrected = $s->record($this->finance, $this->input(['counted_amount' => '30.00', 'private_reason' => 'Verified late recorded original cash receipt and counted again']));
        $this->assertSame(2, $corrected->revision);
        $this->assertSame(2000, $original->fresh()->counted_cents);
        $this->assertSame(3000, $corrected->counted_cents);
        $this->assertSame(2, CashupCount::count());
        $this->assertFalse($this->report()['needs_review']);
    }

    public function test_role_school_ended_membership_and_another_operator_hash_cannot_record_a_count(): void
    {
        $s = app(Cashups::class);
        $this->refuses(fn () => $s->report($this->parent, (int) $this->finance->id, 'ZAR', now()->toDateString()), 403);
        $this->refuses(fn () => app(CurrentSchool::class)->run(2, fn () => $s->report($this->finance, (int) $this->finance->id, 'ZAR', now()->toDateString())), 403);
        $other = User::create(['first_name' => 'Synthetic', 'last_name' => 'Other cashier', 'email' => 'other.cashier@example.test', 'password' => Hash::make('SyntheticCashier2026'), 'status' => 1, 'password_change_required' => false]);
        $other->assignRole('Super Admin');
        app(MembershipService::class)->grant($other, 1, 'Super Admin');
        $foreign = $s->report($other, (int) $other->id, 'ZAR', now()->toDateString());
        $this->refuses(fn () => $s->record($this->finance, $this->input(['source_hash' => $foreign['source_hash']])), 409);
        app(MembershipService::class)->end($this->finance, 1, 'Super Admin');
        $this->refuses(fn () => $s->record($this->finance, $this->input()), 403);
        $this->assertSame(0, CashupCount::count());
    }

    public function test_past_cash_carries_forward_and_pause_keeps_private_control_history_available(): void
    {
        $this->deposit('cash', '5.00', now()->subDay()->toDateString());
        $r = $this->report();
        $this->assertSame(500, $r['opening_cents']);
        $this->assertSame(2000, $r['receipts_cents']);
        $this->assertSame(2500, $r['closing_cents']);
        app(ModuleGate::class)->change('wallet', false, $this->finance);
        $row = app(Cashups::class)->record($this->finance, $this->input(['counted_amount' => '25.00']));
        $this->assertSame(2500, $row->counted_cents);
        $this->assertSame(2500, $this->report()['cash_asset_cents']);
    }

    public function test_unattributed_cash_and_projection_differences_refuse_closure(): void
    {
        $cash = WalletParentDeposit::where('method', 'cash')->sole()->asset_account_id;
        $bank = WalletParentDeposit::where('method', 'eft')->sole()->asset_account_id;
        $ledger = app(LedgerService::class);
        $ledger->post('synthetic:unattributed:cash', 'synthetic:unattributed:cash', 'Synthetic unknown cash movement', [Posting::debit($cash, Money::ofCents(100, 'ZAR')), Posting::credit($bank, Money::ofCents(100, 'ZAR'))]);
        $this->assertSame(1, $this->report()['unattributed_cash_postings']);
        $this->refuses(fn () => app(Cashups::class)->record($this->finance, $this->input()), 409);
        DB::table('ledger_accounts')->where('id', $cash)->update(['balance_cents' => 2101]);
        $this->assertNotEmpty(app(CashFlows::class)->inspect('ZAR')['cash_projection_differences']);
        $this->assertSame(0, CashupCount::count());
    }

    public function test_sql_cash_count_history_and_retained_rollback_are_immutable(): void
    {
        $row = app(Cashups::class)->record($this->finance, $this->input());
        foreach (['update', 'delete'] as $action) {
            try {
                if ($action === 'update') {
                    DB::table('cashup_counts')->where('id', $row->id)->update(['counted_cents' => 1]);
                } else {
                    DB::table('cashup_counts')->where('id', $row->id)->delete();
                }$this->fail('Original cash count history was changed');
            } catch (QueryException) {
                $this->assertSame(1, CashupCount::count());
            }
        }
        $m = require database_path('migrations/2026_10_28_210000_add_daily_operator_cashup_counts.php');
        try {
            $m->down();
            $this->fail('Retained count rollback succeeded');
        } catch (\RuntimeException) {
            $this->assertSame(2000, $row->fresh()->counted_cents);
        }
    }

    public function test_actual_adult_wallet_cash_payout_reduces_only_its_cash_custodian(): void
    {
        $w = app(WalletWithdrawals::class);
        $w->policy($this->finance, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'cash_threshold_amount' => '250.00', 'cash_at_boundary' => false, 'accepting_requests' => true]);
        $start = now('Africa/Johannesburg')->addDay()->startOfDay()->addHours(10);
        $slot = $w->publishSlot($this->finance, ['operation_id' => (string) Str::uuid(), 'starts_at' => $start->format('Y-m-d\TH:i'), 'ends_at' => $start->copy()->addHour()->format('Y-m-d\TH:i'), 'capacity' => 2]);
        $row = $w->request($this->parent, ['operation_id' => (string) Str::uuid(), 'amount' => '5.00', 'slot_id' => $slot->id, 'confirmed' => true]);
        $w->approve($this->finance, $row->id, ['operation_id' => (string) Str::uuid(), 'verified_original_holder' => true]);
        $other = User::create(['first_name' => 'Synthetic', 'last_name' => 'Unfunded cashier', 'email' => 'unfunded.cashier@example.test', 'password' => Hash::make(Str::random(40)), 'status' => 1, 'password_change_required' => false]);
        $other->assignRole('Super Admin');
        app(MembershipService::class)->grant($other, 1, 'Super Admin');
        $this->refuses(fn () => $w->finish($other, $row->id, 'paid', ['operation_id' => (string) Str::uuid(), 'payout_reference' => 'SYNTHETIC-ACTUAL-COUNTED-ADULT-CASH', 'paid_on' => now()->toDateString(), 'private_evidence' => 'Independently verified actual cash given to the original parent', 'verified_actual_payout' => true, 'verified_original_holder' => true]), 422);
        $custody = app(CashCustody::class);
        $handover = $custody->propose($this->finance, ['operation_id' => (string) Str::uuid(), 'recipient_id' => $other->id, 'currency' => 'ZAR', 'amount' => '5.00', 'private_evidence' => 'Actual original cash handed to independent paying operator', 'confirmed' => true]);
        $custody->finish($other, $handover->id, 'accepted', ['operation_id' => (string) Str::uuid(), 'private_evidence' => 'Independently counted actual cash received for original-holder payout', 'confirmed' => true]);
        $w->finish($other, $row->id, 'paid', ['operation_id' => (string) Str::uuid(), 'payout_reference' => 'SYNTHETIC-ACTUAL-COUNTED-ADULT-CASH', 'paid_on' => now()->toDateString(), 'private_evidence' => 'Independently verified actual cash given to the original parent', 'verified_actual_payout' => true, 'verified_original_holder' => true]);
        $this->assertSame((int) $other->id, (int) DB::table('wallet_withdrawal_actions')->where('status', 'paid')->value('cash_operator_id'));
        $r = $this->report();
        $this->assertTrue($r['healthy']);
        $this->assertSame(500, $r['payouts_cents']);
        $this->assertSame(1500, $r['closing_cents']);
        $this->assertSame(1500, $r['cash_asset_cents']);
        $this->assertSame(1500, app(Cashups::class)->record($this->finance, $this->input(['counted_amount' => '15.00']))->counted_cents);
    }

    public function test_actual_vendor_cash_payout_is_included_but_the_wallet_purchase_is_not_cash(): void
    {
        app(ModuleGate::class)->change('vendors', true, $this->finance);
        $c = app(VendorCatalogues::class);
        $category = $c->category($this->finance, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'key' => 'food', 'name' => 'Synthetic food', 'status' => 'active']);
        $v = $c->vendor($this->finance, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'category_id' => $category->id, 'name' => 'Synthetic cash merchant', 'legal_name' => 'Synthetic original cash merchant', 'kind' => 'independent', 'status' => 'active', 'vat_registered' => false, 'refund_bearer' => 'vendor', 'chargeback_bearer' => 'vendor', 'settlement_method' => 'cash', 'settlement_cycle' => 'manual', 'settlement_delay_days' => 0, 'settlement_terms' => 'Explicit original merchant cash settlement agreement']);
        $p = $c->product($this->finance, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'vendor_id' => $v->id, 'category_id' => $category->id, 'sku' => 'CASHUP-VENDOR-01', 'name' => 'Synthetic counted merchant goods', 'unit_label' => 'each', 'price' => '12.50', 'vat_kind' => 'none', 'vat_rate_bps' => 0, 'tracks_stock' => false, 'status' => 'active']);
        $wallet = WalletAccount::sole();
        $orders = app(VendorOrders::class);
        $q = $orders->quote($this->parent, ['vendor_id' => $v->id, 'wallet_id' => $wallet->id, 'lines' => [['product_id' => $p->id, 'quantity' => 1]]]);
        $order = $orders->purchase($this->parent, ['operation_id' => (string) Str::uuid(), 'quote' => $q['quote'], 'confirmed' => true]);
        $orders->collect($this->finance, $v->id, ['operation_id' => (string) Str::uuid(), 'order_number' => $order->order_number, 'confirmed' => true]);
        $this->assertSame(2000, $this->report()['closing_cents']);
        $reviewer = User::create(['first_name' => 'Synthetic', 'last_name' => 'Finance reviewer', 'email' => 'cashup.review@example.test', 'password' => Hash::make('SyntheticReview2026'), 'status' => 1, 'password_change_required' => false]);
        $reviewer->assignRole('Super Admin');
        app(MembershipService::class)->grant($reviewer, 1, 'Super Admin');
        $s = app(VendorSettlements::class);
        $batch = $s->prepare($this->finance, ['operation_id' => (string) Str::uuid(), 'vendor_id' => $v->id, 'vendor_revision' => $v->revision, 'period_from' => now()->toDateString(), 'period_to' => now()->toDateString(), 'order_ids' => [$order->id], 'confirmed_original_terms' => true]);
        $s->approve($reviewer, $batch->id, ['operation_id' => (string) Str::uuid(), 'confirmed' => true]);
        $this->refuses(fn () => $s->finish($reviewer, $batch->id, ['operation_id' => (string) Str::uuid(), 'paid_on' => now()->toDateString(), 'private_evidence' => 'Actually paid and independently verified original merchant cash', 'payout_reference' => 'SYNTHETIC-ACTUAL-MERCHANT-CASH', 'confirmed' => true, 'verified_original_vendor' => true, 'verified_actual_payout' => true]), 422);
        $custody = app(CashCustody::class);
        $handover = $custody->propose($this->finance, ['operation_id' => (string) Str::uuid(), 'recipient_id' => $reviewer->id, 'currency' => 'ZAR', 'amount' => '12.50', 'private_evidence' => 'Actual original merchant cash handed to independent paying operator', 'confirmed' => true]);
        $custody->finish($reviewer, $handover->id, 'accepted', ['operation_id' => (string) Str::uuid(), 'private_evidence' => 'Independently counted original cash received for actual merchant payout', 'confirmed' => true]);
        $s->finish($reviewer, $batch->id, ['operation_id' => (string) Str::uuid(), 'paid_on' => now()->toDateString(), 'private_evidence' => 'Actually paid and independently verified original merchant cash', 'payout_reference' => 'SYNTHETIC-ACTUAL-MERCHANT-CASH', 'confirmed' => true, 'verified_original_vendor' => true, 'verified_actual_payout' => true]);
        $this->assertSame((int) $reviewer->id, (int) DB::table('vendor_settlement_outcomes')->value('cash_operator_id'));
        $r = $this->report();
        $this->assertTrue($r['healthy']);
        $this->assertSame(1250, $r['payouts_cents']);
        $this->assertSame(750, $r['closing_cents']);
        $this->assertSame(750, $r['cash_asset_cents']);
        $this->assertSame(750, app(Cashups::class)->record($this->finance, $this->input(['counted_amount' => '7.50']))->counted_cents);
    }

    public function test_private_panel_and_export_bind_school_actor_and_only_recorded_custodians(): void
    {
        $body = $this->input() + ['expected_school_id' => 1, 'expected_account_id' => $this->finance->id];
        $this->actingAs($this->finance, 'web')->get('/cashups')->assertOk()->assertHeader('Cache-Control', 'no-store, private');
        $this->get('/cashups?operator_id='.$this->schoolB->ids['farid_user'])->assertNotFound()->assertDontSee('Baobab');
        $this->get('/cashups/export?operator_id='.$this->schoolB->ids['farid_user'].'&currency=ZAR&counted_on='.now()->toDateString())->assertNotFound();
        $this->post('/cashups', array_replace($body, ['expected_school_id' => 2]))->assertForbidden();
        $this->post('/cashups', array_replace($body, ['expected_account_id' => $this->parent->id]))->assertForbidden();
        $this->post('/cashups', $body)->assertRedirect()->assertSessionMissing('_old_input');
        $this->post('/cashups', $body)->assertRedirect();
        $this->assertSame(1, CashupCount::count());
        $this->post('/cashups', array_replace($body, ['operation_id' => (string) Str::uuid(), 'revision' => 1, 'private_evidence' => 'short']))->assertUnprocessable()->assertSessionMissing('_old_input');
        $csv = $this->get('/cashups/export?operator_id='.$this->finance->id.'&currency=ZAR&counted_on='.now()->toDateString())->assertOk()->assertHeader('Cache-Control', 'no-store, private');
        $this->assertStringContainsString('counted_cents,variance_cents,count_revision', $csv->streamedContent());
        $this->assertStringNotContainsString('Actual counted synthetic notes', $csv->streamedContent());
    }
}
