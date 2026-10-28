<?php

declare(strict_types=1);

namespace Tests\Feature\Money;

use App\Models\Money\BillRun;
use App\Models\Money\FeeAccount;
use App\Models\Money\FeeCreditNote;
use App\Models\Money\FeeLeaverPolicy;
use App\Models\Money\FeeLeaverRun;
use App\Models\Money\FeePlan;
use App\Models\Money\Invoice;
use App\Models\Money\InvoiceLine;
use App\Models\People\Enrolment;
use App\Models\Platform\GuardianLink;
use App\Models\Platform\SchoolMembership;
use App\Models\Platform\SchoolModule;
use App\Models\SessionYear;
use App\Models\User;
use App\Services\Money\BillRuns;
use App\Services\Money\FeeAccounts;
use App\Services\Money\FeeAllocations;
use App\Services\Money\FeeCreditNotes;
use App\Services\Money\FeeDiscountRuns;
use App\Services\Money\FeeDiscounts;
use App\Services\Money\FeeLeaverPolicies;
use App\Services\Money\FeeLeaverRuns;
use App\Services\Money\FeeLifecyclePolicies;
use App\Services\Money\FeePaymentReconciliation;
use App\Services\Money\FeePayments;
use App\Services\Money\FeePaymentSettings;
use App\Services\Money\FeePlans;
use App\Services\Money\FeeStatements;
use App\Services\Money\ParentFeeStatements;
use App\Services\Platform\Ledger\LedgerService;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Tests\Concerns\UsesTwoSchools;
use Tests\TestCase;

