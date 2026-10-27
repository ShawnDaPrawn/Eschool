<?php

declare(strict_types=1);

namespace Tests\Feature\Money;

use App\Models\Money\BillingOperation;
use App\Models\Money\BillRun;
use App\Models\Money\FeeAccount;
use App\Models\Money\FeeAccountHolder;
use App\Models\Money\FeeAccountLearner;
use App\Models\Money\FeePlan;
use App\Models\Money\Invoice;
use App\Models\Money\InvoiceLine;
use App\Models\People\Enrolment;
use App\Models\Platform\GuardianLink;
use App\Models\Platform\PlatformStaffIdentity;
use App\Models\Platform\School;
use App\Models\Platform\SchoolMembership;
use App\Models\SessionYear;
use App\Models\User;
use App\Services\Money\BillingPreview;
use App\Services\Money\BillRuns;
use App\Services\Money\FeeAccounts;
use App\Services\Money\FeePlans;
use App\Services\Platform\Ledger\AccountType;
use App\Services\Platform\Ledger\LedgerService;
use App\Services\Platform\Ledger\Money;
use App\Services\Platform\Ledger\Posting;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Tests\Concerns\UsesTwoSchools;
use Tests\TestCase;

class FamilyBillingTest extends TestCase
{
    use UsesTwoSchools;

    private User $admin;

