<?php

declare(strict_types=1);

namespace Tests\Feature\Money;

use App\Models\Money\WalletAccount;
use App\Models\Money\WalletWithdrawal;
use App\Models\Money\WalletWithdrawalAction;
use App\Models\People\Enrolment;
use App\Models\Platform\GuardianLink;
use App\Models\Platform\SchoolMembership;
use App\Models\User;
use App\Services\Money\ExternalDisbursements;
use App\Services\Money\FeeAccounts;
use App\Services\Money\FeePayments;
use App\Services\Money\FeeRefundPolicies;
use App\Services\Money\FeeRefunds;
use App\Services\Money\WalletDeposits;
use App\Services\Money\WalletOperations;
use App\Services\Money\WalletSettings;
use App\Services\Money\WalletWithdrawalReconciliation;
use App\Services\Money\WalletWithdrawals;
use App\Services\Platform\Ledger\LedgerService;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Sanctum;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Tests\Concerns\UsesTwoSchools;
use Tests\Concerns\ValidatesOpenApiResponses;
use Tests\TestCase;

class WalletWithdrawalsTest extends TestCase
{
    use UsesTwoSchools, ValidatesOpenApiResponses;

    private User $admin;

    private User $parent;

    private int $slot;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        $this->admin = $this->superAdmin();
        $this->parent = User::findOrFail($this->schoolA->ids['farid_user']);
        $this->parent->forceFill(['email_verified_at' => now()])->save();
        app(ModuleGate::class)->change('wallet', true, $this->admin);
        app(ModuleGate::class)->change('fees', false, $this->admin);
        app(WalletSettings::class)->save($this->admin, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'currency' => 'ZAR', 'daily_spend_cents' => 5000, 'child_limits_required' => true]);
        app(WalletDeposits::class)->policy($this->admin, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'cash_allowed' => true, 'eft_allowed' => true]);
        foreach (['cash', 'eft'] as $method) {
            $claim = app(WalletDeposits::class)->claim($this->parent, ['operation_id' => (string) Str::uuid(), 'amount' => '500.00', 'method' => $method, 'source_reference' => 'SYNTHETIC-CLAIM-'.$method, 'paid_on' => now()->toDateString(), 'evidence' => 'Private synthetic original parent proof', 'confirmed' => true]);
            app(WalletDeposits::class)->verify($this->admin, $claim->id, ['operation_id' => (string) Str::uuid(), 'amount' => '500.00', 'method' => $method, 'source_reference' => 'SYNTHETIC-RECEIVED-'.$method, 'received_on' => now()->toDateString(), 'evidence' => 'Independent synthetic actual Finance proof', 'verified_original_holder' => true, 'verified_actual_receipt' => true]);
        }
        $this->policy();
        $tomorrow = now(app(CurrentSchool::class)->school()->timezone ?? 'UTC')->addDay()->startOfDay()->addHours(10);
        $this->slot = app(WalletWithdrawals::class)->publishSlot($this->admin, ['operation_id' => (string) Str::uuid(), 'starts_at' => $tomorrow->format('Y-m-d\TH:i'), 'ends_at' => $tomorrow->copy()->addHour()->format('Y-m-d\TH:i'), 'capacity' => 2])->id;
    }

    private function policy(bool $cashBoundary = false, int $revision = 0): void
    {
        app(WalletWithdrawals::class)->policy($this->admin, ['operation_id' => (string) Str::uuid(), 'revision' => $revision, 'cash_threshold_amount' => '250.00', 'cash_at_boundary' => $cashBoundary, 'accepting_requests' => true]);
    }

    private function input(string $amount = '300.00', array $extra = []): array
    {
        return array_replace(['operation_id' => (string) Str::uuid(), 'amount' => $amount, 'private_bank_instructions' => 'Private synthetic bank details for original Farid holder', 'confirmed' => true], $extra);
    }

    private function approved(array $input = []): WalletWithdrawal
    {
        $row = app(WalletWithdrawals::class)->request($this->parent, $input ?: $this->input());
        app(WalletWithdrawals::class)->approve($this->admin, $row->id, ['operation_id' => (string) Str::uuid(), 'verified_original_holder' => true]);

        return $row;
    }

    private function payout(string $ref = 'SYNTHETIC-ACTUAL-PAYOUT'): array
    {
        return ['operation_id' => (string) Str::uuid(), 'payout_reference' => $ref, 'paid_on' => now()->toDateString(), 'private_evidence' => 'Private synthetic verified bank payout proof', 'verified_actual_payout' => true, 'verified_original_holder' => true];
    }

    private function balance(): int
    {
        return app(LedgerService::class)->balance(WalletAccount::sole()->ledger_account_id)->cents;
    }

    private function refuses(callable $work, int $status): void
    {
        try {
            $work();
            $this->fail('Expected withdrawal refusal');
        } catch (ValidationException) {
            $this->assertSame(422, $status);
        } catch (ModelNotFoundException) {
            $this->assertSame(404, $status);
        } catch (HttpExceptionInterface $e) {
            $this->assertSame($status, $e->getStatusCode(), $e->getMessage());
        }
    }

    public function test_request_reserves_original_funds_once_and_cancellation_restores_only_that_wallet(): void
    {
        $input = $this->input();
        $row = app(WalletWithdrawals::class)->request($this->parent, $input);
        $this->assertSame($row->id, app(WalletWithdrawals::class)->request($this->parent, $input)->id);
        $this->assertSame(70000, $this->balance());
        $this->assertSame(30000, app(LedgerService::class)->balance($row->pending_account_id)->cents);
        $this->assertSame('committed', app(WalletOperations::class)->resolve($this->parent, $input['operation_id'])['status']);
        $this->assertSame(0, DB::table('financial_disbursements')->count());
        $cancel = ['operation_id' => (string) Str::uuid(), 'private_reason' => 'Synthetic change of mind', 'confirmed' => true];
        app(WalletWithdrawals::class)->finish($this->parent, $row->id, 'cancelled', $cancel);
        app(WalletWithdrawals::class)->finish($this->parent, $row->id, 'cancelled', $cancel);
        $this->assertSame(100000, $this->balance());
        $this->assertSame(0, app(LedgerService::class)->balance($row->pending_account_id)->cents);
        $this->assertSame('cancelled', app(WalletWithdrawals::class)->state($row));
        $this->assertSame(1, WalletWithdrawalAction::count());
    }

    public function test_exactly_r250_is_a_school_choice_and_cash_uses_an_available_pickup_slot(): void
    {
        $bank = app(WalletWithdrawals::class)->request($this->parent, $this->input('250.00'));
        $this->assertSame('eft', $bank->method);
        $this->policy(true, 1);
        $cash = app(WalletWithdrawals::class)->request($this->parent, $this->input('250.00', ['slot_id' => $this->slot, 'private_bank_instructions' => null]));
        $this->assertSame('cash', $cash->method);
        $this->refuses(fn () => app(WalletWithdrawals::class)->request($this->parent, $this->input('249.99')), 422);
        $this->refuses(fn () => app(WalletWithdrawals::class)->policy($this->admin, ['operation_id' => (string) Str::uuid(), 'revision' => 2, 'cash_threshold_amount' => '251.00', 'cash_at_boundary' => true, 'accepting_requests' => true]), 422);
    }

    public function test_independent_finance_approval_and_verified_payout_clear_the_reservation_once(): void
    {
        $row = app(WalletWithdrawals::class)->request($this->parent, $this->input());
        $this->refuses(fn () => app(WalletWithdrawals::class)->finish($this->admin, $row->id, 'paid', $this->payout()), 409);
        $this->parent->givePermissionTo('wallet-finance-manage');
        $this->refuses(fn () => app(WalletWithdrawals::class)->approve($this->parent, $row->id, ['operation_id' => (string) Str::uuid(), 'verified_original_holder' => true]), 403);
        app(WalletWithdrawals::class)->approve($this->admin, $row->id, ['operation_id' => (string) Str::uuid(), 'verified_original_holder' => true]);
        $this->refuses(fn () => app(WalletWithdrawals::class)->finish($this->parent, $row->id, 'cancelled', ['operation_id' => (string) Str::uuid(), 'private_reason' => 'Cannot cancel after processing starts', 'confirmed' => true]), 409);
        $input = $this->payout();
        app(WalletWithdrawals::class)->finish($this->admin, $row->id, 'paid', $input);
        app(WalletWithdrawals::class)->finish($this->admin, $row->id, 'paid', $input);
        $this->assertSame(70000, $this->balance());
        $this->assertSame(0, app(LedgerService::class)->balance($row->pending_account_id)->cents);
        $this->assertSame(1, DB::table('financial_disbursements')->count());
        $this->assertSame('wallet', DB::table('financial_disbursements')->value('domain'));
        $this->assertSame(20000, (int) DB::table('ledger_accounts')->where('reference', 'wallet:eft:ZAR')->value('balance_cents'));
        $this->refuses(fn () => app(ExternalDisbursements::class)->available('eft', 'ZAR', ' synthetic-actual-payout '), 409);
        $this->assertSame([], app(LedgerService::class)->projectionDifferences());
    }

    public function test_current_learners_and_mixed_learner_roles_never_request_withdrawals(): void
    {
        $learner = User::findOrFail($this->schoolA->ids['asha_user']);
        $this->refuses(fn () => app(WalletWithdrawals::class)->request($learner, $this->input()), 403);
        $this->parent->assignRole('Student');
        $this->refuses(fn () => app(WalletWithdrawals::class)->request($this->parent, $this->input()), 403);
        $this->assertSame(0, WalletWithdrawal::count());
        $this->assertSame(100000, $this->balance());
    }

    public function test_slot_capacity_closure_and_released_booking_are_transactional(): void
    {
        $input = fn () => $this->input('10.00', ['slot_id' => $this->slot, 'private_bank_instructions' => null]);
        $one = app(WalletWithdrawals::class)->request($this->parent, $input());
        app(WalletWithdrawals::class)->request($this->parent, $input());
        $this->refuses(fn () => app(WalletWithdrawals::class)->request($this->parent, $input()), 409);
        app(WalletWithdrawals::class)->finish($this->parent, $one->id, 'cancelled', ['operation_id' => (string) Str::uuid(), 'private_reason' => 'Synthetic slot released', 'confirmed' => true]);
        app(WalletWithdrawals::class)->request($this->parent, $input());
        app(WalletWithdrawals::class)->closeSlot($this->admin, $this->slot, ['operation_id' => (string) Str::uuid(), 'private_reason' => 'Close new bookings only', 'confirmed' => true]);
        $this->assertSame([], app(WalletWithdrawals::class)->index($this->parent)['slots']);
        $this->assertSame(98000, $this->balance());
    }

    public function test_private_bank_details_and_payout_proof_are_encrypted_and_never_in_personal_history(): void
    {
        $row = $this->approved();
        $input = $this->payout();
        app(WalletWithdrawals::class)->finish($this->admin, $row->id, 'paid', $input);
        $this->assertStringNotContainsString('Private synthetic', DB::table('wallet_withdrawals')->value('private_bank_instructions'));
        $this->assertArrayNotHasKey('private_bank_instructions', $row->toArray());
        $page = app(WalletWithdrawals::class)->index($this->parent);
        $this->assertSame('paid', $page['items'][0]['status']);
        $this->assertStringNotContainsString('Private synthetic', json_encode($page, JSON_THROW_ON_ERROR));
        $this->assertSame('WW-PAY-'.WalletWithdrawalAction::where('withdrawal_id', $row->id)->where('status', 'paid')->sole()->id, $page['items'][0]['payout_reference']);
    }

    public function test_approval_revocation_and_unfunded_actual_asset_refuse_payment_without_losing_reserved_money(): void
    {
        $row = $this->approved($this->input('600.00'));
        $this->refuses(fn () => app(WalletWithdrawals::class)->finish($this->admin, $row->id, 'paid', $this->payout()), 422);
        $this->assertSame('approved', app(WalletWithdrawals::class)->state($row));
        $this->assertSame(60000, app(LedgerService::class)->balance($row->pending_account_id)->cents);
        $this->admin->roles()->firstOrFail()->revokePermissionTo('wallet-finance-manage');
        $this->refuses(fn () => app(WalletWithdrawals::class)->finish($this->admin, $row->id, 'paid', $this->payout()), 403);
    }

    public function test_wrong_school_finance_cannot_approve_or_read_the_original_withdrawal(): void
    {
        $row = app(WalletWithdrawals::class)->request($this->parent, $this->input());
        app(CurrentSchool::class)->run(2, function () use ($row): void {
            $finance = User::findOrFail($this->schoolB->ids['tara_user']);
            $finance->assignRole('Super Admin');
            app(ModuleGate::class)->change('wallet', true, $finance);
            $this->refuses(fn () => app(WalletWithdrawals::class)->approve($finance, $row->id, ['operation_id' => (string) Str::uuid(), 'verified_original_holder' => true]), 404);
            $this->assertSame(0, app(WalletWithdrawals::class)->index($finance)['total']);
        });
    }

    public function test_verified_original_holder_can_still_be_paid_after_leaving_without_becoming_school_money(): void
    {
        $row = $this->approved();
        $this->parent->forceFill(['status' => 0])->save();
        SchoolMembership::where('school_id', 1)->where('user_id', $this->parent->id)->update(['status' => 'ended']);
        app(WalletWithdrawals::class)->finish($this->admin, $row->id, 'paid', $this->payout());
        $this->assertSame(70000, $this->balance());
        $this->assertSame('paid', app(WalletWithdrawals::class)->state($row));
    }

    public function test_sql_and_rollback_refuse_to_edit_or_erase_new_withdrawal_history(): void
    {
        $row = $this->approved();
        $failed = false;
        try {
            DB::table('wallet_withdrawals')->where('id', $row->id)->update(['amount_cents' => 1]);
        } catch (QueryException) {
            $failed = true;
        }$this->assertTrue($failed);
        $migration = require database_path('migrations/2026_10_28_150000_add_reserved_adult_wallet_withdrawals.php');
        $this->expectException(\RuntimeException::class);
        $migration->down();
    }

    public function test_own_v2_request_and_cancellation_preserve_private_bank_details_and_balanced_money(): void
    {
        Sanctum::actingAs($this->parent);
        $this->getJson('/api/v2/wallets/withdrawals', ['X-School-Id' => '1'])->assertOk()->assertJsonPath('data.can_request', true)->assertJsonPath('data.cash_threshold_cents', 25000)->assertJsonPath('data.cash_at_boundary', false);
        $input = $this->input();
        $headers = ['X-School-Id' => '1', 'Idempotency-Key' => $input['operation_id']];
        $first = $this->postJson('/api/v2/wallets/withdrawals', $input, $headers)->assertOk()->assertJsonPath('data.status', 'requested')->assertJsonPath('data.amount_cents', 30000)->assertJsonMissingPath('data.private_bank_instructions');
        $id = (int) $first->json('data.id');
        $this->postJson('/api/v2/wallets/withdrawals', $input, $headers)->assertOk()->assertJsonPath('data.id', $id);
        $this->assertSame(70000, $this->balance());
        $cancel = ['operation_id' => (string) Str::uuid(), 'private_reason' => 'Original holder changed their mind', 'confirmed' => true];
        $this->postJson('/api/v2/wallets/withdrawals/'.$id.'/cancel', $cancel, ['X-School-Id' => '1', 'Idempotency-Key' => $cancel['operation_id']])->assertOk()->assertJsonPath('data.status', 'cancelled');
        $this->assertSame(100000, $this->balance());
        $this->assertTrue(WalletWithdrawalReconciliation::healthy(app(WalletWithdrawalReconciliation::class)->inspect()));
    }

    public function test_v2_wrong_owner_and_learner_cannot_cancel_or_request_adult_money(): void
    {
        $row = app(WalletWithdrawals::class)->request($this->parent, $this->input());
        $teacher = User::findOrFail($this->schoolA->ids['tara_user']);
        Sanctum::actingAs($teacher);
        $cancel = ['operation_id' => (string) Str::uuid(), 'private_reason' => 'Synthetic wrong holder attempt', 'confirmed' => true];
        $this->postJson('/api/v2/wallets/withdrawals/'.$row->id.'/cancel', $cancel, ['X-School-Id' => '1', 'Idempotency-Key' => $cancel['operation_id']])->assertNotFound();
        Sanctum::actingAs(User::findOrFail($this->schoolA->ids['asha_user']));
        $input = $this->input();
        $this->postJson('/api/v2/wallets/withdrawals', $input, ['X-School-Id' => '1', 'Idempotency-Key' => $input['operation_id']])->assertForbidden();
        $this->getJson('/api/v2/wallets/withdrawals', ['X-School-Id' => '1'])->assertOk()->assertJsonPath('data.is_adult', false)->assertJsonPath('data.can_request', false)->assertJsonPath('data.total', 0);
    }

    public function test_finance_panel_approves_records_actual_payout_and_prints_a_clean_receipt(): void
    {
        $row = app(WalletWithdrawals::class)->request($this->parent, $this->input());
        $bind = ['expected_school_id' => 1, 'expected_account_id' => $this->admin->id];
        $this->actingAs($this->admin, 'web')->get('/wallet-withdrawals')->assertOk();
        $this->get('/wallet-withdrawals/'.$row->id)->assertOk()->assertSee('Private synthetic bank details for original Farid holder');
        $this->post('/wallet-withdrawals/'.$row->id.'/approve', ['operation_id' => (string) Str::uuid(), 'verified_original_holder' => true] + $bind)->assertRedirect('/wallet-withdrawals/'.$row->id);
        $this->post('/wallet-withdrawals/'.$row->id.'/paid', $this->payout() + $bind)->assertRedirect('/wallet-withdrawals/'.$row->id);
        $this->get('/wallet-withdrawals/'.$row->id.'/receipt')->assertOk()->assertSee('300.00')->assertDontSee('Private synthetic')->assertDontSee('SYNTHETIC-ACTUAL-PAYOUT');
        $this->assertTrue(WalletWithdrawalReconciliation::healthy(app(WalletWithdrawalReconciliation::class)->inspect()));
    }

    public function test_finance_can_return_only_an_existing_departed_adults_original_wallet(): void
    {
        $this->parent->forceFill(['status' => 0])->save();
        SchoolMembership::where('school_id', 1)->where('user_id', $this->parent->id)->update(['status' => 'ended']);
        $input = $this->input('300.00', ['private_reason' => 'Original parent left the school; verified original bank holder', 'verified_original_holder' => true]);
        $row = app(WalletWithdrawals::class)->request($this->admin, $input, $this->parent->id);
        $this->assertSame($this->parent->id, $row->holder_user_id);
        $this->assertSame(70000, $this->balance());
        app(WalletWithdrawals::class)->approve($this->admin, $row->id, ['operation_id' => (string) Str::uuid(), 'verified_original_holder' => true]);
        app(WalletWithdrawals::class)->finish($this->admin, $row->id, 'paid', $this->payout());
        $this->assertSame('paid', app(WalletWithdrawals::class)->state($row));
        $learner = User::findOrFail($this->schoolA->ids['asha_user']);
        $this->refuses(fn () => app(WalletWithdrawals::class)->request($this->admin, $this->input('300.00', ['private_reason' => 'Synthetic learner return refused', 'verified_original_holder' => true]), $learner->id), 403);
    }

    public function test_disabled_history_retains_reservations_and_own_receipts_without_accepting_new_requests(): void
    {
        $row = app(WalletWithdrawals::class)->request($this->parent, $this->input());
        app(ModuleGate::class)->change('wallet', false, $this->admin);
        Sanctum::actingAs($this->parent);
        $this->getJson('/api/v2/wallets/withdrawals', ['X-School-Id' => '1'])->assertOk()->assertJsonPath('data.read_only', true)->assertJsonPath('data.can_request', false)->assertJsonPath('data.items.0.reference', 'WW-'.$row->id);
        $input = $this->input();
        $this->postJson('/api/v2/wallets/withdrawals', $input, ['X-School-Id' => '1', 'Idempotency-Key' => $input['operation_id']])->assertForbidden();
        $this->assertSame(70000, $this->balance());
    }

    public function test_one_outside_payout_cannot_be_booked_as_both_a_wallet_withdrawal_and_a_fee_refund(): void
    {
        app(ModuleGate::class)->change('fees', true, $this->admin);
        GuardianLink::updateOrCreate(['learner_id' => $this->schoolA->ids['asha'], 'guardian_user_id' => $this->parent->id], ['status' => 'active', 'can_see_fees' => true, 'can_approve' => true, 'relationship' => 'guardian']);
        Enrolment::create(['learner_id' => $this->schoolA->ids['asha'], 'class_section_id' => $this->schoolA->ids['section_a'], 'session_year_id' => $this->schoolA->ids['session_year'], 'starts_on' => '2026-01-01', 'legacy_dates' => false, 'reason' => 'Synthetic shared payout identity account']);
        $account = app(FeeAccounts::class)->save($this->admin, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'name' => 'Synthetic outgoing identity family', 'currency' => 'ZAR', 'session_year_id' => $this->schoolA->ids['session_year'], 'learner_ids' => [$this->schoolA->ids['asha']], 'holders' => [['user_id' => $this->parent->id, 'share_percent' => '100']]]);
        app(FeeRefundPolicies::class)->save($this->admin, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'cash_allowed' => true, 'eft_allowed' => true, 'independent_approval' => false]);
        foreach ([true, false] as $walletFirst) {
            $reference = $walletFirst ? 'SYNTHETIC-OUTGOING-WALLET-FIRST' : 'SYNTHETIC-OUTGOING-FEES-FIRST';
            $payment = app(FeePayments::class)->record($this->admin, ['operation_id' => (string) Str::uuid(), 'fee_account_id' => $account->id, 'holder_user_id' => $this->parent->id, 'pinned_learner_id' => null, 'method' => 'eft', 'source_reference' => 'SYNTHETIC-FEE-INCOMING-'.Str::uuid(), 'amount' => '300.00', 'received_on' => now()->toDateString(), 'confirmed' => true]);
            $refund = app(FeeRefunds::class)->prepare($this->admin, ['operation_id' => (string) Str::uuid(), 'payment_id' => $payment->id, 'amount' => '300.00', 'method' => 'eft', 'private_explanation' => 'Synthetic original-holder return']);
            app(FeeRefunds::class)->reserve($this->admin, $refund->id, ['operation_id' => (string) Str::uuid(), 'confirmed' => true]);
            $withdrawal = $this->approved();
            $feeInput = ['operation_id' => (string) Str::uuid(), 'confirmed' => true, 'verified_original_holder' => true, 'payout_reference' => $reference, 'private_evidence' => 'Independent synthetic actual bank payout proof'];
            if ($walletFirst) {
                app(WalletWithdrawals::class)->finish($this->admin, $withdrawal->id, 'paid', $this->payout($reference));
                $this->refuses(fn () => app(FeeRefunds::class)->payout($this->admin, $refund->id, $feeInput), 409);
                $this->assertSame('reserved', app(FeeRefunds::class)->state($refund));
            } else {
                app(FeeRefunds::class)->payout($this->admin, $refund->id, $feeInput);
                $this->refuses(fn () => app(WalletWithdrawals::class)->finish($this->admin, $withdrawal->id, 'paid', $this->payout($reference)), 409);
                $this->assertSame('approved', app(WalletWithdrawals::class)->state($withdrawal));
            }
        }
        $this->assertSame(2, DB::table('financial_disbursements')->count());
        $this->assertSame(40000, $this->balance());
        $this->assertTrue(WalletWithdrawalReconciliation::healthy(app(WalletWithdrawalReconciliation::class)->inspect()));
    }
}
