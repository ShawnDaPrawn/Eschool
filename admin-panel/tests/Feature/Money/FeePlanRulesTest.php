<?php

declare(strict_types=1);

namespace Tests\Feature\Money;

use App\Models\Money\BillRun;
use App\Models\Money\FeePlan;
use App\Models\Money\Invoice;
use App\Models\Money\InvoiceLine;
use App\Models\People\Enrolment;
use App\Models\Platform\GuardianLink;
use App\Models\Platform\ProfileFieldCategory;
use App\Models\Platform\ProfileFieldDefinition;
use App\Models\Platform\ProfileFieldValue;
use App\Models\Platform\SchoolMembership;
use App\Models\SessionYear;
use App\Models\User;
use App\Services\Money\BillRuns;
use App\Services\Money\FeeAccounts;
use App\Services\Money\FeeAllocations;
use App\Services\Money\FeeCalculator;
use App\Services\Money\FeePaymentChoices;
use App\Services\Money\FeePayments;
use App\Services\Money\FeePaymentSettings;
use App\Services\Money\FeePlans;
use App\Services\Money\FeeReminders;
use App\Services\Money\FeeReminderSettings;
use App\Services\Money\FeeReporting;
use App\Services\Money\ParentFeeStatements;
use App\Services\Platform\Ledger\AccountType;
use App\Services\Platform\Ledger\LedgerService;
use App\Services\Platform\Ledger\Money;
use App\Services\Platform\Ledger\Posting;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Tests\Concerns\UsesTwoSchools;
use Tests\TestCase;

