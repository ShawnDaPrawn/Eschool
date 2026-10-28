<?php

declare(strict_types=1);

namespace Tests\Feature\Money;

use App\Models\Money\WalletAccount;
use App\Models\Money\WalletDeskAction;
use App\Models\Money\WalletDeskTopup;
use App\Models\Platform\GuardianLink;
use App\Models\Platform\LearnerApproval;
use App\Models\Platform\SchoolMembership;
use App\Models\User;
use App\Policies\Money\WalletPolicy;
use App\Services\Money\WalletDeskReconciliation;
use App\Services\Money\WalletDeskTopups;
use App\Services\Money\WalletFundingService;
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
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Tests\Concerns\UsesTwoSchools;
use Tests\Concerns\ValidatesOpenApiResponses;
use Tests\TestCase;

class WalletDeskTopupsTest extends TestCase
{
    use UsesTwoSchools, ValidatesOpenApiResponses;

    private User $admin;

    private User $other;

    private User $parent;

    private User $child;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->setDateTime(2026, 6, 15, 12, 0, 0));
        $this->setUpTwoSchools();
        $this->admin = $this->superAdmin();
        $this->other = User::findOrFail($this->schoolA->ids['tara_user']);
        $this->parent = User::findOrFail($this->schoolA->ids['farid_user']);
        $this->child = User::findOrFail($this->schoolA->ids['asha_user']);
        SchoolMembership::create(['school_id' => 1, 'user_id' => $this->other->id, 'role' => 'Super Admin', 'status' => 'active']);
        $this->other->assignRole('Super Admin');
        app(ModuleGate::class)->change('wallet', true, $this->admin);
        app(ModuleGate::class)->change('fees', false, $this->admin);
        GuardianLink::updateOrCreate(['learner_id' => $this->schoolA->ids['asha'], 'guardian_user_id' => $this->parent->id], ['status' => 'active', 'can_approve' => true, 'can_see_fees' => false, 'relationship' => 'guardian']);
        $this->parent->forceFill(['email_verified_at' => now()])->save();
        LearnerApproval::firstOrCreate(['learner_id' => $this->schoolA->ids['asha'], 'guardian_user_id' => $this->parent->id], ['consent_version' => config('identity.consent_version'), 'consented_at' => now()]);
        app(WalletSettings::class)->save($this->admin, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'currency' => 'ZAR', 'daily_spend_cents' => 5000, 'child_limits_required' => true]);
        app(WalletFundingService::class)->policy($this->admin, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'cash_allowed' => true, 'eft_allowed' => true]);
        app(WalletFundingService::class)->record($this->admin, ['operation_id' => (string) Str::uuid(), 'method' => 'cash', 'source_reference' => 'SCHOOL-FUNDING-1', 'amount' => '100.00', 'received_on' => '2026-06-15', 'school_owned' => true, 'evidence' => 'Synthetic school-owned master backing']);
        app(WalletDeskTopups::class)->policy($this->admin, $this->policy());
    }

    private function policy(array $change = []): array
    {
        return array_replace(['operation_id' => (string) Str::uuid(), 'revision' => 0, 'threshold_amount' => '25.00'], $change);
    }

    private function grant(array $change = []): array
    {
        return array_replace(['operation_id' => (string) Str::uuid(), 'owner_user_id' => $this->child->id, 'amount' => '20.00', 'reason' => 'Private synthetic school grant reason', 'confirmed' => true], $change);
    }

    private function action(array $change = []): array
    {
        return array_replace(['operation_id' => (string) Str::uuid(), 'confirmed' => true], $change);
    }

    private function refuses(callable $call, int $status): void
    {
        try {
            $call();
            $this->fail('Expected desk refusal');
        } catch (ValidationException) {
            $this->assertSame(422, $status);
        } catch (ModelNotFoundException) {
            $this->assertSame(404, $status);
        } catch (HttpExceptionInterface $e) {
            $this->assertSame($status, $e->getStatusCode(), $e->getMessage());
        }
    }

    public function test_exact_threshold_credits_once_without_another_approval_and_issues_the_approved_wallet(): void
    {
        $input = $this->grant(['amount' => '25.00']);
        $row = app(WalletDeskTopups::class)->grant($this->admin, $input);
        $wallet = WalletAccount::sole();
        $this->assertFalse($row->requires_second_approval);
        $this->assertSame($row->id, app(WalletDeskTopups::class)->grant($this->admin, $input)->id);
        $this->assertSame('released', WalletDeskAction::sole()->action);
        $this->assertSame(2500, app(LedgerService::class)->balance($wallet->ledger_account_id)->cents);
        $this->assertSame(7500, app(WalletFundingService::class)->balance($this->admin)['balance_cents']);
        $this->assertSame(0, app(LedgerService::class)->balance($row->reserve_account_id)->cents);
        $this->assertSame(3, DB::table('ledger_entries')->count());
        $this->assertSame([], app(LedgerService::class)->projectionDifferences());
    }

    public function test_above_threshold_reserves_then_requires_another_current_finance_approver_and_replays_once(): void
    {
        $row = app(WalletDeskTopups::class)->grant($this->admin, $this->grant(['amount' => '30.00']));
        $wallet = WalletAccount::sole();
        $this->assertTrue($row->requires_second_approval);
        $this->assertSame(0, WalletDeskAction::count());
        $this->assertSame(0, app(LedgerService::class)->balance($wallet->ledger_account_id)->cents);
        $this->assertSame(3000, app(LedgerService::class)->balance($row->reserve_account_id)->cents);
        $this->assertSame(7000, app(WalletFundingService::class)->balance($this->admin)['balance_cents']);
        $this->refuses(fn () => app(WalletDeskTopups::class)->finish($this->admin, $row->id, 'released', $this->action()), 403);
        $input = $this->action();
        app(WalletDeskTopups::class)->finish($this->other, $row->id, 'released', $input);
        app(WalletDeskTopups::class)->finish($this->other, $row->id, 'released', $input);
        $this->assertSame(1, WalletDeskAction::count());
        $this->assertSame(3000, app(LedgerService::class)->balance($wallet->ledger_account_id)->cents);
        $this->assertSame(0, app(LedgerService::class)->balance($row->reserve_account_id)->cents);
        $this->assertSame(3, DB::table('ledger_entries')->count());
        $this->refuses(fn () => app(WalletDeskTopups::class)->finish($this->admin, $row->id, 'cancelled', $this->action(['note' => 'Synthetic cancellation attempt'])), 409);
    }

    public function test_cancelling_pending_school_grant_restores_master_even_after_recipient_becomes_inactive(): void
    {
        $row = app(WalletDeskTopups::class)->grant($this->admin, $this->grant(['amount' => '30.00']));
        $this->child->forceFill(['status' => 0])->save();
        $this->refuses(fn () => app(WalletDeskTopups::class)->finish($this->other, $row->id, 'released', $this->action()), 403);
        $input = $this->action(['note' => 'Synthetic inactive recipient cancellation']);
        app(WalletDeskTopups::class)->finish($this->admin, $row->id, 'cancelled', $input);
        app(WalletDeskTopups::class)->finish($this->admin, $row->id, 'cancelled', $input);
        $this->assertSame('cancelled', WalletDeskAction::sole()->action);
        $this->assertSame(10000, app(WalletFundingService::class)->balance($this->admin)['balance_cents']);
        $this->assertSame(0, app(LedgerService::class)->balance($row->reserve_account_id)->cents);
        $this->assertSame(0, app(LedgerService::class)->balance(WalletAccount::sole()->ledger_account_id)->cents);
    }

    public function test_original_independent_approval_requirement_survives_a_later_higher_threshold(): void
    {
        $row = app(WalletDeskTopups::class)->grant($this->admin, $this->grant(['amount' => '30.00']));
        app(WalletDeskTopups::class)->policy($this->admin, $this->policy(['revision' => 1, 'threshold_amount' => '100.00']));
        $this->refuses(fn () => app(WalletDeskTopups::class)->finish($this->admin, $row->id, 'released', $this->action()), 403);
        app(WalletDeskTopups::class)->finish($this->other, $row->id, 'released', $this->action());
        $this->assertTrue(WalletDeskAction::sole()->requires_second_approval);
    }

    public function test_lost_requester_authority_cannot_supply_the_first_approval_but_cancellation_remains_available(): void
    {
        $row = app(WalletDeskTopups::class)->grant($this->admin, $this->grant(['amount' => '30.00']));
        SchoolMembership::where('school_id', 1)->where('user_id', $this->admin->id)->update(['status' => 'suspended']);
        $this->refuses(fn () => app(WalletDeskTopups::class)->finish($this->other, $row->id, 'released', $this->action()), 403);
        $this->assertSame(0, WalletDeskAction::count());
        app(WalletDeskTopups::class)->finish($this->other, $row->id, 'cancelled', $this->action(['note' => 'Synthetic former requester cancellation']));
        $this->assertSame(10000, app(WalletFundingService::class)->balance($this->other)['balance_cents']);
    }

    public function test_insufficient_master_funds_and_invalid_money_leave_no_partial_wallet_reservation(): void
    {
        foreach (['100.01', '0.00', '1.001'] as $amount) {
            $this->refuses(fn () => app(WalletDeskTopups::class)->grant($this->admin, $this->grant(['amount' => $amount])), 422);
        }$this->assertSame(0, WalletDeskTopup::count());
        $this->assertSame(0, WalletAccount::count());
        $this->assertSame(1, DB::table('ledger_entries')->count());
        $this->assertSame(10000, app(WalletFundingService::class)->balance($this->admin)['balance_cents']);
    }

    public function test_wrong_role_school_owner_and_unapproved_learner_are_refused(): void
    {
        $this->refuses(fn () => app(WalletDeskTopups::class)->grant($this->parent, $this->grant()), 403);
        $this->refuses(fn () => app(WalletDeskTopups::class)->grant($this->child, $this->grant()), 403);
        $this->refuses(fn () => app(WalletDeskTopups::class)->grant($this->admin, $this->grant(['owner_user_id' => $this->schoolB->ids['asha_user']])), 404);
        LearnerApproval::where('learner_id', $this->schoolA->ids['asha'])->update(['withdrawn_at' => now()]);
        $this->refuses(fn () => app(WalletDeskTopups::class)->grant($this->admin, $this->grant()), 403);
        $this->assertSame(0, WalletDeskTopup::count());
    }

    public function test_zero_threshold_requires_second_approval_for_every_positive_grant_and_revisions_are_explicit(): void
    {
        $this->refuses(fn () => app(WalletDeskTopups::class)->policy($this->admin, $this->policy()), 409);
        app(WalletDeskTopups::class)->policy($this->admin, $this->policy(['revision' => 1, 'threshold_amount' => '0.00']));
        $row = app(WalletDeskTopups::class)->grant($this->admin, $this->grant(['amount' => '0.01']));
        $this->assertTrue($row->requires_second_approval);
        $this->assertSame(0, WalletDeskAction::count());
    }

    public function test_personal_receipts_are_own_guardian_scoped_and_never_expose_finance_evidence(): void
    {
        $row = app(WalletDeskTopups::class)->grant($this->admin, $this->grant(['amount' => '30.00']));
        $page = app(WalletDeskTopups::class)->receipts($this->parent, $row->wallet_account_id);
        $this->assertSame('pending', $page['items'][0]['status']);
        $this->assertSame((int) $this->child->id, $page['owner_user_id']);
        $this->assertStringNotContainsString('Private synthetic', json_encode($page));
        $this->assertArrayNotHasKey('requested_by', $page['items'][0]);
        $this->assertArrayNotHasKey('reserve_account_id', $page['items'][0]);
        $this->assertSame($page['items'], app(WalletDeskTopups::class)->receipts($this->child, $row->wallet_account_id)['items']);
        $other = User::findOrFail($this->schoolA->ids['gina_user']);
        $other->forceFill(['email_verified_at' => now()])->save();
        $this->refuses(fn () => app(WalletDeskTopups::class)->receipts($other, $row->wallet_account_id), 404);
        GuardianLink::where('guardian_user_id', $this->parent->id)->update(['can_approve' => false]);
        $this->refuses(fn () => app(WalletDeskTopups::class)->receipts($this->parent, $row->wallet_account_id), 404);
    }

    public function test_disabled_wallet_retains_receipts_but_refuses_new_grants_approvals_and_permanent_replays(): void
    {
        $input = $this->grant(['amount' => '30.00']);
        $row = app(WalletDeskTopups::class)->grant($this->admin, $input);
        app(ModuleGate::class)->change('wallet', false, $this->admin);
        $this->assertTrue(app(WalletDeskTopups::class)->receipts($this->parent, $row->wallet_account_id)['read_only']);
        $this->refuses(fn () => app(WalletDeskTopups::class)->grant($this->admin, $input), 403);
        $this->refuses(fn () => app(WalletDeskTopups::class)->finish($this->other, $row->id, 'released', $this->action()), 403);
        $this->assertSame('pending', app(WalletDeskTopups::class)->financeReceipt($this->admin, $row->id)['receipt']['status']);
    }

    public function test_api_receipt_contract_is_private_and_rechecks_current_guardian_authority(): void
    {
        $row = app(WalletDeskTopups::class)->grant($this->admin, $this->grant(['amount' => '30.00']));
        $url = '/api/v2/wallets/'.$row->wallet_account_id.'/desk-topups';
        $this->app['auth']->forgetGuards();
        $this->withToken($this->parent->createToken('synthetic-wallet-receipts')->plainTextToken)->withHeaders(['X-School-Id' => '1', 'X-Account-Id' => (string) $this->parent->id])->getJson($url)->assertOk()->assertHeader('Cache-Control', 'no-store, private')->assertJsonPath('data.items.0.status', 'pending')->assertJsonPath('data.wallet_id', $row->wallet_account_id)->assertJsonPath('data.items.0.amount_cents', 3000);
        GuardianLink::where('guardian_user_id', $this->parent->id)->update(['can_approve' => false]);
        $this->getJson($url)->assertNotFound();
    }

    public function test_finance_panel_binds_current_actor_and_school_for_grant_and_independent_approval(): void
    {
        $bind = ['expected_account_id' => $this->admin->id, 'expected_school_id' => 1];
        $input = $this->grant(['amount' => '30.00']);
        $this->actingAs($this->admin)->get('/wallet-topups')->assertOk()->assertHeader('Cache-Control', 'no-store, private');
        $this->post('/wallet-topups/grants', $input + [...$bind, 'expected_school_id' => 2])->assertForbidden();
        $this->post('/wallet-topups/grants', $input + $bind)->assertStatus(303);
        $row = WalletDeskTopup::sole();
        $this->get('/wallet-topups/'.$row->id.'/receipt')->assertOk()->assertSee('Awaiting independent approval')->assertSee($input['reason']);
        $this->actingAs($this->other)->post('/wallet-topups/'.$row->id.'/release', $this->action() + $bind)->assertForbidden();
        $this->post('/wallet-topups/'.$row->id.'/release', $this->action() + [...$bind, 'expected_account_id' => $this->other->id])->assertStatus(303);
        $this->get('/wallet-topups/'.$row->id.'/receipt')->assertOk()->assertSee('Credited');
        $this->assertTrue(WalletDeskReconciliation::healthy(app(WalletDeskReconciliation::class)->inspect()));
    }

    public function test_a_cashier_cannot_raise_the_policy_threshold_to_bypass_independent_approval(): void
    {
        $this->other->removeRole('Super Admin');
        $this->other->givePermissionTo('wallet-desk-process');
        $this->refuses(fn () => app(WalletPolicy::class)->finance($this->other), 403);
        $this->refuses(fn () => app(WalletDeskTopups::class)->policy($this->other, $this->policy(['revision' => 1, 'threshold_amount' => '100.00'])), 403);
        $row = app(WalletDeskTopups::class)->grant($this->other, $this->grant(['amount' => '30.00']));
        $this->assertTrue($row->requires_second_approval);
        $this->refuses(fn () => app(WalletDeskTopups::class)->finish($this->other, $row->id, 'released', $this->action()), 403);
        app(WalletDeskTopups::class)->finish($this->admin, $row->id, 'released', $this->action());
        $this->assertSame('released', WalletDeskAction::sole()->action);
    }

    public function test_other_school_finance_and_api_context_cannot_read_or_release_this_schools_topup(): void
    {
        $row = app(WalletDeskTopups::class)->grant($this->admin, $this->grant(['amount' => '30.00']));
        SchoolMembership::create(['school_id' => 2, 'user_id' => $this->admin->id, 'role' => 'Super Admin', 'status' => 'active']);
        app(CurrentSchool::class)->run(2, function (): void {
            $actor = $this->admin->fresh();
            $actor->assignRole('Super Admin');
            app(ModuleGate::class)->change('wallet', true, $actor);
        });
        $this->app['auth']->forgetGuards();
        $this->withToken($this->admin->createToken('synthetic-desk-other-school')->plainTextToken)->withHeaders(['X-School-Id' => '2', 'X-Account-Id' => (string) $this->admin->id])->getJson('/api/v2/wallets/'.$row->wallet_account_id.'/desk-topups')->assertNotFound();
        $this->flushHeaders();
        $this->app['auth']->shouldUse('web');
        $this->app['auth']->forgetGuards();
        $this->actingAs($this->admin)->withSession([config('tenancy.session_key') => 2])->get('/wallet-topups/'.$row->id.'/receipt')->assertNotFound();
        $this->post('/wallet-topups/'.$row->id.'/release', $this->action() + ['expected_account_id' => $this->admin->id, 'expected_school_id' => 2])->assertNotFound();
        $this->assertSame(0, WalletDeskAction::count());
    }

    public function test_sql_and_pending_reconciliation_detect_an_unattributed_pending_release_without_repairing_it(): void
    {
        $row = app(WalletDeskTopups::class)->grant($this->admin, $this->grant(['amount' => '30.00']));
        $this->assertTrue(WalletDeskReconciliation::healthy(app(WalletDeskReconciliation::class)->inspect()));
        $wallet = WalletAccount::sole();
        $money = Money::ofCents(3000, 'ZAR');
        app(LedgerService::class)->post('wallet-desk:orphan', 'wallet-desk:orphan', 'Synthetic missing terminal action', [Posting::debit($row->reserve_account_id, $money), Posting::credit($wallet->ledger_account_id, $money)]);
        $report = app(WalletDeskReconciliation::class)->inspect();
        $this->assertSame(1, $report['desk_pending_liability_mismatches']);
        $this->assertSame(1, $report['unattributed_desk_entries']);
        $this->artisan('ledger:reconcile', ['--school' => '1', '--json' => true, '--rebuild' => true])->expectsOutputToContain('"domain_history_valid": false')->assertFailed();
    }

    public function test_sql_prevents_self_approval_and_keeps_reservation_actions_immutable(): void
    {
        $row = app(WalletDeskTopups::class)->grant($this->admin, $this->grant(['amount' => '30.00']));
        $wallet = WalletAccount::sole();
        try {
            DB::transaction(function () use ($row, $wallet): void {
                $money = Money::ofCents(3000, 'ZAR');
                $entry = app(LedgerService::class)->post('synthetic-self-approve', 'synthetic-self-approve', 'Synthetic forbidden self-approval', [Posting::debit($row->reserve_account_id, $money), Posting::credit($wallet->ledger_account_id, $money)]);
                WalletDeskAction::create(['topup_id' => $row->id, 'action' => 'released', 'actor_id' => $this->admin->id, 'ledger_entry_id' => $entry, 'policy_revision' => 1, 'threshold_cents' => 2500, 'requires_second_approval' => true, 'note' => null, 'created_at' => now('UTC')]);
            });
            $this->fail('Expected SQL second-approval guard');
        } catch (QueryException) {
            $this->addToAssertionCount(1);
        }$this->assertSame(0, WalletDeskAction::count());
        $this->assertSame(3000, app(LedgerService::class)->balance($row->reserve_account_id)->cents);
        foreach ([fn () => DB::table('wallet_desk_topups')->update(['owner_user_id' => $this->parent->id]), fn () => DB::table('wallet_desk_topups')->delete()] as $write) {
            try {
                $write();
                $this->fail('Expected immutable desk reservation');
            } catch (QueryException) {
                $this->addToAssertionCount(1);
            }
        }
    }
}