class FeeLeaverCreditsTest extends TestCase
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

    public function test_original_contract_cents_payer_credits_and_no_duplicate_departure_refund(): void
    {
        $invoice = $this->billed();
        $this->policy();
        $this->depart('2026-05-31');
        $run = $this->review($invoice);
        $this->assertSame(587, $run->amount_cents);
        $this->assertSame(414, $run->snapshot['served_cents']);
        $posted = $this->apply($run);
        $this->assertSame(2, FeeCreditNote::count());
        $this->assertSame(587, (int) FeeCreditNote::sum('amount_cents'));
        $this->assertSame(414, array_sum(array_column(array_filter(app(FeeStatements::class)->finance(FeeAccount::findOrFail($invoice->fee_account_id))['charges'], fn ($row) => $row['learner_id'] === $this->schoolA->ids['asha']), 'charged_cents')));
        $this->refuses(fn () => $this->review($invoice), 422);
        $this->assertSame('posted', $posted->state);
        $this->healthy();
    }

    public function test_frozen_policy_and_rate_survive_later_changes_and_class_moves_are_not_departures(): void
    {
        $this->families();
        app(FeeLifecyclePolicies::class)->save($this->admin, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'session_year_id' => $this->year, 'join_basis' => 'daily', 'leaver_basis' => 'daily', 'installments' => 'preserve_dates']);
        $invoice = $this->billed(false);
        $this->policy(['prior_policyless_basis' => 'no_credit']);
        $this->depart('2026-05-31');
        $plan = FeePlan::firstOrFail();
        $plan->forceFill(['amount_cents' => 999999, 'revision' => 2])->save();
        $run = $this->review($invoice);
        $this->assertSame(587, $run->amount_cents);
        $this->assertSame('original_bill_policy', $run->snapshot['policy_origin']);
        Enrolment::create(['learner_id' => $this->schoolA->ids['asha'], 'session_year_id' => $this->year, 'class_section_id' => $this->schoolA->ids['section_c'], 'starts_on' => '2026-06-01', 'ends_on' => null, 'legacy_dates' => false, 'reason' => 'Synthetic current class move']);
        $this->refuses(fn () => app(FeeLeaverRuns::class)->approve($this->admin, $run->id, $this->operation(1)), 422);
        $this->assertSame(0, FeeCreditNote::count());
    }

    public function test_calendar_edits_do_not_change_original_contract_or_pending_departure_approval(): void
    {
        $this->families();
        app(FeeLifecyclePolicies::class)->save($this->admin, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'session_year_id' => $this->year, 'join_basis' => 'daily', 'leaver_basis' => 'daily', 'installments' => 'preserve_dates']);
        $invoice = $this->billed(false);
        $this->policy();
        $this->depart('2026-05-31');
        $run = $this->review($invoice);
        SessionYear::whereKey($this->year)->update(['start_date' => '2025-12-01', 'end_date' => '2027-01-31']);
        $after = $this->review($invoice);
        $this->assertSame(587, $after->amount_cents);
        $this->assertSame(414, $after->snapshot['served_cents']);
        $this->assertSame('2026-01-01', $after->snapshot['quote']['proration']['year_start']);
        $this->assertSame('2026-12-31', $after->snapshot['quote']['proration']['year_end']);
        $this->apply($run);
        $this->healthy();
    }

    public function test_older_policyless_bill_uses_original_year_for_missing_joining_date_and_proration(): void
    {
        $dispatcher = BillRun::getEventDispatcher();
        BillRun::setEventDispatcher(clone $dispatcher);
        BillRun::creating(function (BillRun $run): void {
            $snapshot = $run->snapshot;
            foreach ($snapshot['rows'] as &$row) {
                unset($row['enrolment_start'],$row['enrolment_end'],$row['enrolment_id'],$row['enrolment_segments']);
            }unset($row);
            $run->snapshot = $snapshot;
        });
        try {
            $invoice = $this->billed();
        } finally {
            BillRun::setEventDispatcher($dispatcher);
        }
        $this->policy();
        $this->depart('2026-05-31');
        SessionYear::whereKey($this->year)->update(['start_date' => '2025-12-01', 'end_date' => '2027-01-31']);
        $run = $this->review($invoice);
        $this->assertSame('explicit_prior_policyless_rule', $run->snapshot['policy_origin']);
        $this->assertSame(587, $run->amount_cents);
        $this->assertSame(414, $run->snapshot['served_cents']);
        $this->apply($run);
        $this->healthy();
    }

    public function test_later_earlier_departure_credits_only_remaining_cumulative_difference(): void
    {
        $invoice = $this->billed();
        $this->policy();
        $this->depart('2026-05-31');
        $this->apply($this->review($invoice));
        $this->depart('2026-04-30');
        $next = $this->review($invoice);
        $this->assertSame(85, $next->amount_cents);
        $this->apply($next);
        $this->assertSame(672, (int) FeeCreditNote::sum('amount_cents'));
        $this->refuses(fn () => $this->review($invoice), 422);
        $this->healthy();
    }

    public function test_own_previous_reductions_are_retained_without_transferring_benefits_between_payers(): void
    {
        $invoice = $this->billed();
        $this->policy();
        $line = InvoiceLine::where('invoice_id', $invoice->id)->where('learner_id', $this->schoolA->ids['asha'])->firstOrFail();
        $input = ['operation_id' => (string) Str::uuid(), 'invoice_line_id' => $line->id, 'holder_user_id' => $this->schoolA->ids['farid_user'], 'amount' => '1.00', 'reason_code' => 'fee_reduction', 'reason' => 'Synthetic original own bursary'];
        $p = app(FeeCreditNotes::class)->preview($this->admin, $input);
        app(FeeCreditNotes::class)->post($this->admin, $input + ['fingerprint' => $p['fingerprint'], 'confirmed' => true]);
        $this->depart('2026-05-31');
        $run = $this->review($invoice);
        $this->assertSame(587, $run->amount_cents);
        $holders = collect($run->snapshot['holders'])->keyBy('holder_user_id');
        $this->assertSame(107, $holders[$this->schoolA->ids['farid_user']]['target_cents']);
        $this->assertSame(207, $holders[$this->schoolA->ids['mona_user']]['target_cents']);
        $this->apply($run);
        $this->healthy();
    }

    public function test_explicit_proportional_reductions_do_not_cancel_prior_leaver_credits_or_other_corrections(): void
    {
        $invoice = $this->billed();
        $this->policy(['prior_reductions' => 'proportion_reductions']);
        $line = InvoiceLine::where('invoice_id', $invoice->id)->where('learner_id', $this->schoolA->ids['asha'])->firstOrFail();
        $input = ['operation_id' => (string) Str::uuid(), 'invoice_line_id' => $line->id, 'holder_user_id' => $this->schoolA->ids['farid_user'], 'amount' => '1.00', 'reason_code' => 'fee_reduction', 'reason' => 'Synthetic original reduction'];
        $p = app(FeeCreditNotes::class)->preview($this->admin, $input);
        app(FeeCreditNotes::class)->post($this->admin, $input + ['fingerprint' => $p['fingerprint'], 'confirmed' => true]);
        $other = array_replace($input, ['operation_id' => (string) Str::uuid(), 'holder_user_id' => $this->schoolA->ids['mona_user'], 'amount' => '0.10', 'reason_code' => 'billing_error', 'reason' => 'Synthetic original billing correction']);
        $p = app(FeeCreditNotes::class)->preview($this->admin, $other);
        app(FeeCreditNotes::class)->post($this->admin, $other + ['fingerprint' => $p['fingerprint'], 'confirmed' => true]);
        $this->depart('2026-05-31');
        $run = $this->review($invoice);
        $this->assertSame(528, $run->amount_cents);
        $holders = collect($run->snapshot['holders'])->keyBy('holder_user_id');
        $this->assertSame(197, $holders[$this->schoolA->ids['mona_user']]['target_cents']);
        $this->assertSame(10, $holders[$this->schoolA->ids['mona_user']]['other_credits_before_cents']);

        $this->apply($run);
        $this->refuses(fn () => $this->review($invoice), 422);
        $this->healthy();
    }

    public function test_wrong_role_foreign_invoice_learner_stale_policy_and_late_rollback(): void
    {
        $invoice = $this->billed();
        $this->policy();
        $this->depart('2026-05-31');
        $input = $this->input($invoice);
        $this->refuses(fn () => app(FeeLeaverRuns::class)->prepare(User::findOrFail($this->schoolA->ids['farid_user']), $input), 403);
        $this->refuses(fn () => app(FeeLeaverRuns::class)->prepare($this->admin, array_replace($input, ['learner_id' => $this->schoolB->ids['asha']])), 404);
        $run = $this->review($invoice);
        $this->policy(['revision' => 1, 'prior_reductions' => 'proportion_reductions']);
        $this->refuses(fn () => app(FeeLeaverRuns::class)->approve($this->admin, $run->id, $this->operation(1)), 409);
        $run = $this->review($invoice);
        $run = app(FeeLeaverRuns::class)->approve($this->admin, $run->id, $this->operation(1));
        FeeLeaverRun::updating(function (FeeLeaverRun $row): void {
            if ($row->state === 'posted') {
                throw new RuntimeException('Synthetic leaver seal failure');
            }
        });
        try {
            app(FeeLeaverRuns::class)->post($this->admin, $run->id, $this->operation(2));
            $this->fail('Expected synthetic failure');
        } catch (RuntimeException $error) {
            $this->assertSame('Synthetic leaver seal failure', $error->getMessage());
        } finally {
            FeeLeaverRun::flushEventListeners();
        }
        $this->assertSame(0, FeeCreditNote::count());
        $this->assertSame('approved', $run->fresh()->state);
        $this->applyPrepared($run);
        $this->healthy();
    }

    public function test_paid_leaver_credit_restores_the_original_pinned_receipt_without_a_cash_refund(): void
    {
        $invoice = $this->billed();
        $this->policy();
        app(FeePaymentSettings::class)->save($this->admin, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'allocation_order' => 'oldest_first']);
        $payment = app(FeePayments::class)->record($this->admin, ['operation_id' => (string) Str::uuid(), 'fee_account_id' => $invoice->fee_account_id, 'holder_user_id' => $this->schoolA->ids['farid_user'], 'pinned_learner_id' => $this->schoolA->ids['asha'], 'amount' => '5.01', 'method' => 'cash', 'received_on' => '2026-06-15', 'source_reference' => 'SYNTHETIC-LEAVER-PAID', 'confirmed' => true]);
        $preview = app(FeeAllocations::class)->preview($this->admin, $payment->id);
        app(FeeAllocations::class)->post($this->admin, $payment->id, ['operation_id' => (string) Str::uuid(), 'fingerprint' => $preview['fingerprint'], 'confirmed' => true]);
        $this->depart('2026-05-31');
        $run = $this->apply($this->review($invoice));
        $this->assertSame(294, (int) FeeCreditNote::sum('released_cents'));
        $receipt = app(FeeStatements::class)->receipts(FeeAccount::findOrFail($invoice->fee_account_id))[0];
        $this->assertSame(294, $receipt['credit_cents']);
        $this->assertSame($this->schoolA->ids['asha'], $receipt['pinned_learner_id']);
        $this->assertSame(501, (int) DB::table('ledger_accounts')->where('type', 'cash_desk')->sum('balance_cents'));
        $this->healthy();
    }

    public function test_explicit_independent_approval_rollback_retention_and_private_panel_bindings(): void
    {
        $invoice = $this->billed();
        $this->policy(['independent_approval' => true]);
        $this->depart('2026-05-31');
        $run = $this->review($invoice);
        $this->refuses(fn () => app(FeeLeaverRuns::class)->approve($this->admin, $run->id, $this->operation(1)), 403);
        $second = User::findOrFail($this->schoolA->ids['tara_user']);
        $second->givePermissionTo('fee-credits-manage');
        $run = app(FeeLeaverRuns::class)->approve($second, $run->id, $this->operation(1));
        $post = $this->operation(2);
        $posted = app(FeeLeaverRuns::class)->post($this->admin, $run->id, $post);
        $this->assertSame($posted->id, app(FeeLeaverRuns::class)->post($this->admin, $run->id, $post)->id);
        $this->actingAs($this->admin)->get('/fee-leavers/'.$run->id)->assertOk()->assertHeader('Cache-Control', 'no-store, private')->assertSee('PRIVATE-SYNTHETIC-DEPARTURE-EVIDENCE');
        $this->post('/fee-leavers/policy', ['expected_school_id' => 2, 'expected_account_id' => $this->admin->id])->assertForbidden();
        $this->post('/fee-leavers/policy', ['expected_school_id' => 1, 'expected_account_id' => $this->admin->id, 'operation_id' => (string) Str::uuid(), 'revision' => 1, 'prior_policyless_basis' => 'PRIVATE-INVALID'])->assertStatus(422)->assertSessionMissing('_old_input');
        try {
            DB::table('fee_leaver_runs')->where('id', $run->id)->update(['amount_cents' => 1]);
            $this->fail('Retain sealed leaver credits');
        } catch (QueryException) {
            $this->assertSame(587, $run->fresh()->amount_cents);
        }
        app(ModuleGate::class)->change('fees', false, $this->admin);
        $this->get('/fee-leavers/'.$run->id)->assertOk();
        $this->refuses(fn () => app(FeeLeaverRuns::class)->post($this->admin, $run->id, $post), 403);
        $this->healthy();
    }

    public function test_a_real_foreign_invoice_and_review_are_inaccessible_without_financial_side_effects(): void
    {
        $foreign = app(CurrentSchool::class)->run(2, function (): array {
            $actor = User::findOrFail($this->schoolB->ids['tara_user']);
            $actor->givePermissionTo(['fee-plans-manage', 'fee-accounts-manage', 'fee-billing-manage', 'fee-credits-manage']);
            SchoolMembership::where('user_id', $actor->id)->update(['starts_on' => '2026-01-01']);
            SchoolModule::updateOrCreate(['module' => 'fees'], ['enabled' => true, 'in_package' => true, 'read_only' => false]);
            app(ModuleGate::class)->forget();
            $year = $this->schoolB->ids['session_year'];
            SessionYear::whereKey($year)->update(['default' => 1, 'start_date' => '2026-01-01', 'end_date' => '2026-12-31']);
            Enrolment::where('session_year_id', $year)->delete();
            Enrolment::create(['session_year_id' => $year, 'learner_id' => $this->schoolB->ids['asha'], 'class_section_id' => $this->schoolB->ids['section_a'], 'starts_on' => '2026-01-01', 'ends_on' => null, 'legacy_dates' => false, 'reason' => 'Synthetic foreign tuition']);
            GuardianLink::updateOrCreate(['learner_id' => $this->schoolB->ids['asha'], 'guardian_user_id' => $this->schoolB->ids['farid_user']], ['status' => 'active', 'can_see_fees' => true, 'can_approve' => true, 'relationship' => 'guardian']);
            app(FeePlans::class)->save($actor, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'session_year_id' => $year, 'name' => 'Baobab private tuition', 'scope' => 'all', 'target_id' => null, 'amount' => '10.01', 'currency' => 'ZAR', 'state' => 'active']);
            app(FeeAccounts::class)->save($actor, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'session_year_id' => $year, 'name' => 'Baobab private account', 'currency' => 'ZAR', 'learner_ids' => [$this->schoolB->ids['asha']], 'holders' => [['user_id' => $this->schoolB->ids['farid_user'], 'share_percent' => '100']]]);
            $bill = app(BillRuns::class)->prepare($actor, ['operation_id' => (string) Str::uuid(), 'session_year_id' => $year, 'as_of' => '2026-06-15', 'due_on' => '2026-07-01', 'tax_treatment' => 'no_vat']);
            app(BillRuns::class)->approve($actor, $bill->id, $this->operation(1));
            app(BillRuns::class)->post($actor, $bill->id, $this->operation(2));
            $invoice = Invoice::firstOrFail();
            app(FeeLeaverPolicies::class)->save($actor, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'prior_policyless_basis' => 'daily', 'prior_reductions' => 'retain_full', 'independent_approval' => false]);
            Enrolment::where('learner_id', $this->schoolB->ids['asha'])->update(['ends_on' => '2026-05-31']);
            $run = app(FeeLeaverRuns::class)->prepare($actor, ['operation_id' => (string) Str::uuid(), 'invoice_id' => $invoice->id, 'learner_id' => $this->schoolB->ids['asha'], 'private_explanation' => 'Baobab private departure proof']);

            return ['invoice' => $invoice->id, 'run' => $run->id];
        });
        $this->policy();
        $this->actingAs($this->admin)->get('/fee-leavers/new?invoice_id='.$foreign['invoice'])->assertNotFound()->assertDontSee('Baobab');
        $this->get('/fee-leavers/'.$foreign['run'])->assertNotFound()->assertDontSee('Baobab');
        $this->refuses(fn () => app(FeeLeaverRuns::class)->prepare($this->admin, ['operation_id' => (string) Str::uuid(), 'invoice_id' => $foreign['invoice'], 'learner_id' => $this->schoolB->ids['asha'], 'private_explanation' => 'Synthetic foreign refusal']), 404);
        $this->assertSame(0, FeeCreditNote::count());
    }

    public function test_parent_sees_only_own_generic_departure_credit_and_private_rules_remain_finance_only(): void
    {
        $invoice = $this->billed();
        $this->policy();
        $this->depart('2026-05-31');
        $this->apply($this->review($invoice));
        $parent = User::findOrFail($this->schoolA->ids['farid_user']);
        $page = app(ParentFeeStatements::class)->show($parent, $invoice->fee_account_id, perPage: 50);
        $json = json_encode($page, JSON_THROW_ON_ERROR);
        $this->assertStringNotContainsString('PRIVATE-SYNTHETIC-DEPARTURE-EVIDENCE', $json);
        $this->assertStringNotContainsString('prior_policyless_basis', $json);
        $this->assertStringNotContainsString('Mona', $json);
        $this->assertStringContainsString('leaver_credit', $json);
        $this->assertStringNotContainsString('PRIVATE-SYNTHETIC-DEPARTURE-EVIDENCE', (string) DB::table('fee_leaver_runs')->value('private_explanation'));
        $migration = require database_path('migrations/2026_10_27_950000_add_reviewed_leaver_credits.php');
        try {
            $migration->down();
            $this->fail('Retain populated departure history');
        } catch (RuntimeException $error) {
            $this->assertStringContainsString('Retain', $error->getMessage());
        }$this->healthy();
    }

    public function test_sql_retains_exact_encrypted_review_evidence_and_original_creation_time(): void
    {
        $invoice = $this->billed();
        $this->policy();
        $this->depart('2026-05-31');
        $run = $this->review($invoice);
        $cipher = (string) DB::table('fee_leaver_runs')->where('id', $run->id)->value('private_explanation');
        $changed = strtoupper(substr($cipher, 0, 1)).substr($cipher, 1);
        $this->assertNotSame($cipher, $changed);
        try {
            DB::table('fee_leaver_runs')->where('id', $run->id)->update(['state' => 'approved', 'revision' => 2, 'approved_by' => $this->admin->id, 'private_explanation' => $changed]);
            $this->fail('Encrypted evidence bytes must not change at approval');
        } catch (QueryException) {
            $this->assertSame('preview', $run->fresh()->state);
        }
        try {
            DB::table('fee_leaver_runs')->where('id', $run->id)->update(['state' => 'approved', 'revision' => 2, 'approved_by' => $this->admin->id, 'created_at' => now()->addSecond()]);
            $this->fail('Review creation time must not change');
        } catch (QueryException) {
            $this->assertSame('preview', $run->fresh()->state);
        }
        $this->apply($run);
        $this->healthy();
    }

    public function test_existing_discount_reviews_also_retain_exact_private_evidence_on_upgrade(): void
    {
        $invoice = $this->billed();
        $rule = app(FeeDiscounts::class)->save($this->admin, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'session_year_id' => $this->year, 'name' => 'Synthetic original bursary', 'kind' => 'bursary', 'source_kind' => 'annual_tuition', 'learner_id' => $this->schoolA->ids['asha'], 'fee_plan_id' => null, 'fee_item_id' => null, 'currency' => 'ZAR', 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'amount_kind' => 'fixed', 'amount_value' => 100, 'sibling_position' => null, 'minimum_paid_bp' => null, 'payment_deadline' => null, 'staff_roles' => null, 'overlap' => 'remaining', 'independent_approval' => false]);
        $rule = app(FeeDiscounts::class)->transition($this->admin, $rule->id, $this->operation(1) + ['action' => 'approve']);
        $review = app(FeeDiscountRuns::class)->prepare($this->admin, ['operation_id' => (string) Str::uuid(), 'fee_discount_id' => $rule->id, 'fee_account_id' => $invoice->fee_account_id, 'learner_id' => $this->schoolA->ids['asha'], 'private_explanation' => 'Synthetic retained bursary proof']);
        $cipher = (string) DB::table('fee_discount_runs')->where('id', $review->id)->value('private_explanation');
        $changed = strtoupper(substr($cipher, 0, 1)).substr($cipher, 1);
        $this->assertNotSame($cipher, $changed);
        try {
            DB::table('fee_discount_runs')->where('id', $review->id)->update(['state' => 'approved', 'revision' => 2, 'approved_by' => $this->admin->id, 'private_explanation' => $changed]);
            $this->fail('Retain exact original discount review evidence');
        } catch (QueryException) {
            $this->assertSame('preview', $review->fresh()->state);
        }
        $review = app(FeeDiscountRuns::class)->approve($this->admin, $review->id, $this->operation(1));
        app(FeeDiscountRuns::class)->post($this->admin, $review->id, $this->operation(2));
        $this->healthy();
    }

    private function billed(bool $makeFamilies = true): Invoice
    {
        if ($makeFamilies) {
            $this->families();
        }$bill = $this->prepare();
        app(BillRuns::class)->approve($this->admin, $bill->id, $this->operation(1));
        app(BillRuns::class)->post($this->admin, $bill->id, $this->operation(2));

        return Invoice::whereIn('id', InvoiceLine::where('learner_id', $this->schoolA->ids['asha'])->select('invoice_id'))->firstOrFail();
    }

    private function depart(string $date): void
    {
        Enrolment::where('learner_id', $this->schoolA->ids['asha'])->where('session_year_id', $this->year)->update(['ends_on' => $date]);
    }

    private function policy(array $changes = []): FeeLeaverPolicy
    {
        return app(FeeLeaverPolicies::class)->save($this->admin, array_replace(['operation_id' => (string) Str::uuid(), 'revision' => 0, 'prior_policyless_basis' => 'daily', 'prior_reductions' => 'retain_full', 'independent_approval' => false], $changes));
    }

    private function input(Invoice $invoice): array
    {
        return ['operation_id' => (string) Str::uuid(), 'invoice_id' => $invoice->id, 'learner_id' => $this->schoolA->ids['asha'], 'private_explanation' => 'PRIVATE-SYNTHETIC-DEPARTURE-EVIDENCE'];
    }

    private function review(Invoice $invoice): FeeLeaverRun
    {
        return app(FeeLeaverRuns::class)->prepare($this->admin, $this->input($invoice));
    }

    private function apply(FeeLeaverRun $run): FeeLeaverRun
    {
        return $this->applyPrepared(app(FeeLeaverRuns::class)->approve($this->admin, $run->id, $this->operation(1)));
    }

    private function applyPrepared(FeeLeaverRun $run): FeeLeaverRun
    {
        return app(FeeLeaverRuns::class)->post($this->admin, $run->id, $this->operation(2));
    }

    private function healthy(): void
    {
        $this->assertSame([], app(LedgerService::class)->projectionDifferences());
        $this->assertTrue(FeePaymentReconciliation::healthy(app(FeePaymentReconciliation::class)->inspect()));
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