class FeePlanRulesTest extends TestCase
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
        foreach (['a' => '1', 'c' => '7'] as $section => $grade) {
            $class = DB::table('class_sections')->where('id', $this->schoolA->ids['section_'.$section])->value('class_id');
            DB::table('classes')->where('school_id', 1)->where('id', $class)->update(['grade_level_id' => DB::table('grade_levels')->where('code', $grade)->value('id')]);
        }
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

    }

    public function test_phase_and_campus_boarding_profile_rules_match_only_configured_learner_values(): void
    {
        $campus = $this->field('campus');
        $boarding = $this->field('boarding', 'boolean');
        $this->value($campus, 'asha_user', 'North');
        $this->value($boarding, 'asha_user', true);
        $this->value($campus, 'ben_user', 'South');
        $this->value($boarding, 'ben_user', true);
        $this->value($campus, 'cara_user', 'North');
        $this->value($boarding, 'cara_user', false);
        $plan = $this->savePlan(['applies_to' => [['field' => 'profile', 'definition_id' => $campus->id, 'values' => ['North']], ['field' => 'profile', 'definition_id' => $boarding->id, 'values' => [true]]]]);
        $preview = app(FeeCalculator::class)->preview($this->admin, $this->year, '2026-06-15');
        $ready = array_values(array_filter($preview['rows'], fn ($r) => $r['issues'] === []));
        $this->assertSame([$this->schoolA->ids['asha']], array_column($ready, 'learner_id'));
        $phase = DB::table('grade_levels as g')->join('classes as c', 'c.grade_level_id', '=', 'g.id')->join('class_sections as s', 's.class_id', '=', 'c.id')->where('s.id', $this->schoolA->ids['section_a'])->value('g.phase');
        $this->savePlan(['revision' => 1, 'applies_to' => [['field' => 'phase', 'values' => [$phase]]]], $plan->id);
        $this->assertGreaterThanOrEqual(2, app(FeeCalculator::class)->preview($this->admin, $this->year, '2026-06-15')['matched']);
    }

    public function test_hidden_sensitive_nonlearner_and_real_foreign_profile_definitions_are_refused(): void
    {
        $field = $this->field('campus');
        $foreign = app(CurrentSchool::class)->run(2, fn () => $this->field('baobab-campus'));
        foreach ([$foreign->id] as $id) {
            $this->refuses(fn () => $this->savePlan(['applies_to' => [['field' => 'profile', 'definition_id' => $id, 'values' => ['North']]]]), 404);
        }
        foreach ([['hidden' => true], ['sensitive' => true], ['profile_type' => 'parent'], ['visible_to' => ['owner']]] as $change) {
            $old = $field->only(array_keys($change));
            $field->update($change);
            $this->refuses(fn () => $this->savePlan(['applies_to' => [['field' => 'profile', 'definition_id' => $field->id, 'values' => ['North']]]]), 404);
            $field->update($old);
        }
        $teacher = User::findOrFail($this->schoolA->ids['tara_user']);
        $this->refuses(fn () => app(FeePlans::class)->save($teacher, $this->planInput()), 403);
    }

    public function test_changed_profile_after_preview_invalidates_approval_without_posting(): void
    {
        $campus = $this->field('campus');
        foreach (['asha_user', 'ben_user', 'cara_user'] as $user) {
            $this->value($campus, $user, 'North');
        }
        $this->savePlan(['applies_to' => [['field' => 'profile', 'definition_id' => $campus->id, 'values' => ['North']]]]);
        $this->families();
        $run = $this->prepare();
        $this->value($campus, 'asha_user', 'South');
        $this->refuses(fn () => app(BillRuns::class)->approve($this->admin, $run->id, $this->operation(1)), 409);
        $this->assertSame(0, Invoice::count());
        $this->assertSame(0, DB::table('ledger_entries')->count());
    }

    public function test_instalments_preserve_annual_and_each_holder_cents_and_age_by_actual_due_dates(): void
    {
        $this->savePlan(['payment_options' => $this->scheduleOptions(), 'default_payment_option' => 'terms']);
        $accounts = $this->families();
        $run = $this->postRun();
        $lines = InvoiceLine::join('invoices as i', 'i.id', '=', 'invoice_lines.invoice_id')->where('i.fee_account_id', $accounts[0]->id)->get(['invoice_lines.*']);
        $this->assertCount(8, $lines);
        $this->assertSame(2002, $lines->sum('amount_cents'));
        $holders = [];
        foreach ($lines as $line) {
            foreach ($line->holder_shares as $share) {
                $holders[$share['user_id']] = ($holders[$share['user_id']] ?? 0) + $share['amount_cents'];
            }
        }
        $this->assertSame(1002, $holders[$this->schoolA->ids['farid_user']]);
        $this->assertSame(1000, $holders[$this->schoolA->ids['mona_user']]);
        $report = app(FeeReporting::class)->ageing($this->admin, 'ZAR');
        $this->assertSame(3003, $report['outstanding_cents']);
        $this->assertGreaterThan(0, $report['buckets']['current']);
        $this->assertGreaterThan(0, $report['buckets']['days_90_plus']);
        $statement = app(ParentFeeStatements::class)->show(User::findOrFail($this->schoolA->ids['farid_user']), $accounts[0]->id, perPage: 50);
        $this->assertSame(['2026-01-15', '2026-04-15', '2026-07-15', '2026-10-15'], array_values(array_unique(array_column($statement['charges']['items'], 'due_on'))));
        $this->assertSame([], app(LedgerService::class)->projectionDifferences());
        $again = $this->prepare();
        $this->assertSame(0, $again->snapshot['billable']);
        $this->assertSame(3, Invoice::join('invoice_lines', 'invoice_lines.invoice_id', '=', 'invoices.id')->distinct('invoice_lines.learner_id')->count('invoice_lines.learner_id'));
    }

    public function test_invalid_schedule_totals_duplicate_dates_keys_and_out_of_year_dates_refuse(): void
    {
        $bad = $this->scheduleOptions();
        $bad[0]['installments'][0]['weight_bp'] = 2499;
        $this->refuses(fn () => $this->savePlan(['payment_options' => $bad]), 422);
        $bad = $this->scheduleOptions();
        $bad[0]['installments'][1]['month'] = 1;
        $this->refuses(fn () => $this->savePlan(['payment_options' => $bad]), 422);
        $bad = $this->scheduleOptions();
        $bad[0]['installments'][0]['month'] = 2;
        $bad[0]['installments'][0]['day'] = 29;
        $this->refuses(fn () => $this->savePlan(['payment_options' => $bad]), 422);
        $this->refuses(fn () => $this->savePlan(['payment_options' => [$this->scheduleOptions()[0], $this->scheduleOptions()[0]]]), 422);
        $this->refuses(fn () => $this->savePlan(['payment_options' => $this->scheduleOptions(), 'default_payment_option' => 'missing']), 422);
        $this->assertSame(0, FeePlan::count());
    }

    public function test_unreadable_or_newly_hidden_rule_values_create_explicit_exceptions(): void
    {
        $field = $this->field('campus');
        $this->value($field, 'asha_user', 'North');
        $this->savePlan(['applies_to' => [['field' => 'profile', 'definition_id' => $field->id, 'values' => ['North']]]]);
        ProfileFieldValue::where('definition_id', $field->id)->update(['value' => 'invalid ciphertext']);
        $this->assertArrayHasKey('Fee plan rule inputs need review', app(FeeCalculator::class)->preview($this->admin, $this->year, '2026-06-15')['exceptions']);
        $field->update(['hidden' => true]);
        $this->assertArrayHasKey('Fee plan rule inputs need review', app(FeeCalculator::class)->preview($this->admin, $this->year, '2026-06-15')['exceptions']);
    }

    public function test_finance_can_select_an_option_before_posting_and_replay_stays_permanent(): void
    {
        $plan = $this->savePlan(['payment_options' => $this->scheduleOptions(), 'default_payment_option' => 'terms']);
        $families = $this->families();
        $input = $this->choiceInput($plan, 'annual');
        $choice = app(FeePaymentChoices::class)->save($this->admin, $families[0]->id, $input);
        $this->assertSame($choice->id, app(FeePaymentChoices::class)->save($this->admin, $families[0]->id, $input)->id);
        $run = $this->postRun();
        $this->assertSame(1, InvoiceLine::where('learner_id', $this->schoolA->ids['asha'])->count());
        $this->assertSame(4, InvoiceLine::where('learner_id', $this->schoolA->ids['cara'])->count());
        $this->assertSame('2026-07-01', InvoiceLine::where('learner_id', $this->schoolA->ids['asha'])->firstOrFail()->installment_due_on);
        $this->assertSame($choice->id, app(FeePaymentChoices::class)->save($this->admin, $families[0]->id, $input)->id);
        $this->refuses(fn () => app(FeePaymentChoices::class)->save($this->admin, $families[0]->id, $this->choiceInput($plan, 'terms', ['revision' => 1])), 422);
    }

    public function test_option_changes_invalidate_prepared_review_and_wrong_family_school_role_cannot_save(): void
    {
        $plan = $this->savePlan(['payment_options' => $this->scheduleOptions(), 'default_payment_option' => 'terms']);
        $families = $this->families();
        $run = $this->prepare();
        $input = $this->choiceInput($plan, 'annual');
        app(FeePaymentChoices::class)->save($this->admin, $families[0]->id, $input);
        $this->refuses(fn () => app(BillRuns::class)->approve($this->admin, $run->id, $this->operation(1)), 409);
        $this->refuses(fn () => app(FeePaymentChoices::class)->save($this->admin, $families[1]->id, array_replace($input, ['operation_id' => (string) Str::uuid()])), 404);
        $teacher = User::findOrFail($this->schoolA->ids['tara_user']);
        $this->refuses(fn () => app(FeePaymentChoices::class)->save($teacher, $families[0]->id, $input), 403);
        $this->refuses(fn () => app(CurrentSchool::class)->run(2, fn () => app(FeePaymentChoices::class)->save($this->admin, $families[0]->id, $input)), 403);
        $this->actingAs($this->admin)->post('/fee-accounts/'.$families[0]->id.'/payment-choice', $this->choiceInput($plan, 'terms', ['revision' => 1]) + ['expected_school_id' => 2, 'expected_account_id' => $this->admin->id])->assertForbidden();
        $this->assertSame(0, DB::table('ledger_entries')->count());
    }

    public function test_installment_payment_allocation_uses_real_due_order_and_reminders_stop_for_paid_holder(): void
    {
        $this->savePlan(['payment_options' => $this->scheduleOptions(), 'default_payment_option' => 'terms']);
        $families = $this->families();
        $this->postRun();
        app(FeePaymentSettings::class)->save($this->admin, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'allocation_order' => 'oldest_first']);
        $payment = app(FeePayments::class)->record($this->admin, ['operation_id' => (string) Str::uuid(), 'fee_account_id' => $families[0]->id, 'holder_user_id' => $this->schoolA->ids['farid_user'], 'pinned_learner_id' => null, 'method' => 'eft', 'source_reference' => 'SCHEDULE-PAYMENT', 'amount' => '5.02', 'received_on' => '2026-06-15', 'confirmed' => true]);
        $preview = app(FeeAllocations::class)->preview($this->admin, $payment->id);
        $this->assertSame(['2026-01-15', '2026-01-15', '2026-04-15', '2026-04-15'], array_column($preview['rows'], 'due_on'));
        app(FeeAllocations::class)->post($this->admin, $payment->id, ['operation_id' => (string) Str::uuid(), 'fingerprint' => $preview['fingerprint'], 'confirmed' => true]);
        app(FeeReminderSettings::class)->save($this->admin, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'enabled' => true, 'min_days_overdue' => 7, 'cadence_days' => 7, 'local_hour' => 0]);
        $this->assertSame(0, app(FeeReminders::class)->parent(User::findOrFail($this->schoolA->ids['farid_user'])));
        $this->assertSame(1, app(FeeReminders::class)->parent(User::findOrFail($this->schoolA->ids['mona_user'])));
        $this->assertSame([], app(LedgerService::class)->projectionDifferences());
    }

    public function test_panel_parses_human_schedules_and_rules_with_private_error_binding(): void
    {
        $field = $this->field('boarding', 'boolean');
        $input = $this->planInput() + ['rule_rows' => [['field' => 'profile:'.$field->id, 'values' => 'No']], 'option_rows' => [['key' => 'terms', 'label' => 'Four installments', 'schedule' => "01-15 25.00\n04-15 25.00\n07-15 25.00\n10-15 25.00"]], 'payment_default_index' => '0', 'expected_school_id' => 1, 'expected_account_id' => $this->admin->id];
        $this->actingAs($this->admin)->get('/fee-plans/new')->assertOk()->assertSee('Additional matching rules')->assertSee('Payment schedules');
        $this->post('/fee-plans/new', $input)->assertStatus(303);
        $plan = FeePlan::firstOrFail();
        $this->assertSame([false], $plan->applies_to[0]['values']);
        $this->assertCount(4, $plan->payment_options[0]['installments']);
        $this->get('/fee-plans/'.$plan->id.'/edit')->assertOk()->assertHeader('Cache-Control', 'no-store, private');
        $bad = array_replace($input, ['operation_id' => (string) Str::uuid(), 'option_rows' => [['key' => 'terms', 'label' => 'Bad schedule', 'schedule' => 'not a date']]]);
        $this->post('/fee-plans/new', $bad)->assertStatus(422)->assertSessionMissing('_old_input');
    }

    public function test_copy_revalidates_relative_dates_and_sql_rejects_forged_installments(): void
    {
        $plan = $this->savePlan(['payment_options' => $this->scheduleOptions(), 'default_payment_option' => 'terms']);
        $this->families();
        $this->postRun();
        $next = SessionYear::create(['name' => 'Synthetic next schedule year', 'start_date' => '2027-01-01', 'end_date' => '2027-12-31', 'default' => 0]);
        $copy = app(FeePlans::class)->copy($this->admin, $plan->id, ['operation_id' => (string) Str::uuid(), 'revision' => 1, 'session_year_id' => $next->id, 'name' => 'Copied schedules', 'uplift_percent' => '5']);
        $this->assertSame($plan->payment_options, $copy->payment_options);
        $this->assertSame('draft', $copy->state);
        $line = InvoiceLine::firstOrFail();
        try {
            DB::table('invoice_lines')->where('id', $line->id)->update(['installment_due_on' => '2026-02-01']);
            $this->fail('Posted schedule must stay immutable');
        } catch (QueryException $e) {
            $this->assertSame('45000', $e->errorInfo[0]);
        }
        $migration = require database_path('migrations/2026_10_27_800000_expand_fee_plan_rules_and_schedules.php');
        try {
            $migration->down();
            $this->fail('Retain populated schedules');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('Retain expanded', $e->getMessage());
        }
    }

    public function test_sql_refuses_a_partial_reviewed_schedule_even_with_a_balanced_matching_partial_entry(): void
    {
        $this->savePlan(['payment_options' => $this->scheduleOptions(), 'default_payment_option' => 'terms']);
        $families = $this->families();
        $run = $this->prepare();
        app(BillRuns::class)->approve($this->admin, $run->id, $this->operation(1));
        $account = $families[0];
        $row = collect($run->snapshot['rows'])->firstWhere('account_id', $account->id);
        $part = $row['installment_lines'][0];
        $ledger = app(LedgerService::class);
        $receivable = $ledger->openAccount(AccountType::FeeReceivable, 'fees:account:'.$account->id.':receivable', 'ZAR');
        $income = $ledger->openAccount(AccountType::FeeIncome, 'fees:income:ZAR', 'ZAR');
        $account->update(['ledger_account_id' => $receivable]);
        $money = Money::ofCents($part['amount_cents'], 'ZAR');
        $entry = $ledger->post('synthetic-partial-schedule', 'synthetic-partial-schedule', 'Synthetic partial schedule', [Posting::debit($receivable, $money), Posting::credit($income, $money)]);
        $invoice = Invoice::create(['fee_account_id' => $account->id, 'bill_run_id' => $run->id, 'session_year_id' => $this->year, 'ledger_entry_id' => $entry, 'number' => 'SYNTHETIC-PARTIAL', 'account_name' => $account->name, 'currency' => 'ZAR', 'total_cents' => $part['amount_cents'], 'holders' => [], 'issued_on' => '2026-06-15', 'due_on' => $part['due_on'], 'created_by' => $this->admin->id, 'created_at' => now('UTC')]);
        InvoiceLine::create(['invoice_id' => $invoice->id, 'session_year_id' => $this->year, 'fee_plan_id' => $row['plan_id'], 'plan_revision' => $row['plan_revision'], 'learner_id' => $row['learner_id'], 'learner_name' => $row['name'], 'description' => $row['plan_names'][0], 'period_start' => '2026-01-01', 'period_end' => '2026-12-31', 'currency' => 'ZAR', 'amount_cents' => $part['amount_cents'], 'vat_cents' => 0, 'holder_shares' => $part['holder_shares'], 'installment_number' => $part['installment_number'], 'installment_due_on' => $part['due_on']]);
        try {
            $invoice->update(['sealed_at' => now('UTC')]);
            $this->fail('Every reviewed installment must be present');
        } catch (QueryException $e) {
            $this->assertStringContainsString('every reviewed installment', $e->getMessage());
        }
        $this->assertNull($invoice->fresh()->sealed_at);
        $this->assertSame([], app(LedgerService::class)->projectionDifferences());
    }

    private function choiceInput(FeePlan $plan, string $option, array $changes = []): array
    {
        return array_replace(['operation_id' => (string) Str::uuid(), 'revision' => 0, 'session_year_id' => $this->year, 'learner_id' => $this->schoolA->ids['asha'], 'fee_plan_id' => $plan->id, 'option_key' => $option], $changes);
    }

    private function planInput(array $changes = []): array
    {
        return array_replace(['operation_id' => (string) Str::uuid(), 'revision' => 0, 'name' => 'Synthetic scheduled tuition', 'session_year_id' => $this->year, 'scope' => 'all', 'target_id' => null, 'currency' => 'ZAR', 'amount' => '10.01', 'state' => 'active'], $changes);
    }

    private function savePlan(array $changes = [], ?int $id = null): FeePlan
    {
        return app(FeePlans::class)->save($this->admin, $this->planInput($changes), $id);
    }

    private function scheduleOptions(): array
    {
        return [['key' => 'terms', 'label' => 'Four installments', 'installments' => array_map(fn ($m) => ['month' => $m, 'day' => 15, 'weight_bp' => 2500], [1, 4, 7, 10])], ['key' => 'annual', 'label' => 'Annual', 'installments' => []]];
    }

    private function field(string $key, string $type = 'text'): ProfileFieldDefinition
    {
        $category = ProfileFieldCategory::firstOrCreate(['profile_type' => 'learner', 'key' => 'billing-selector'], ['label' => 'Billing selectors', 'sort_order' => 0]);

        return ProfileFieldDefinition::create(['category_id' => $category->id, 'profile_type' => 'learner', 'key' => $key, 'label' => $key, 'type' => $type, 'source' => 'custom', 'validation' => [], 'options' => [], 'visible_to' => ['school_admin'], 'editable_by' => ['school_admin'], 'required' => false, 'platform_locked' => false, 'sensitive' => false, 'encrypted' => true, 'hidden' => false, 'sort_order' => 0]);
    }

    private function value(ProfileFieldDefinition $field, string $user, mixed $value): void
    {
        ProfileFieldValue::updateOrCreate(['definition_id' => $field->id, 'user_id' => $this->schoolA->ids[$user]], ['encrypted' => true, 'value' => Crypt::encryptString(json_encode($value, JSON_THROW_ON_ERROR))]);
    }

    private function families(): array
    {
        return [app(FeeAccounts::class)->save($this->admin, $this->familyInput()), app(FeeAccounts::class)->save($this->admin, $this->familyInput(['name' => 'Synthetic Ben', 'learner_ids' => [$this->schoolA->ids['ben']], 'holders' => [['user_id' => $this->schoolA->ids['gina_user'], 'share_percent' => '100']]]))];
    }

    private function postRun(): BillRun
    {
        $run = $this->prepare();
        app(BillRuns::class)->approve($this->admin, $run->id, $this->operation(1));

        return app(BillRuns::class)->post($this->admin, $run->id, $this->operation(2));
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
