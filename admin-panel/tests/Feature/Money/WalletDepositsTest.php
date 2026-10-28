<?php

declare(strict_types=1);

namespace Tests\Feature\Money;

use App\Models\Money\WalletAccount;
use App\Models\Money\WalletDepositAction;
use App\Models\Money\WalletDepositClaim;
use App\Models\Money\WalletParentDeposit;
use App\Models\Platform\SchoolMembership;
use App\Models\User;
use App\Services\Money\WalletDepositReconciliation;
use App\Services\Money\WalletDeposits;
use App\Services\Money\WalletFundingReconciliation;
use App\Services\Money\WalletFundingService;
use App\Services\Money\WalletOperations;
use App\Services\Money\WalletSettings;
use App\Services\Platform\Ledger\LedgerService;
use App\Services\Platform\Ledger\Money;
use App\Services\Platform\Ledger\Posting;
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

class WalletDepositsTest extends TestCase
{
    use UsesTwoSchools, ValidatesOpenApiResponses;

    private User $admin;

    private User $parent;

    private User $teacher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        $this->admin = $this->superAdmin();
        $this->parent = User::findOrFail($this->schoolA->ids['farid_user']);
        $this->teacher = User::findOrFail($this->schoolA->ids['tara_user']);
        $this->parent->forceFill(['email_verified_at' => now()])->save();
        app(ModuleGate::class)->change('wallet', true, $this->admin);
        app(ModuleGate::class)->change('fees', false, $this->admin);
        app(WalletSettings::class)->save($this->admin, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'currency' => 'ZAR', 'daily_spend_cents' => 5000, 'child_limits_required' => true]);
        app(WalletFundingService::class)->policy($this->admin, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'cash_allowed' => true, 'eft_allowed' => true]);
        app(WalletDeposits::class)->policy($this->admin, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'cash_allowed' => true, 'eft_allowed' => true]);
    }

    private function claim(array $change = []): array
    {
        return array_replace(['operation_id' => (string) Str::uuid(), 'amount' => '100.25', 'method' => 'eft', 'source_reference' => 'PARENT-CLAIM-001', 'paid_on' => now()->toDateString(), 'evidence' => 'Private synthetic parent bank evidence', 'confirmed' => true], $change);
    }

    private function verify(array $change = []): array
    {
        return array_replace(['operation_id' => (string) Str::uuid(), 'amount' => '90.12', 'method' => 'eft', 'source_reference' => 'BANK-VERIFIED-001', 'received_on' => now()->toDateString(), 'evidence' => 'Private independent synthetic Finance evidence', 'verified_original_holder' => true, 'verified_actual_receipt' => true], $change);
    }

    private function refuses(callable $call, int $status): void
    {
        try {
            $call();
            $this->fail('Expected deposit refusal');
        } catch (ValidationException) {
            $this->assertSame(422, $status);
        } catch (ModelNotFoundException) {
            $this->assertSame(404, $status);
        } catch (HttpExceptionInterface $e) {
            $this->assertSame($status, $e->getStatusCode(), $e->getMessage());
        }
    }

    public function test_parent_claim_creates_no_money_and_retains_private_original_proof(): void
    {
        $input = $this->claim();
        $row = app(WalletDeposits::class)->claim($this->parent, $input);
        $this->assertSame($row->id, app(WalletDeposits::class)->claim($this->parent, $input)->id);
        $this->assertSame(0, DB::table('ledger_entries')->count());
        $this->assertSame(0, DB::table('financial_receipts')->count());
        $this->assertSame(1, WalletAccount::count());
        $this->assertSame($this->parent->id, WalletAccount::sole()->user_id);
        $this->assertNotSame($input['evidence'], DB::table('wallet_deposit_claims')->value('evidence'));
        $this->assertSame($input['evidence'], $row->evidence);
        $this->assertArrayNotHasKey('evidence', $row->toArray());
        $this->refuses(fn () => app(WalletDeposits::class)->claim($this->parent, array_replace($input, ['amount' => '101.25'])), 409);
    }

    public function test_independent_verification_credits_actual_money_to_original_parent_and_never_the_master(): void
    {
        $row = app(WalletDeposits::class)->claim($this->parent, $this->claim());
        $input = $this->verify();
        $deposit = app(WalletDeposits::class)->verify($this->admin, $row->id, $input);
        $this->assertSame($deposit->id, app(WalletDeposits::class)->verify($this->admin, $row->id, $input)->id);
        $this->assertSame(10025, $row->fresh()->requested_cents);
        $this->assertSame(9012, app(LedgerService::class)->balance(WalletAccount::sole()->ledger_account_id)->cents);
        $this->assertSame(1, DB::table('ledger_entries')->count());
        $this->assertSame(0, DB::table('ledger_accounts')->where('reference', 'wallet:master:ZAR')->count());
        $this->assertSame('parent', DB::table('financial_receipts')->value('domain'));
        $this->assertTrue(WalletFundingReconciliation::healthy(app(WalletFundingReconciliation::class)->inspect()));
        $receipt = app(WalletDeposits::class)->index($this->parent)['items'][0];
        $this->assertSame('verified', $receipt['status']);
        $this->assertSame(9012, $receipt['verified_receipt']['amount_cents']);
        $this->assertStringNotContainsString('independent', json_encode($receipt, JSON_THROW_ON_ERROR));
        $this->assertStringNotContainsString('bank evidence', json_encode($receipt, JSON_THROW_ON_ERROR));
        $this->assertSame([], app(LedgerService::class)->projectionDifferences());
    }

    public function test_parent_with_finance_permission_cannot_verify_their_own_claim(): void
    {
        $row = app(WalletDeposits::class)->claim($this->parent, $this->claim());
        $this->parent->givePermissionTo('wallet-finance-manage');
        $this->refuses(fn () => app(WalletDeposits::class)->verify($this->parent, $row->id, $this->verify()), 403);
        $this->assertSame(0, WalletParentDeposit::count());
        $this->assertSame(0, DB::table('ledger_entries')->count());
    }

    public function test_already_received_money_keeps_its_original_holder_after_they_leave(): void
    {
        $row = app(WalletDeposits::class)->claim($this->parent, $this->claim());
        $this->parent->forceFill(['status' => 0])->save();
        SchoolMembership::where('school_id', 1)->where('user_id', $this->parent->id)->update(['status' => 'inactive']);
        app(WalletDeposits::class)->verify($this->admin, $row->id, $this->verify());
        $this->assertSame($this->parent->id, WalletAccount::sole()->user_id);
        $this->assertSame(9012, app(LedgerService::class)->balance(WalletAccount::sole()->ledger_account_id)->cents);
        $this->refuses(fn () => app(WalletDeposits::class)->index($this->parent), 403);
    }

    public function test_duplicate_receipt_is_refused_across_parent_and_school_funding_in_both_directions(): void
    {
        $funding = ['operation_id' => (string) Str::uuid(), 'method' => 'eft', 'source_reference' => 'BANK-VERIFIED-001', 'amount' => '90.12', 'received_on' => now()->toDateString(), 'school_owned' => true, 'evidence' => 'Synthetic independent school-owned funding'];
        app(WalletFundingService::class)->record($this->admin, $funding);
        $row = app(WalletDeposits::class)->claim($this->parent, $this->claim());
        $this->refuses(fn () => app(WalletDeposits::class)->verify($this->admin, $row->id, $this->verify()), 409);
        $this->assertSame(0, WalletParentDeposit::count());
        app(WalletDeposits::class)->verify($this->admin, $row->id, $this->verify(['source_reference' => 'PARENT-VERIFIED-002']));
        $funding['operation_id'] = (string) Str::uuid();
        $funding['source_reference'] = 'PARENT-VERIFIED-002';
        $this->refuses(fn () => app(WalletFundingService::class)->record($this->admin, $funding), 409);
        $this->assertSame(2, DB::table('ledger_entries')->count());
        $this->assertSame(2, DB::table('financial_receipts')->count());
    }

    public function test_parent_cancel_and_finance_rejection_move_no_money_and_cannot_be_verified_later(): void
    {
        $row = app(WalletDeposits::class)->claim($this->parent, $this->claim());
        $action = ['operation_id' => (string) Str::uuid(), 'confirmed' => true];
        app(WalletDeposits::class)->finish($this->parent, $row->id, 'cancelled', $action);
        app(WalletDeposits::class)->finish($this->parent, $row->id, 'cancelled', $action);
        $this->refuses(fn () => app(WalletDeposits::class)->verify($this->admin, $row->id, $this->verify()), 409);
        $other = app(WalletDeposits::class)->claim($this->parent, $this->claim());
        app(WalletDeposits::class)->finish($this->admin, $other->id, 'rejected', ['operation_id' => (string) Str::uuid(), 'confirmed' => true, 'reason_code' => 'not_verified', 'private_note' => 'Private synthetic verifier rejection reason']);
        $this->assertSame(2, WalletDepositAction::count());
        $this->assertSame(0, DB::table('ledger_entries')->count());
        $this->assertStringNotContainsString('Private synthetic', json_encode(app(WalletDeposits::class)->index($this->parent), JSON_THROW_ON_ERROR));
    }

    public function test_role_verification_method_future_and_fractional_amount_rules_apply(): void
    {
        $this->refuses(fn () => app(WalletDeposits::class)->claim($this->teacher, $this->claim()), 403);
        $this->refuses(fn () => app(WalletDeposits::class)->claim($this->parent, $this->claim(['amount' => '1.001'])), 422);
        $this->refuses(fn () => app(WalletDeposits::class)->claim($this->parent, $this->claim(['paid_on' => now()->addDays(3)->toDateString()])), 422);
        $this->parent->forceFill(['email_verified_at' => null])->save();
        $this->refuses(fn () => app(WalletDeposits::class)->claim($this->parent, $this->claim()), 403);
        $this->assertSame(0, WalletDepositClaim::count());
        $this->assertFalse(app(WalletDeposits::class)->index($this->teacher)['can_submit']);
    }

    public function test_verification_refuses_current_finance_revocation_even_on_permanent_replay(): void
    {
        $row = app(WalletDeposits::class)->claim($this->parent, $this->claim());
        $input = $this->verify();
        app(WalletDeposits::class)->verify($this->admin, $row->id, $input);
        $this->admin->roles()->firstOrFail()->revokePermissionTo('wallet-finance-manage');
        $this->refuses(fn () => app(WalletDeposits::class)->verify($this->admin, $row->id, $input), 403);
        $this->assertSame(1, WalletParentDeposit::count());
    }

    public function test_wrong_school_finance_cannot_verify_or_read_original_parent_request(): void
    {
        $row = app(WalletDeposits::class)->claim($this->parent, $this->claim());
        app(CurrentSchool::class)->run(2, function () use ($row): void {
            $actor = User::findOrFail($this->schoolB->ids['tara_user']);
            $actor->assignRole('Super Admin');
            app(ModuleGate::class)->change('wallet', true, $actor);
            $this->refuses(fn () => app(WalletDeposits::class)->verify($actor, $row->id, $this->verify()), 404);
            $this->assertSame(0, app(WalletDeposits::class)->index($actor)['total']);
        });
        $this->assertSame(0, WalletParentDeposit::count());
    }

    public function test_cash_retains_actual_verifier_custody_and_no_bank_settlement_reference(): void
    {
        $row = app(WalletDeposits::class)->claim($this->parent, $this->claim(['method' => 'cash']));
        $deposit = app(WalletDeposits::class)->verify($this->admin, $row->id, $this->verify(['method' => 'cash']));
        $this->assertSame($this->admin->id, $deposit->cash_operator_id);
        $this->assertNull($deposit->settlement_reference);
        $this->assertSame('cash_desk', DB::table('ledger_accounts')->where('id', $deposit->asset_account_id)->value('type'));
    }

    public function test_disabled_history_and_interrupted_claim_recovery_preserve_the_original_intent(): void
    {
        $input = $this->claim();
        app(WalletDeposits::class)->claim($this->parent, $input);
        $this->assertSame('committed', app(WalletOperations::class)->resolve($this->parent, $input['operation_id'])['status']);
        $closed = $this->claim();
        app(WalletOperations::class)->resolve($this->parent, $closed['operation_id']);
        $this->refuses(fn () => app(WalletDeposits::class)->claim($this->parent, $closed), 409);
        app(ModuleGate::class)->change('wallet', false, $this->admin);
        $this->assertFalse(app(WalletDeposits::class)->index($this->parent)['can_submit']);
        $this->assertSame(1, app(WalletDeposits::class)->index($this->parent)['total']);
        $this->refuses(fn () => app(WalletDeposits::class)->claim($this->parent, $this->claim()), 403);
    }

    public function test_parent_money_and_original_proof_cannot_be_edited_or_rolled_back(): void
    {
        $row = app(WalletDeposits::class)->claim($this->parent, $this->claim());
        $deposit = app(WalletDeposits::class)->verify($this->admin, $row->id, $this->verify());
        try {
            DB::table('wallet_parent_deposits')->where('id', $deposit->id)->update(['amount_cents' => 1]);
            $this->fail('Original money was edited');
        } catch (QueryException) {
            $this->assertSame(9012, $deposit->fresh()->amount_cents);
        }
        $m = require database_path('migrations/2026_10_28_140000_add_verified_parent_wallet_deposits.php');
        $this->expectException(\RuntimeException::class);
        $m->down();
    }

    public function test_v2_claim_and_verified_receipts_match_contract_without_exposing_proof(): void
    {
        Sanctum::actingAs($this->parent);
        $input = $this->claim(['source_reference' => '  parent   api  001  ']);
        $response = $this->postJson('/api/v2/wallets/deposits', $input, ['X-School-Id' => '1', 'Idempotency-Key' => $input['operation_id']])->assertOk()->assertJsonPath('data.status', 'pending')->assertJsonPath('data.requested_reference', 'PARENT API 001');
        $response->assertJsonMissingPath('data.evidence');
        $claim = (int) $response->json('data.id');
        $this->getJson('/api/v2/wallets/deposits', ['X-School-Id' => '1'])->assertOk()->assertJsonPath('data.total', 1)->assertJsonPath('data.can_submit', true);
        app(WalletDeposits::class)->verify($this->admin, $claim, $this->verify());
        $this->getJson('/api/v2/wallets/deposits', ['X-School-Id' => '1'])->assertOk()->assertJsonPath('data.items.0.status', 'verified')->assertJsonPath('data.items.0.verified_receipt.amount_cents', 9012)->assertJsonMissingPath('data.items.0.verified_receipt.evidence');
    }

    public function test_v2_teacher_cannot_claim_and_another_verified_parent_cannot_cancel_its_owner(): void
    {
        $claim = app(WalletDeposits::class)->claim($this->parent, $this->claim());
        Sanctum::actingAs($this->teacher);
        $input = $this->claim();
        $this->postJson('/api/v2/wallets/deposits', $input, ['X-School-Id' => '1', 'Idempotency-Key' => $input['operation_id']])->assertForbidden();
        $this->getJson('/api/v2/wallets/deposits', ['X-School-Id' => '1'])->assertOk()->assertJsonPath('data.total', 0)->assertJsonPath('data.is_parent', false);
        $other = User::create(['first_name' => 'Other', 'last_name' => 'Synthetic Parent', 'email' => 'other.deposit.'.Str::uuid().'@example.test', 'password' => bcrypt('SyntheticWalletTests2026!'), 'school_id' => 1, 'status' => 1]);
        $other->forceFill(['email_verified_at' => now()])->save();
        $other->assignRole('Parent');
        Sanctum::actingAs($other);
        $cancel = ['operation_id' => (string) Str::uuid(), 'confirmed' => true];
        $this->postJson('/api/v2/wallets/deposits/'.$claim->id.'/cancel', $cancel, ['X-School-Id' => '1', 'Idempotency-Key' => $cancel['operation_id']])->assertNotFound();
        $this->assertSame(0, WalletDepositAction::count());
    }

    public function test_parent_methods_can_pause_without_disabling_internal_wallets_or_history(): void
    {
        app(WalletDeposits::class)->claim($this->parent, $this->claim());
        app(WalletDeposits::class)->policy($this->admin, ['operation_id' => (string) Str::uuid(), 'revision' => 1, 'cash_allowed' => false, 'eft_allowed' => false]);
        $page = app(WalletDeposits::class)->index($this->parent);
        $this->assertFalse($page['can_submit']);
        $this->assertFalse($page['read_only']);
        $this->assertSame(1, $page['total']);
        $this->assertTrue(app(ModuleGate::class)->writable('wallet'));
        $this->refuses(fn () => app(WalletDeposits::class)->claim($this->parent, $this->claim()), 422);
    }

    public function test_finance_panel_verifies_corrected_amount_once_and_prints_only_the_safe_receipt(): void
    {
        $claim = app(WalletDeposits::class)->claim($this->parent, $this->claim());
        $this->actingAs($this->admin, 'web')->get('/wallet-deposits')->assertOk();
        $this->get('/wallet-deposits/'.$claim->id)->assertOk()->assertSee('Private synthetic parent bank evidence');
        $input = $this->verify() + ['expected_school_id' => 1, 'expected_account_id' => $this->admin->id];
        $this->post('/wallet-deposits/'.$claim->id.'/verify', $input)->assertRedirect('/wallet-deposits/'.$claim->id);
        $this->post('/wallet-deposits/'.$claim->id.'/verify', $input)->assertRedirect('/wallet-deposits/'.$claim->id);
        $this->get('/wallet-deposits/'.$claim->id.'/receipt')->assertOk()->assertSee('90.12')->assertDontSee('Private independent synthetic Finance evidence')->assertDontSee('Private synthetic parent bank evidence');
        $this->assertSame(1, WalletParentDeposit::count());
    }

    public function test_v2_foreign_parent_and_finance_cannot_read_or_cancel_another_schools_request(): void
    {
        $claim = app(WalletDeposits::class)->claim($this->parent, $this->claim());
        [$foreignParent, $foreignFinance] = app(CurrentSchool::class)->run(2, function (): array {
            $finance = User::findOrFail($this->schoolB->ids['tara_user']);
            $finance->assignRole('Super Admin');
            app(ModuleGate::class)->change('wallet', true, $finance);
            $parent = User::findOrFail($this->schoolB->ids['farid_user']);
            $parent->forceFill(['email_verified_at' => now()])->save();

            return [$parent, $finance];
        });
        Sanctum::actingAs($foreignParent);
        $this->getJson('/api/v2/wallets/deposits', ['X-School-Id' => '2'])->assertOk()->assertJsonPath('data.school_id', 2)->assertJsonPath('data.total', 0);
        $cancel = ['operation_id' => (string) Str::uuid(), 'confirmed' => true];
        $this->postJson('/api/v2/wallets/deposits/'.$claim->id.'/cancel', $cancel, ['X-School-Id' => '2', 'Idempotency-Key' => $cancel['operation_id']])->assertNotFound();
        $this->getJson('/api/v2/wallets/deposits', ['X-School-Id' => '1'])->assertForbidden();
        $this->actingAs($foreignFinance, 'web')->withSession(['school_id' => 2]);
        $this->get('/wallet-deposits/'.$claim->id)->assertNotFound();
        $this->get('/wallet-deposits/'.$claim->id.'/receipt')->assertNotFound();
        $this->assertSame(0, DB::table('wallet_deposit_actions')->count());
        $this->assertSame(0, DB::table('ledger_entries')->count());
    }

    public function test_failed_private_finance_validation_never_flashes_proof_and_stale_actor_binding_is_refused(): void
    {
        $claim = app(WalletDeposits::class)->claim($this->parent, $this->claim());
        $this->actingAs($this->admin, 'web');
        $input = $this->verify(['amount' => '1.001']) + ['expected_school_id' => 1, 'expected_account_id' => $this->admin->id];
        $this->post('/wallet-deposits/'.$claim->id.'/verify', $input)->assertUnprocessable()->assertSessionMissing('_old_input');
        $input = $this->verify() + ['expected_school_id' => 1, 'expected_account_id' => $this->parent->id];
        $this->post('/wallet-deposits/'.$claim->id.'/verify', $input)->assertForbidden();
        $this->assertSame(0, DB::table('ledger_entries')->count());
    }

    public function test_deposit_domain_reconciliation_rejects_unattributed_money_and_preserves_projection_safety(): void
    {
        $claim = app(WalletDeposits::class)->claim($this->parent, $this->claim());
        $deposit = app(WalletDeposits::class)->verify($this->admin, $claim->id, $this->verify());
        $this->assertTrue(WalletDepositReconciliation::healthy(app(WalletDepositReconciliation::class)->inspect()));
        $source = 'wallet-parent-deposit:unattributed';
        app(LedgerService::class)->post($source, $source, 'Synthetic unattributed parent deposit', [Posting::debit($deposit->asset_account_id, Money::ofCents(1, 'ZAR')), Posting::credit(WalletAccount::sole()->ledger_account_id, Money::ofCents(1, 'ZAR'))]);
        $this->assertSame(1, app(WalletDepositReconciliation::class)->inspect()['unattributed_parent_deposits']);
    }
}
