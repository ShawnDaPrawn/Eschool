<?php

declare(strict_types=1);

namespace Tests\Feature\Money;

use App\Models\Money\BillRun;
use App\Models\Money\FeeLifecyclePolicy;
use App\Models\Money\FeePlan;
use App\Models\Money\Invoice;
use App\Models\Money\InvoiceLine;
use App\Models\People\Enrolment;
use App\Models\Platform\GuardianLink;
use App\Models\Platform\SchoolMembership;
use App\Models\SessionYear;
use App\Models\User;
use App\Services\Money\BillRuns;
use App\Services\Money\FeeAccounts;
use App\Services\Money\FeeCalculator;
use App\Services\Money\FeeLifecyclePolicies;
use App\Services\Money\FeePlans;
use App\Services\Platform\Ledger\LedgerService;
use App\Services\Platform\Modules\ModuleGate;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Tests\Concerns\UsesTwoSchools;
use Tests\TestCase;

class FeeLifecycleTest extends TestCase
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

    public function test_partial_enrolment_needs_an_explicit_policy_and_preview_matches_posted_period_and_cents(): void
    {
        $this->families();
        Enrolment::where('learner_id', $this->schoolA->ids['asha'])->update(['starts_on' => '2026-06-15']);
        $before = $this->prepare();
        $this->assertArrayHasKey('Pro-rata or leaver rule required', $before->snapshot['exceptions']);
        $this->policy();
        $run = $this->prepare();
        $row = collect($run->snapshot['rows'])->firstWhere('learner_id', $this->schoolA->ids['asha']);
        $this->assertSame(548, $row['amount_cents']);
        $this->assertSame(1001, $row['annual_cents']);
        $this->assertSame('2026-06-15', $row['period_start']);
        $this->assertSame(548, $row['proration']['amount_cents']);
        $rate = collect(app(FeeCalculator::class)->preview($this->admin, $this->year, '2026-06-15')['rows'])->firstWhere('learner_id', $this->schoolA->ids['asha']);
        $this->assertSame($row['amount_cents'], $rate['amount_cents']);
        app(BillRuns::class)->approve($this->admin, $run->id, $this->operation(1));
        app(BillRuns::class)->post($this->admin, $run->id, $this->operation(2));
        $line = InvoiceLine::where('learner_id', $this->schoolA->ids['asha'])->firstOrFail();
        $this->assertSame(548, $line->amount_cents);
        $this->assertSame('2026-06-15', $line->period_start->toDateString());
        $this->assertSame(548, array_sum(array_column($line->holder_shares, 'amount_cents')));
        $this->assertSame(1001, FeePlan::firstOrFail()->amount_cents);
        $this->assertSame([], app(LedgerService::class)->projectionDifferences());
    }

    public function test_calendar_month_known_leaver_and_full_year_policies_are_explicit(): void
    {
        $this->families();
        Enrolment::where('learner_id', $this->schoolA->ids['asha'])->update(['starts_on' => '2026-06-15', 'ends_on' => '2026-08-02']);
        $this->policy(['join_basis' => 'calendar_month', 'leaver_basis' => 'calendar_month']);
        $run = $this->prepare();
        $row = collect($run->snapshot['rows'])->firstWhere('learner_id', $this->schoolA->ids['asha']);
        $this->assertSame(250, $row['amount_cents']);
        $this->assertSame('2026-06-01', $row['period_start']);
        $this->assertSame('2026-08-31', $row['period_end']);
        $this->policy(['revision' => 1, 'join_basis' => 'full_year', 'leaver_basis' => 'no_credit']);
        $run = $this->prepare();
        $row = collect($run->snapshot['rows'])->firstWhere('learner_id', $this->schoolA->ids['asha']);
        $this->assertSame(1001, $row['amount_cents']);
        $this->assertSame('2026-01-01', $row['period_start']);
        $this->assertSame('2026-12-31', $row['period_end']);
    }

    public function test_remaining_installments_redistribute_exact_holder_cents_and_reviewed_due_fallback(): void
    {
        $this->families();
        Enrolment::where('learner_id', $this->schoolA->ids['asha'])->update(['starts_on' => '2026-06-15']);
        $plan = FeePlan::firstOrFail();
        $plan->forceFill(['payment_options' => [['key' => 'terms', 'label' => 'Three payments', 'installments' => [['month' => 1, 'day' => 1, 'weight_bp' => 3333], ['month' => 5, 'day' => 1, 'weight_bp' => 3333], ['month' => 9, 'day' => 1, 'weight_bp' => 3334]]]], 'default_payment_option' => 'terms', 'revision' => 2])->save();
        $this->policy(['installments' => 'remaining_dates']);
        $run = $this->prepare();
        $row = collect($run->snapshot['rows'])->firstWhere('learner_id', $this->schoolA->ids['asha']);
        $this->assertCount(1, $row['installment_lines']);
        $this->assertSame('2026-09-01', $row['installment_lines'][0]['due_on']);
        $this->assertSame(548, $row['installment_lines'][0]['amount_cents']);
        $fallback = $this->prepare(['as_of' => '2026-10-27', 'due_on' => '2026-11-01']);
        $row = collect($fallback->snapshot['rows'])->firstWhere('learner_id', $this->schoolA->ids['asha']);
        $this->assertSame('2026-11-01', $row['installment_lines'][0]['due_on']);
        app(BillRuns::class)->approve($this->admin, $run->id, $this->operation(1));
        app(BillRuns::class)->post($this->admin, $run->id, $this->operation(2));
        $this->assertSame([], app(LedgerService::class)->projectionDifferences());
    }

    public function test_policy_or_enrolment_changes_invalidate_review_but_never_reinterpret_posted_history(): void
    {
        $this->families();
        $this->policy();
        $run = $this->prepare();
        $this->policy(['revision' => 1, 'installments' => 'remaining_dates']);
        $this->refuses(fn () => app(BillRuns::class)->approve($this->admin, $run->id, $this->operation(1)), 409);
        $run = $this->prepare();
        Enrolment::where('learner_id', $this->schoolA->ids['asha'])->update(['starts_on' => '2026-02-01']);
        $this->refuses(fn () => app(BillRuns::class)->approve($this->admin, $run->id, $this->operation(1)), 409);
        $run = $this->prepare();
        app(BillRuns::class)->approve($this->admin, $run->id, $this->operation(1));
        $post = $this->operation(2);
        app(BillRuns::class)->post($this->admin, $run->id, $post);
        $before = InvoiceLine::orderBy('id')->get()->toArray();
        $this->policy(['revision' => 2, 'join_basis' => 'full_year']);
        app(BillRuns::class)->post($this->admin, $run->id, $post);
        $this->assertSame($before, InvoiceLine::orderBy('id')->get()->toArray());
    }

    public function test_policy_role_school_current_year_binding_replay_and_private_panel(): void
    {
        $input = $this->policyInput();
        $policy = app(FeeLifecyclePolicies::class)->save($this->admin, $input);
        $this->assertSame($policy->id, app(FeeLifecyclePolicies::class)->save($this->admin, $input)->id);
        $this->refuses(fn () => app(FeeLifecyclePolicies::class)->save(User::findOrFail($this->schoolA->ids['farid_user']), $this->policyInput()), 403);
        $this->refuses(fn () => app(FeeLifecyclePolicies::class)->save($this->admin, $this->policyInput(['session_year_id' => $this->schoolB->ids['session_year']])), 404);
        $this->actingAs($this->admin)->get('/fee-lifecycle')->assertOk()->assertHeader('Cache-Control', 'no-store, private')->assertSee('Mid-year join charging');
        $this->post('/fee-lifecycle', $this->policyInput(['revision' => 1]) + ['expected_school_id' => 2, 'expected_account_id' => $this->admin->id])->assertForbidden();
        $this->post('/fee-lifecycle', $this->policyInput(['revision' => 1, 'join_basis' => 'PRIVATE-INVALID']) + ['expected_school_id' => 1, 'expected_account_id' => $this->admin->id])->assertStatus(422)->assertSessionMissing('_old_input');
        app(ModuleGate::class)->change('fees', false, $this->admin);
        $this->get('/fee-lifecycle')->assertOk()->assertSee('Fee policies are read-only');
        $this->refuses(fn () => $this->policy(['revision' => 1]), 403);
    }

    public function test_contiguous_class_changes_are_continuous_school_attendance_not_a_false_join_or_departure(): void
    {
        $this->families();
        $this->policy();
        Enrolment::where('learner_id', $this->schoolA->ids['asha'])->update(['ends_on' => '2026-06-30']);
        Enrolment::create(['learner_id' => $this->schoolA->ids['asha'], 'session_year_id' => $this->year, 'class_section_id' => $this->schoolA->ids['section_c'], 'starts_on' => '2026-07-01', 'ends_on' => null, 'legacy_dates' => false, 'reason' => 'Synthetic contiguous class move']);
        $run = $this->prepare();
        $row = collect($run->snapshot['rows'])->firstWhere('learner_id', $this->schoolA->ids['asha']);
        $this->assertSame([], $row['issues']);
        $this->assertSame(1001, $row['amount_cents']);
        $this->assertSame('2026-01-01', $row['period_start']);
        $this->assertSame('2026-12-31', $row['period_end']);
        $this->assertCount(2, $row['enrolment_segments']);
        app(BillRuns::class)->approve($this->admin, $run->id, $this->operation(1));
        app(BillRuns::class)->post($this->admin, $run->id, $this->operation(2));
        $this->assertSame(1001, InvoiceLine::where('learner_id', $this->schoolA->ids['asha'])->firstOrFail()->amount_cents);
    }

    public function test_gaps_and_future_overlaps_are_explicit_exceptions_not_guessed_school_departures(): void
    {
        $this->families();
        $this->policy();
        Enrolment::where('learner_id', $this->schoolA->ids['asha'])->update(['ends_on' => '2026-06-30']);
        $next = Enrolment::create(['learner_id' => $this->schoolA->ids['asha'], 'session_year_id' => $this->year, 'class_section_id' => $this->schoolA->ids['section_c'], 'starts_on' => '2026-07-02', 'ends_on' => null, 'legacy_dates' => false, 'reason' => 'Synthetic school attendance gap']);
        $run = $this->prepare();
        $this->assertArrayHasKey('Multiple school attendance periods need a reviewed charge', $run->snapshot['exceptions']);
        $next->update(['starts_on' => '2026-06-30']);
        $run = $this->prepare();
        $this->assertArrayHasKey('Overlapping enrolments', $run->snapshot['exceptions']);
        $this->refuses(fn () => app(BillRuns::class)->approve($this->admin, $run->id, $this->operation(1)), 422);
        $this->assertSame(0, Invoice::count());
    }

    private function policyInput(array $changes = []): array
    {
        return array_replace(['operation_id' => (string) Str::uuid(), 'revision' => 0, 'session_year_id' => $this->year, 'join_basis' => 'daily', 'leaver_basis' => 'daily', 'installments' => 'preserve_dates'], $changes);
    }

    private function policy(array $changes = []): FeeLifecyclePolicy
    {
        return app(FeeLifecyclePolicies::class)->save($this->admin, $this->policyInput($changes));
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