    private int $year;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->setDateTime(2026, 6, 15, 12, 0, 0));
        $this->setUpTwoSchools();
        $this->admin = $this->superAdmin();
        $this->year = $this->schoolA->ids['session_year'];
        SessionYear::whereKey($this->year)->update(['start_date' => '2026-01-01', 'end_date' => '2026-12-31']);
        SchoolMembership::where('school_id', 1)->update(['starts_on' => '2026-01-01']);
        Enrolment::where('session_year_id', $this->year)->delete();
        foreach (['asha' => 'a', 'ben' => 'a', 'cara' => 'c'] as $name => $section) {
            Enrolment::create(['learner_id' => $this->schoolA->ids[$name], 'class_section_id' => $this->schoolA->ids['section_'.$section],
                'session_year_id' => $this->year, 'starts_on' => '2026-01-01', 'ends_on' => null, 'legacy_dates' => false, 'reason' => 'Synthetic annual billing']);
        }
        foreach (['asha', 'cara'] as $learner) {
            foreach (['farid', 'mona'] as $holder) {
                GuardianLink::updateOrCreate(['learner_id' => $this->schoolA->ids[$learner], 'guardian_user_id' => $this->schoolA->ids[$holder.'_user']],
                    ['status' => 'active', 'relationship' => 'guardian', 'can_see_fees' => true, 'can_approve' => true]);
            }
        }
        GuardianLink::updateOrCreate(['learner_id' => $this->schoolA->ids['ben'], 'guardian_user_id' => $this->schoolA->ids['gina_user']],
            ['status' => 'active', 'relationship' => 'guardian', 'can_see_fees' => true, 'can_approve' => true]);
        $this->plan();
    }

    public function test_explicit_family_shares_require_membership_and_fee_visible_guardians_without_money_writes(): void
    {
        $input = $this->familyInput();
        $account = app(FeeAccounts::class)->save($this->admin, $input);
        $this->assertSame(1, $account->revision);
        $this->assertTrue($account->active);
        $this->assertSame($account->id, app(FeeAccounts::class)->save($this->admin, $input)->id);
        $this->assertSame(0, DB::table('ledger_entries')->count());
        $this->assertSame(0, Invoice::count());
        $this->refuses(fn () => app(FeeAccounts::class)->save($this->admin, [...$input, 'name' => 'Different family']), 409);
        $bad = $this->familyInput(['holders' => [['user_id' => $this->schoolA->ids['farid_user'], 'share_percent' => '99.99']]]);
        $this->refuses(fn () => app(FeeAccounts::class)->save($this->admin, $bad), 422);
        GuardianLink::where('guardian_user_id', $this->schoolA->ids['mona_user'])->update(['can_see_fees' => false]);
        $this->refuses(fn () => app(FeeAccounts::class)->save($this->admin, $this->familyInput(['revision' => 1]), $account->id), 422);
        $this->refuses(fn () => app(FeeAccounts::class)->save($this->admin, $this->familyInput(['learner_ids' => [$this->schoolB->ids['asha']]])), 404);
        $this->assertSame(1, FeeAccount::count());
    }

    public function test_finance_can_release_the_last_unbilled_learner_and_reassign_without_moving_posted_debt(): void
    {
        $accounts = $this->families();
        $source = $accounts[1];
        $run = $this->prepare();
        app(BillRuns::class)->approve($this->admin, $run->id, $this->operation(1));
        $input = $this->familyInput(['name' => 'Gina family', 'revision' => 1,
            'holders' => [['user_id' => $this->schoolA->ids['gina_user'], 'share_percent' => '100']]]);
        unset($input['learner_ids']); // HTML multi-select omits its name when cleared.
        $bind = ['expected_account_id' => $this->admin->id, 'expected_school_id' => 1];
        $this->actingAs($this->admin)->post('/fee-accounts/'.$source->id, $input + $bind)->assertStatus(303);
        $this->assertSame(0, FeeAccountLearner::where('fee_account_id', $source->id)->count());
        $this->assertSame(2, $source->fresh()->revision);
        $this->assertSame(0, DB::table('ledger_entries')->count());
        $destination = app(FeeAccounts::class)->save($this->admin, $this->familyInput(['name' => 'Corrected payer family',
            'learner_ids' => [$this->schoolA->ids['ben']], 'holders' => [['user_id' => $this->schoolA->ids['gina_user'], 'share_percent' => '100']]]));
        $this->post('/fee-accounts/'.$source->id, $input + $bind)->assertStatus(303);
        $this->assertSame($destination->id, FeeAccountLearner::where('learner_id', $this->schoolA->ids['ben'])->firstOrFail()->fee_account_id);
        $this->refuses(fn () => app(FeeAccounts::class)->save($this->admin, $this->familyInput(['learner_ids' => []])), 422);
        $this->refuses(fn () => app(BillRuns::class)->post($this->admin, $run->id, $this->operation(2)), 409);
        $fresh = $this->prepare();
        app(BillRuns::class)->approve($this->admin, $fresh->id, $this->operation(1));
        app(BillRuns::class)->post($this->admin, $fresh->id, $this->operation(2));
        $invoice = Invoice::where('fee_account_id', $destination->id)->firstOrFail();
        $removePosted = [...$input, 'operation_id' => (string) Str::uuid()];
        $this->post('/fee-accounts/'.$destination->id, $removePosted + $bind)->assertStatus(422)->assertSessionMissing('_old_input');
        $this->assertSame($destination->id, FeeAccountLearner::where('learner_id', $this->schoolA->ids['ben'])->firstOrFail()->fee_account_id);
        $this->assertSame(1001, $invoice->fresh()->total_cents);
        $this->assertSame(2, Invoice::count());
        $this->assertSame([], app(LedgerService::class)->projectionDifferences());
    }

    public function test_annual_run_previews_approves_and_posts_balanced_sealed_family_invoices(): void
    {
        $this->families();
        $run = $this->prepare();
        $this->assertSame(['ZAR' => 3003], $run->snapshot['totals']);
        $this->assertSame([], $run->snapshot['exceptions']);
        $this->assertSame(3, $run->snapshot['billable']);
        $this->assertSame(0, Invoice::count());
        $this->assertSame(0, DB::table('ledger_entries')->count());
        $this->refuses(fn () => app(BillRuns::class)->post($this->admin, $run->id, $this->operation(1)), 409);
        $approved = app(BillRuns::class)->approve($this->admin, $run->id, $this->operation(1));
        $this->assertSame('approved', $approved->state);
        $this->assertSame(0, Invoice::count());
        $posted = app(BillRuns::class)->post($this->admin, $run->id, $this->operation(2));
        $this->assertSame('posted', $posted->state);
        $this->assertSame(3, $posted->revision);
        $this->assertSame(2, Invoice::count());
        $this->assertSame(3, InvoiceLine::count());
        $this->assertSame(3003, (int) Invoice::sum('total_cents'));
        $this->assertSame(2, DB::table('ledger_entries')->count());
        $this->assertSame(4, DB::table('ledger_postings')->count());
        $this->assertSame(3003, (int) DB::table('ledger_accounts')->where('type', 'fee_receivable')->sum('balance_cents'));
        $this->assertSame(3003, (int) DB::table('ledger_accounts')->where('type', 'fee_income')->sum('balance_cents'));
        $this->assertSame(0, DB::table('ledger_accounts')->whereIn('type', ['clearing', 'due_to_school'])->count());
        $this->assertSame([], app(LedgerService::class)->projectionDifferences());
        foreach (Invoice::all() as $invoice) {
            $this->assertNotNull($invoice->sealed_at);
            $this->assertSame($invoice->total_cents, array_sum(array_column($invoice->holders, 'total_cents')));
        }
        foreach (InvoiceLine::all() as $line) {
            $this->assertSame($line->amount_cents, array_sum(array_column($line->holder_shares, 'amount_cents')));
            $this->assertSame(0, $line->vat_cents);
            $this->assertSame('2026-01-01', $line->period_start->toDateString());
        }
    }

    public function test_exceptions_are_collected_and_zero_or_partial_years_cannot_create_guessed_charges(): void
    {
        $run = $this->prepare();
        $this->assertSame(3, $run->snapshot['exceptions']['No active payer account']);
        $this->refuses(fn () => app(BillRuns::class)->approve($this->admin, $run->id, $this->operation(1)), 422);
        $this->families();
        Enrolment::where('learner_id', $this->schoolA->ids['asha'])->update(['starts_on' => '2026-04-01']);
        $partial = $this->prepare();
        $this->assertSame(1, $partial->snapshot['exceptions']['Pro-rata or leaver rule required']);
        $this->refuses(fn () => app(BillRuns::class)->approve($this->admin, $partial->id, $this->operation(1)), 422);
        $this->assertSame(0, Invoice::count());
        $this->assertSame(0, DB::table('ledger_entries')->count());
    }

    public function test_changed_plans_holders_and_guardian_rights_invalidate_preview_or_approval(): void
    {
        $accounts = $this->families();
        $run = $this->prepare();
        $plan = FeePlan::firstOrFail();
        app(FeePlans::class)->save($this->admin, ['operation_id' => (string) Str::uuid(), 'revision' => 1, 'session_year_id' => $this->year,
            'name' => 'Revised tuition', 'amount' => '10.02', 'currency' => 'ZAR', 'scope' => 'all', 'target_id' => null, 'state' => 'active'], $plan->id);
        $this->refuses(fn () => app(BillRuns::class)->approve($this->admin, $run->id, $this->operation(1)), 409);
        $run = $this->prepare();
        app(BillRuns::class)->approve($this->admin, $run->id, $this->operation(1));
        app(FeeAccounts::class)->archive($this->admin, $accounts[0]->id, $this->operation(1));
        $this->refuses(fn () => app(BillRuns::class)->post($this->admin, $run->id, $this->operation(2)), 409);
        app(FeeAccounts::class)->save($this->admin, $this->familyInput(['revision' => 2]), $accounts[0]->id);
        $run = $this->prepare();
        app(BillRuns::class)->approve($this->admin, $run->id, $this->operation(1));
        GuardianLink::where('guardian_user_id', $this->schoolA->ids['farid_user'])->update(['can_see_fees' => false]);
        $this->refuses(fn () => app(BillRuns::class)->post($this->admin, $run->id, $this->operation(2)), 409);
        $this->assertSame(0, Invoice::count());
    }

    public function test_durable_retries_and_competing_runs_never_rebill_a_learner(): void
    {
        $this->families();
        $input = $this->runInput();
        $a = app(BillRuns::class)->prepare($this->admin, $input);
        $this->travel(3)->days();
        $this->assertSame($a->id, app(BillRuns::class)->prepare($this->admin, $input)->id);
        $b = $this->prepare();
        $approve = $this->operation(1);
        app(BillRuns::class)->approve($this->admin, $a->id, $approve);
        $this->assertSame(2, app(BillRuns::class)->approve($this->admin, $a->id, $approve)->revision);
        app(BillRuns::class)->approve($this->admin, $b->id, $this->operation(1));
        $post = $this->operation(2);
        app(BillRuns::class)->post($this->admin, $a->id, $post);
        $this->assertSame(3, app(BillRuns::class)->post($this->admin, $a->id, $post)->revision);
        $this->refuses(fn () => app(BillRuns::class)->post($this->admin, $a->id, [...$post, 'revision' => 3]), 409);
        $this->refuses(fn () => app(BillRuns::class)->post($this->admin, $b->id, $this->operation(2)), 409);
        $later = $this->prepare();
        $this->assertSame(3, $later->snapshot['skipped']);
        $this->assertSame(0, $later->snapshot['billable']);
        $this->refuses(fn () => app(BillRuns::class)->approve($this->admin, $later->id, $this->operation(1)), 422);
        $this->assertSame(3, InvoiceLine::count());
        $this->assertSame(2, DB::table('ledger_entries')->count());
    }

    public function test_sealed_invoices_and_lines_refuse_database_edits_deletes_and_appends(): void
    {
        $this->families();
        $run = $this->prepare();
        app(BillRuns::class)->approve($this->admin, $run->id, $this->operation(1));
        app(BillRuns::class)->post($this->admin, $run->id, $this->operation(2));
        $invoice = Invoice::firstOrFail();
        $line = InvoiceLine::firstOrFail();
        foreach ([fn () => $invoice->update(['total_cents' => 1]), fn () => $invoice->delete(), fn () => $line->update(['amount_cents' => 1]), fn () => $line->delete(),
            fn () => DB::table('invoice_lines')->insert(collect($line->getRawOriginal())->except('id')->all()),
            fn () => BillRun::whereKey($run->id)->update(['fingerprint' => str_repeat('0', 64)]), fn () => BillingOperation::query()->delete()] as $write) {
            try {
                $write();
                $this->fail('Expected immutable billing history');
            } catch (QueryException $e) {
                $this->assertSame('45000', $e->errorInfo[0]);
            }
        }
        $this->assertSame(3003, (int) Invoice::sum('total_cents'));
    }

    public function test_failed_later_invoice_rolls_back_every_invoice_ledger_projection_and_receipt(): void
    {
        $this->families();
        $run = $this->prepare();
        app(BillRuns::class)->approve($this->admin, $run->id, $this->operation(1));
        $original = Invoice::getEventDispatcher();
        Invoice::setEventDispatcher(clone $original);
        $created = 0;
        Invoice::created(function () use (&$created): void {
            if (++$created === 2) {
                throw new RuntimeException('Synthetic second invoice failure');
            }
        });
        $input = $this->operation(2);
        try {
            app(BillRuns::class)->post($this->admin, $run->id, $input);
            $this->fail('Expected synthetic failure');
        } catch (RuntimeException $e) {
            $this->assertSame('Synthetic second invoice failure', $e->getMessage());
        } finally {
            Invoice::setEventDispatcher($original);
        }
        $this->assertSame('approved', $run->fresh()->state);
        $this->assertSame(0, Invoice::count());
        $this->assertSame(0, InvoiceLine::count());
        $this->assertSame(0, DB::table('ledger_entries')->count());
        $this->assertSame(0, DB::table('ledger_postings')->count());
        $this->assertSame(0, DB::table('ledger_accounts')->count());
        $this->assertSame(0, BillingOperation::where('operation_id', $input['operation_id'])->count());
        $this->assertSame(3, app(BillRuns::class)->post($this->admin, $run->id, $input)->revision);
    }

    public function test_wrong_role_wrong_school_revoked_actor_and_read_only_history_are_enforced(): void
    {
        $accounts = $this->families();
        foreach (['tara', 'farid', 'asha'] as $name) {
            $actor = User::findOrFail($this->schoolA->ids[$name.'_user']);
            $this->refuses(fn () => app(FeeAccounts::class)->save($actor, $this->familyInput()), 403);
            $this->refuses(fn () => app(BillRuns::class)->prepare($actor, $this->runInput()), 403);
        }
        SchoolMembership::create(['school_id' => 2, 'user_id' => $this->admin->id, 'role' => 'Super Admin', 'status' => 'active', 'starts_on' => '2026-01-01']);
        app(CurrentSchool::class)->run(2, function () use ($accounts): void {
            $other = $this->admin->fresh();
            $other->assignRole('Super Admin');
            $this->refuses(fn () => app(FeeAccounts::class)->archive($other, $accounts[0]->id, $this->operation(1)), 404);
        });
        $this->admin = $this->admin->fresh();
        $run = $this->prepare();
        app(BillRuns::class)->approve($this->admin, $run->id, $this->operation(1));
        SchoolMembership::where('school_id', 1)->where('user_id', $this->admin->id)->update(['status' => 'suspended']);
        $this->refuses(fn () => app(BillRuns::class)->post($this->admin, $run->id, $this->operation(2)), 403);
        SchoolMembership::where('school_id', 1)->where('user_id', $this->admin->id)->update(['status' => 'active']);
        app(BillRuns::class)->post($this->admin, $run->id, $this->operation(2));
        app(ModuleGate::class)->change('fees', false, $this->admin);
        $this->refuses(fn () => $this->prepare(), 403);
        $this->assertSame('posted', BillRun::findOrFail($run->id)->state);
        $this->assertSame(2, Invoice::count());
    }

    public function test_holder_cents_are_exact_for_unequal_shares_and_single_cent_ties(): void
    {
        $shares = BillingPreview::shares(1, [['user_id' => 3, 'name' => 'C', 'share_bp' => 3334], ['user_id' => 2, 'name' => 'B', 'share_bp' => 3333], ['user_id' => 1, 'name' => 'A', 'share_bp' => 3333]]);
        $this->assertSame([0, 0, 1], array_column($shares, 'amount_cents'));
        $shares = BillingPreview::shares(1001, [['user_id' => 2, 'name' => 'B', 'share_bp' => 5000], ['user_id' => 1, 'name' => 'A', 'share_bp' => 5000]]);
        $this->assertSame([501, 500], array_column($shares, 'amount_cents'));
    }

    public function test_panel_account_preview_approval_confirmation_and_invoice_history_remain_private(): void
    {
        $this->actingAs($this->admin)->get('/fee-accounts')->assertOk()->assertHeader('Cache-Control', 'no-store, private');
        $this->get('/fee-accounts/new')->assertOk()->assertSee('Save active family account');
        $bind = ['expected_account_id' => $this->admin->id, 'expected_school_id' => 1];
        $this->post('/fee-accounts/new', $this->familyInput() + $bind)->assertStatus(303);
        $account = FeeAccount::firstOrFail();
        $this->get('/fee-accounts/'.$account->id)->assertOk()->assertSee('Synthetic split family');
        $this->get('/fee-accounts/'.$account->id.'/edit')->assertOk()->assertSee('Holder share percentage 1');
        app(FeeAccounts::class)->save($this->admin, $this->familyInput(['name' => 'Gina family', 'learner_ids' => [$this->schoolA->ids['ben']],
            'holders' => [['user_id' => $this->schoolA->ids['gina_user'], 'share_percent' => '100']]]));
        $this->get('/fee-bill-runs')->assertOk()->assertSee('New bill preview');
        $this->get('/fee-bill-runs/new')->assertOk()->assertSee('tax_treatment');
        $this->post('/fee-bill-runs/new', $this->runInput() + $bind)->assertStatus(303);
        $run = BillRun::firstOrFail();
        $this->get('/fee-bill-runs/'.$run->id)->assertOk()->assertSee('Approve bill preview');
        $this->post('/fee-bill-runs/'.$run->id.'/approve', $this->operation(1) + $bind)->assertStatus(303);
        $this->get('/fee-bill-runs/'.$run->id)->assertOk()->assertSee('Post approved invoices');
        $missing = $this->operation(2) + $bind;
        unset($missing['confirmed']);
        $this->post('/fee-bill-runs/'.$run->id.'/post', $missing)->assertStatus(422)->assertSessionMissing('_old_input')->assertHeader('Cache-Control', 'no-store, private');
        $this->assertSame(0, Invoice::count());
        $this->post('/fee-bill-runs/'.$run->id.'/post', $this->operation(2) + $bind)->assertStatus(303);
        $invoice = Invoice::where('fee_account_id', $account->id)->firstOrFail();
        $this->get('/fee-invoices/'.$invoice->id)->assertOk()->assertSee('Recorded holder shares')->assertSee('ZAR 20.02')->assertHeader('Cache-Control', 'no-store, private');
        $this->get('/fee-accounts/'.$account->id)->assertOk()->assertSee('ZAR 20.02');
        $this->get('/fee-bill-runs/'.$run->id.'?exceptions_only=1')->assertOk()->assertSee('No learners match this filter.');
        app(ModuleGate::class)->change('fees', false, $this->admin);
        $this->get('/fee-invoices/'.$invoice->id)->assertOk()->assertSee('ZAR 20.02');
        $this->get('/fee-accounts/'.$account->id)->assertOk()->assertSee('read-only')->assertDontSee('Archive family account');
        $this->get('/fee-bill-runs/'.$run->id)->assertOk()->assertSee('read-only');
    }

    public function test_browser_binding_and_validation_do_not_flash_private_financial_inputs(): void
    {
        $this->actingAs($this->admin);
        $bind = ['expected_account_id' => $this->admin->id, 'expected_school_id' => 1];
        $this->post('/fee-accounts/new', $this->familyInput() + [...$bind, 'expected_school_id' => 2])->assertForbidden();
        $this->post('/fee-accounts/new', $this->familyInput() + [...$bind, 'expected_account_id' => $this->admin->id + 999])->assertForbidden();
        $this->post('/fee-accounts/new', $this->familyInput(['name' => str_repeat('Private family ', 30)]) + $bind)->assertStatus(422)->assertSessionMissing('_old_input');
        $this->post('/fee-bill-runs/new?post=1', $this->runInput() + $bind)->assertStatus(422)->assertSessionMissing('_old_input');
        $missingTax = $this->runInput();
        unset($missingTax['tax_treatment']);
        $this->post('/fee-bill-runs/new', $missingTax + $bind)->assertStatus(422)->assertSessionMissing('_old_input');
        $this->assertSame(0, FeeAccount::count());
        $this->assertSame(0, BillRun::count());
        $this->assertSame(0, DB::table('ledger_entries')->count());
    }

    public function test_planning_account_management_and_billing_permissions_do_not_imply_one_another(): void
    {
        $planner = User::findOrFail($this->schoolA->ids['tara_user']);
        $planner->givePermissionTo('fee-plans-manage');
        $this->refuses(fn () => app(BillRuns::class)->prepare($planner, $this->runInput()), 403);
        $this->refuses(fn () => app(FeeAccounts::class)->save($planner, $this->familyInput()), 403);
        $planner->givePermissionTo('fee-billing-manage');
        $this->assertSame('preview', app(BillRuns::class)->prepare($planner, $this->runInput())->state);
        $manager = User::findOrFail($this->schoolA->ids['theo_user']);
        $manager->givePermissionTo('fee-accounts-manage');
        $this->assertSame(1, app(FeeAccounts::class)->save($manager, $this->familyInput())->revision);
        $this->refuses(fn () => app(BillRuns::class)->prepare($manager, $this->runInput()), 403);
        $this->actingAs($planner)->get('/fee-accounts')->assertForbidden();
        $this->actingAs($manager)->get('/fee-bill-runs')->assertForbidden();
    }

    public function test_future_year_preparation_is_allowed_but_future_invoice_posting_is_refused(): void
    {
        $future = SessionYear::create(['name' => 'Future tuition', 'start_date' => '2027-01-01', 'end_date' => '2027-12-31']);
        foreach (['asha' => 'a', 'ben' => 'a', 'cara' => 'c'] as $name => $section) {
            Enrolment::create(['learner_id' => $this->schoolA->ids[$name], 'class_section_id' => $this->schoolA->ids['section_'.$section], 'session_year_id' => $future->id,
                'starts_on' => '2027-01-01', 'ends_on' => null, 'legacy_dates' => false, 'reason' => 'Synthetic future billing']);
        }
        app(FeePlans::class)->save($this->admin, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'name' => 'Future annual tuition', 'session_year_id' => $future->id,
            'scope' => 'all', 'target_id' => null, 'currency' => 'ZAR', 'amount' => '10.01', 'state' => 'active']);
        app(FeeAccounts::class)->save($this->admin, $this->familyInput(['session_year_id' => $future->id]));
        app(FeeAccounts::class)->save($this->admin, $this->familyInput(['session_year_id' => $future->id, 'name' => 'Future Gina family', 'learner_ids' => [$this->schoolA->ids['ben']],
            'holders' => [['user_id' => $this->schoolA->ids['gina_user'], 'share_percent' => '100']]]));
        $run = $this->prepare(['session_year_id' => $future->id, 'as_of' => '2027-06-15', 'due_on' => '2027-07-01']);
        $this->assertSame(3, $run->snapshot['billable']);
        app(BillRuns::class)->approve($this->admin, $run->id, $this->operation(1));
        $this->refuses(fn () => app(BillRuns::class)->post($this->admin, $run->id, $this->operation(2)), 422);
        $this->actingAs($this->admin)->get('/fee-accounts/new?year_id='.$future->id)->assertOk()->assertSee('Future tuition');
        $this->assertSame(0, Invoice::count());
    }

    public function test_zero_rates_are_not_posted_and_mixed_account_currencies_are_exceptions(): void
    {
        $this->families();
        $plan = FeePlan::firstOrFail();
        $data = ['operation_id' => (string) Str::uuid(), 'revision' => 1, 'name' => 'Free annual tuition', 'session_year_id' => $this->year,
            'scope' => 'all', 'target_id' => null, 'currency' => 'ZAR', 'amount' => '0.00', 'state' => 'active'];
        app(FeePlans::class)->save($this->admin, $data, $plan->id);
        $zero = $this->prepare();
        $this->assertSame(3, $zero->snapshot['skipped']);
        $this->assertSame(0, $zero->snapshot['billable']);
        $this->refuses(fn () => app(BillRuns::class)->approve($this->admin, $zero->id, $this->operation(1)), 422);
        app(FeePlans::class)->save($this->admin, [...$data, 'operation_id' => (string) Str::uuid(), 'revision' => 2, 'amount' => '10.01', 'currency' => 'USD'], $plan->id);
        $mixed = $this->prepare();
        $this->assertSame(3, $mixed->snapshot['exceptions']['Plan and family account currencies differ']);
        $this->assertSame([], $mixed->snapshot['totals']);
        $this->assertSame(0, DB::table('ledger_entries')->count());
    }

    public function test_global_support_identity_and_password_reset_refuse_billing_receipt_recovery(): void
    {
        $this->families();
        $input = $this->runInput();
        $run = app(BillRuns::class)->prepare($this->admin, $input);
        $this->admin->forceFill(['password_change_required' => true])->save();
        $this->refuses(fn () => app(BillRuns::class)->prepare($this->admin, $input), 403);
        $this->admin->forceFill(['password_change_required' => false])->save();
        $identity = PlatformStaffIdentity::create(['user_id' => $this->admin->id, 'active' => true]);
        $this->refuses(fn () => app(BillRuns::class)->prepare($this->admin, $input), 403);
        $identity->update(['active' => false]);
        $this->assertSame($run->id, app(BillRuns::class)->prepare($this->admin, $input)->id);
    }

    public function test_database_rejects_a_family_link_to_another_schools_account(): void
    {
        $other = app(CurrentSchool::class)->run(2, fn () => FeeAccount::create(['reference' => 'NEF-FOREIGN', 'name' => 'Baobab private family', 'currency' => 'ZAR', 'active' => true, 'revision' => 1]));
        try {
            FeeAccountLearner::create(['fee_account_id' => $other->id, 'session_year_id' => $this->year, 'learner_id' => $this->schoolA->ids['asha']]);
            $this->fail('Expected tenant foreign key refusal');
        } catch (QueryException $e) {
            $this->assertSame('23000', $e->errorInfo[0]);
        }
        $this->actingAs($this->admin)->get('/fee-accounts/'.$other->id)->assertNotFound()->assertDontSee('Baobab private family');
    }

    public function test_school_calendar_dates_and_money_instants_do_not_shift_with_local_timezone(): void
    {
        DB::table('schools')->where('id', 1)->update(['timezone' => 'Africa/Johannesburg']);
        app(CurrentSchool::class)->set(School::findOrFail(1));
        config(['app.timezone' => 'Africa/Johannesburg']);
        $this->travelTo(CarbonImmutable::parse('2026-06-15T23:30:00Z'));
        $this->families();
        $run = $this->prepare();
        app(BillRuns::class)->approve($this->admin, $run->id, $this->operation(1));
        $posted = app(BillRuns::class)->post($this->admin, $run->id, $this->operation(2));
        $this->assertSame('2026-06-15 23:30:00', $posted->posted_at->utc()->format('Y-m-d H:i:s'));
        $this->assertSame('2026-06-15 23:30:00', $posted->approved_at->utc()->format('Y-m-d H:i:s'));
        $invoice = Invoice::firstOrFail();
        $this->assertSame('2026-06-16', $invoice->issued_on->toDateString());
        $this->assertSame('2026-06-15 23:30:00', $invoice->sealed_at->utc()->format('Y-m-d H:i:s'));
    }

    public function test_unsealed_invoice_cannot_finalize_or_make_a_run_posted_and_reconciliation_reports_it(): void
    {
        $accounts = $this->families();
        $run = $this->prepare();
        $account = $accounts[0];
        $ledger = app(LedgerService::class);
        $receivable = $ledger->openAccount(AccountType::FeeReceivable, 'raw-test:receivable', 'ZAR');
        $income = $ledger->openAccount(AccountType::FeeIncome, 'raw-test:income', 'ZAR');
        $account->update(['ledger_account_id' => $receivable]);
        $money = Money::ofCents(1001, 'ZAR');
        $entry = $ledger->post('raw-test:invoice', 'raw-test:invoice', 'Synthetic raw invariant probe', [Posting::debit($receivable, $money), Posting::credit($income, $money)]);
        $invoice = Invoice::create(['fee_account_id' => $account->id, 'bill_run_id' => $run->id, 'session_year_id' => $this->year, 'ledger_entry_id' => $entry,
            'number' => 'RAW-INVARIANT', 'account_name' => $account->name, 'currency' => 'ZAR', 'total_cents' => 1001, 'holders' => [], 'issued_on' => '2026-06-15', 'due_on' => '2026-07-01', 'created_by' => $this->admin->id, 'created_at' => now('UTC')]);
        try {
            $invoice->update(['sealed_at' => now('UTC')]);
            $this->fail('Expected finalization to require invoice lines');
        } catch (QueryException $e) {
            $this->assertSame('45000', $e->errorInfo[0]);
        }
        $run->update(['state' => 'approved', 'revision' => 2, 'approved_by' => $this->admin->id, 'approved_at' => now('UTC')]);
        try {
            $run->update(['state' => 'posted', 'revision' => 3, 'posted_at' => now('UTC')]);
            $this->fail('Expected posted run to require every sealed invoice');
        } catch (QueryException $e) {
            $this->assertSame('45000', $e->errorInfo[0]);
        }
        $this->artisan('ledger:reconcile', ['--school' => 1, '--json' => true])->expectsOutputToContain('"incomplete_invoices": 1')->assertFailed();
    }

    public function test_real_1200_learner_600_family_bill_run_posts_exactly_once(): void
    {
        $user = (array) DB::table('users')->where('id', $this->schoolA->ids['asha_user'])->first();
        $student = (array) DB::table('students')->where('id', $this->schoolA->ids['asha'])->first();
        unset($user['id'], $student['id']);
        $learners = [$this->schoolA->ids['asha'], $this->schoolA->ids['ben'], $this->schoolA->ids['cara']];
        for ($i = 0; $i < 1197; $i++) {
            $person = DB::table('users')->insertGetId(array_replace($user, ['email' => 'bill-scale-'.$i.'@synthetic.example.test']));
            $learner = DB::table('students')->insertGetId(array_replace($student, ['user_id' => $person]));
            DB::table('enrolments')->insert(['school_id' => 1, 'learner_id' => $learner, 'class_section_id' => $student['class_section_id'], 'session_year_id' => $this->year,
                'starts_on' => '2026-01-01', 'ends_on' => null, 'legacy_dates' => false, 'reason' => 'Synthetic full bill-run scale']);
            $learners[] = $learner;
        }
        foreach (array_chunk($learners, 2) as $n => $family) {
            $account = FeeAccount::create(['name' => 'Synthetic scale family '.$n, 'reference' => 'SCALE-'.$n, 'currency' => 'ZAR', 'active' => true, 'revision' => 1]);
            FeeAccountHolder::create(['fee_account_id' => $account->id, 'session_year_id' => $this->year, 'user_id' => $this->schoolA->ids['farid_user'], 'share_bp' => 10000]);
            foreach ($family as $learner) {
                GuardianLink::updateOrCreate(['learner_id' => $learner, 'guardian_user_id' => $this->schoolA->ids['farid_user']], ['status' => 'active', 'relationship' => 'guardian', 'can_see_fees' => true, 'can_approve' => true]);
                FeeAccountLearner::create(['fee_account_id' => $account->id, 'session_year_id' => $this->year, 'learner_id' => $learner]);
            }
        }
        $queries = 0;
        $measuring = true;
        DB::listen(function () use (&$queries, &$measuring): void {
            if ($measuring) {
                $queries++;
            }
        });
        $started = microtime(true);
        try {
            $run = $this->prepare();
            $previewQueries = $queries;
            $this->assertSame(1200, $run->snapshot['billable']);
            $this->assertSame(600, $run->snapshot['account_count']);
            $this->assertSame(['ZAR' => 1201200], $run->snapshot['totals']);
            $this->assertSame([], $run->snapshot['exceptions']);
            $this->assertLessThan(150, $previewQueries, 'A bill preview must batch families and guardians, not query per learner.');
            app(BillRuns::class)->approve($this->admin, $run->id, $this->operation(1));
            $post = $this->operation(2);
            app(BillRuns::class)->post($this->admin, $run->id, $post);
            app(BillRuns::class)->post($this->admin, $run->id, $post);
        } finally {
            $measuring = false;
        }
        $elapsed = microtime(true) - $started;
        $this->assertSame(600, Invoice::count());
        $this->assertSame(1200, InvoiceLine::count());
        $this->assertSame(600, DB::table('ledger_entries')->count());
        $this->assertSame(1200, DB::table('ledger_postings')->count());
        $this->assertSame(1201200, (int) Invoice::sum('total_cents'));
        $this->assertSame(1201200, (int) DB::table('ledger_accounts')->where('type', 'fee_receivable')->sum('balance_cents'));
        $this->assertSame(1201200, (int) DB::table('ledger_accounts')->where('type', 'fee_income')->sum('balance_cents'));
        $this->assertSame([], app(LedgerService::class)->projectionDifferences());
        $this->assertLessThan(900, $elapsed, 'Server work alone must not consume the complete 15-minute bill-run task.');
        file_put_contents(storage_path('logs/family-bill-run-scale.json'), json_encode(['learners' => 1200, 'families' => 600, 'preview_queries' => $previewQueries, 'total_queries' => $queries, 'seconds' => $elapsed], JSON_THROW_ON_ERROR));
    }

    private function families(): array
    {
        return [app(FeeAccounts::class)->save($this->admin, $this->familyInput()), app(FeeAccounts::class)->save($this->admin, $this->familyInput([
            'name' => 'Gina family', 'learner_ids' => [$this->schoolA->ids['ben']], 'holders' => [['user_id' => $this->schoolA->ids['gina_user'], 'share_percent' => '100']]]))];
    }

    private function familyInput(array $changes = []): array
    {
        return array_replace(['operation_id' => (string) Str::uuid(), 'revision' => 0, 'name' => 'Synthetic split family', 'currency' => 'ZAR', 'session_year_id' => $this->year,
            'learner_ids' => [$this->schoolA->ids['asha'], $this->schoolA->ids['cara']], 'holders' => [['user_id' => $this->schoolA->ids['farid_user'], 'share_percent' => '50'], ['user_id' => $this->schoolA->ids['mona_user'], 'share_percent' => '50']]], $changes);
    }

    private function runInput(array $changes = []): array
    {
        return array_replace(['operation_id' => (string) Str::uuid(), 'session_year_id' => $this->year, 'as_of' => '2026-06-15', 'due_on' => '2026-07-01', 'tax_treatment' => 'no_vat'], $changes);
    }

    private function prepare(array $changes = []): BillRun
    {
        return app(BillRuns::class)->prepare($this->admin, $this->runInput($changes));
    }

    private function operation(int $revision): array
    {
        return ['operation_id' => (string) Str::uuid(), 'revision' => $revision, 'confirmed' => true];
    }

    private function plan(): void
    {
        app(FeePlans::class)->save($this->admin, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'name' => 'Synthetic annual tuition', 'session_year_id' => $this->year,
            'scope' => 'all', 'target_id' => null, 'currency' => 'ZAR', 'amount' => '10.01', 'state' => 'active']);
    }

    private function refuses(callable $call, int $status): void
    {
        try {
            $call();
            $this->fail('Expected refusal');
        } catch (ValidationException $e) {
            $this->assertSame(422, $status);
        } catch (ModelNotFoundException $e) {
            $this->assertSame(404, $status);
        } catch (HttpExceptionInterface $e) {
            $this->assertSame($status, $e->getStatusCode());
        }
    }
}
