<?php

declare(strict_types=1);

namespace Tests\Feature\Money;

use App\Models\ClassSchool;
use App\Models\Money\FeePlan;
use App\Models\People\Enrolment;
use App\Models\Platform\PlatformStaffIdentity;
use App\Models\Platform\SchoolMembership;
use App\Models\SessionYear;
use App\Models\User;
use App\Services\Money\FeeCalculator;
use App\Services\Money\FeePlans;
use App\Services\Platform\Ledger\AccountType;
use App\Services\Platform\Ledger\LedgerService;
use App\Services\Platform\Ledger\Money;
use App\Services\Platform\Ledger\Posting;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Tests\Concerns\UsesTwoSchools;
use Tests\TestCase;

class FeePlanTest extends TestCase
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
        Enrolment::where('session_year_id', $this->year)->delete();
        foreach (['asha' => 'a', 'ben' => 'a', 'cara' => 'c'] as $name => $section) {
            Enrolment::create(['learner_id' => $this->schoolA->ids[$name], 'class_section_id' => $this->schoolA->ids['section_'.$section],
                'session_year_id' => $this->year, 'starts_on' => '2026-01-01', 'ends_on' => null, 'reason' => 'Synthetic billing fixture', 'legacy_dates' => false]);
        }
    }

    public function test_plan_saves_exact_cents_and_audits_changes_without_posting_money(): void
    {
        $plan = $this->save(['amount' => '12000.07']);
        $this->assertSame(1200007, $plan->amount_cents);
        $this->assertSame('ZAR', $plan->currency);
        $changed = $this->save(['name' => 'Revised tuition', 'revision' => 1, 'amount' => '12500.00'], $plan->id);
        $this->assertSame(2, $changed->revision);
        $this->assertSame(2, DB::table('audit_log')->where('action', 'money.fee_plan_saved')->count());
        $this->assertSame(0, DB::table('ledger_entries')->count());
    }

    public function test_duplicate_operation_is_durable_and_changed_input_or_stale_revision_conflicts(): void
    {
        $input = $this->input();
        $first = app(FeePlans::class)->save($this->admin, $input);
        $this->travel(3)->days();
        $this->assertSame($first->id, app(FeePlans::class)->save($this->admin, $input)->id);
        $this->refuses(fn () => app(FeePlans::class)->save($this->admin, [...$input, 'amount' => '99.00']), 409);
        $this->refuses(fn () => $this->save(['revision' => 0], $first->id), 409);
        $this->assertSame(1, FeePlan::count());
    }

    public function test_new_year_copy_rounds_uplift_in_integer_cents_and_stays_draft(): void
    {
        $source = $this->save(['amount' => '10.01']);
        $year = SessionYear::create(['name' => 'Next synthetic year', 'start_date' => '2027-01-01', 'end_date' => '2027-12-31']);
        $input = ['operation_id' => (string) Str::uuid(), 'revision' => 1, 'session_year_id' => $year->id, 'name' => 'Next tuition', 'uplift_percent' => '5.25'];
        $copy = app(FeePlans::class)->copy($this->admin, $source->id, $input);
        $this->assertSame(1054, $copy->amount_cents);
        $this->assertSame('draft', $copy->state);
        $this->assertSame(1001, $source->fresh()->amount_cents);
        $this->assertSame($copy->id, app(FeePlans::class)->copy($this->admin, $source->id, $input)->id);
        $this->refuses(fn () => app(FeePlans::class)->copy($this->admin, $source->id, [...$input, 'operation_id' => (string) Str::uuid(), 'session_year_id' => $this->year]), 422);
    }

    public function test_preview_uses_dated_enrolments_and_exact_annual_rates_without_any_write(): void
    {
        $this->save(['scope' => 'class', 'target_id' => $this->schoolA->ids['class_a'], 'amount' => '10.01']);
        // The legacy current class disagrees: the dated enrolment remains authoritative.
        DB::table('students')->where('id', $this->schoolA->ids['asha'])->update(['class_id' => $this->schoolA->ids['class_c']]);
        $before = DB::table('audit_log')->count();
        $result = $this->preview();
        $this->assertSame(3, $result['learners']);
        $this->assertSame(2, $result['matched']);
        $this->assertSame(['ZAR' => 2002], $result['totals']);
        $this->assertSame(['No active fee plan' => 1], $result['exceptions']);
        $this->assertSame($before, DB::table('audit_log')->count());
        $this->assertSame(0, DB::table('ledger_entries')->count());
        $this->assertSame(0, DB::table('ledger_accounts')->count());
    }

    public function test_grade_scope_uses_canonical_grade_mapping(): void
    {
        $grade = (int) DB::table('grade_levels')->orderBy('id')->value('id');
        ClassSchool::whereKey($this->schoolA->ids['class_a'])->update(['grade_level_id' => $grade]);
        $this->save(['scope' => 'grade', 'target_id' => $grade]);
        $this->assertSame(2, $this->preview()['matched']);
    }

    public function test_preview_reports_overlaps_and_ignores_draft_archived_and_other_year_plans(): void
    {
        $this->save(['state' => 'draft']);
        $this->save(['state' => 'archived']);
        $this->assertSame(['No active fee plan' => 3], $this->preview()['exceptions']);
        $this->save();
        $this->save(['scope' => 'class', 'target_id' => $this->schoolA->ids['class_a']]);
        $result = $this->preview();
        $this->assertSame(['Multiple active fee plans' => 2], $result['exceptions']);
        $this->assertSame(1, $result['matched']);
    }

    public function test_preview_flags_legacy_dates_partial_year_and_overlapping_enrolments(): void
    {
        $this->save();
        Enrolment::where('learner_id', $this->schoolA->ids['asha'])->update(['legacy_dates' => true]);
        Enrolment::where('learner_id', $this->schoolA->ids['ben'])->update(['starts_on' => '2026-06-01']);
        Enrolment::where('learner_id', $this->schoolA->ids['cara'])->update(['ends_on' => '2026-07-01']);
        $result = $this->preview();
        $this->assertSame(0, $result['matched']);
        $this->assertSame([], $result['totals']);
        $this->assertSame(1, $result['exceptions']['Confirm imported enrolment dates']);
        $this->assertSame(2, $result['exceptions']['Pro-rata or leaver rule required']);
        $enrolment = Enrolment::where('learner_id', $this->schoolA->ids['asha'])->firstOrFail();
        $enrolment->replicate()->save();
        $this->assertSame(1, $this->preview()['exceptions']['Overlapping enrolments']);
    }

    public function test_preview_excludes_future_and_ended_enrolments_and_rejects_outside_year_dates(): void
    {
        $this->save();
        Enrolment::where('learner_id', $this->schoolA->ids['asha'])->update(['starts_on' => '2026-07-01']);
        Enrolment::where('learner_id', $this->schoolA->ids['ben'])->update(['ends_on' => '2026-06-14']);
        $this->assertSame(1, $this->preview()['learners']);
        $this->refuses(fn () => app(FeeCalculator::class)->preview($this->admin, $this->year, '2027-01-01'), 422);
    }

    public function test_foreign_plan_class_and_year_are_unavailable_and_preview_has_no_foreign_names(): void
    {
        $this->refuses(fn () => $this->save(['scope' => 'class', 'target_id' => $this->schoolB->ids['class_a']]), 404);
        $this->refuses(fn () => $this->save(['session_year_id' => $this->schoolB->ids['session_year']]), 404);
        $foreignId = app(CurrentSchool::class)->run(2, fn () => FeePlan::create(['session_year_id' => $this->schoolB->ids['session_year'], 'name' => 'Baobab private fee',
            'amount_cents' => 900, 'currency' => 'ZAR', 'scope' => 'all', 'target_id' => null, 'state' => 'active', 'revision' => 1])->id);
        $this->refuses(fn () => $this->save(['revision' => 1], $foreignId), 404);
        $this->assertStringNotContainsString('Baobab', json_encode($this->preview(), JSON_THROW_ON_ERROR));
        $this->actingAs($this->admin)->get('/fee-plans/'.$foreignId.'/edit')->assertNotFound();
    }

    public function test_learner_parent_teacher_and_revoked_membership_cannot_use_finance_planning(): void
    {
        foreach (['asha', 'farid', 'tara'] as $name) {
            $actor = User::findOrFail($this->schoolA->ids[$name.'_user']);
            $this->refuses(fn () => app(FeePlans::class)->save($actor, $this->input()), 403);
            $this->refuses(fn () => app(FeeCalculator::class)->preview($actor, $this->year, '2026-06-15'), 403);
            $response = $this->actingAs($actor)->get('/fee-plans');
            if ($name === 'tara') {
                $response->assertForbidden()->assertHeader('Cache-Control', 'no-store, private');
            } else {
                $response->assertRedirect(); // Existing panel Role middleware excludes app-only profiles.
            }
        }
        $input = $this->input();
        app(FeePlans::class)->save($this->admin, $input);
        SchoolMembership::where('school_id', 1)->where('user_id', $this->admin->id)->update(['status' => 'suspended']);
        $this->refuses(fn () => app(FeePlans::class)->save($this->admin, $input), 403);
    }

    public function test_disabled_fees_refuse_new_changes_and_retained_money_allows_read_only_plans(): void
    {
        $plan = $this->save();
        $ledger = app(LedgerService::class);
        $cash = $ledger->openAccount(AccountType::CashDesk, 'synthetic:cash', 'ZAR');
        $family = $ledger->openAccount(AccountType::Family, 'synthetic:family', 'ZAR');
        $ledger->post('synthetic:receipt', 'synthetic:receipt', 'Synthetic receipt', [Posting::debit($cash, Money::ofCents(100)), Posting::credit($family, Money::ofCents(100))]);
        app(ModuleGate::class)->change('fees', false, $this->admin);
        $this->refuses(fn () => $this->save(['revision' => 1], $plan->id), 403);
        $this->assertSame(3, $this->preview()['matched']);
        $this->actingAs($this->admin)->get('/fee-plans')->assertOk()->assertSee('read-only');
    }

    public function test_invalid_values_unsupported_rules_and_negative_uplift_are_not_silently_accepted(): void
    {
        foreach ([['amount' => '-1.00'], ['amount' => '0.001'], ['amount' => '1e5'], ['currency' => 'zar'], ['scope' => 'profile'], ['scope' => 'class', 'target_id' => null], ['scope' => 'all', 'target_id' => 1]] as $change) {
            $this->refuses(fn () => $this->save($change), 422);
        }
        $plan = $this->save();
        $this->refuses(fn () => app(FeePlans::class)->copy($this->admin, $plan->id, ['operation_id' => (string) Str::uuid(), 'revision' => 1,
            'session_year_id' => $this->year, 'name' => 'Invalid uplift', 'uplift_percent' => '-5']), 422);
    }

    public function test_panel_workflow_binds_account_school_and_does_not_flash_private_failures(): void
    {
        $this->actingAs($this->admin)->get('/fee-plans')->assertOk()->assertSee('New fee plan')->assertHeader('Cache-Control', 'no-store, private');
        $this->get('/fee-plans/new')->assertOk()->assertSee('scope_choice');
        $input = $this->input() + ['expected_account_id' => $this->admin->id, 'expected_school_id' => 1];
        $this->post('/fee-plans/new', $input)->assertStatus(303);
        $plan = FeePlan::firstOrFail();
        $this->get('/fee-plans/'.$plan->id.'/edit')->assertOk()->assertSee('Synthetic tuition');
        $this->get('/fee-plans/years/'.$this->year.'/preview?date=2026-06-15')->assertOk()->assertSee('3')->assertSee('not invoices');
        $this->post('/fee-plans/new', [...$input, 'operation_id' => (string) Str::uuid(), 'expected_school_id' => 2])->assertForbidden();
        $this->post('/fee-plans/new', [...$input, 'operation_id' => (string) Str::uuid(), 'expected_account_id' => $this->schoolA->ids['farid_user']])->assertForbidden();
        $this->post('/fee-plans/new', [...$input, 'amount' => 'bad'])->assertStatus(422)->assertSessionMissing('_old_input');
        $this->post('/fee-plans/new?state=active', $input)->assertStatus(422);
    }

    public function test_preview_keeps_currencies_separate_and_foreign_homerooms_unavailable(): void
    {
        $this->save(['scope' => 'class', 'target_id' => $this->schoolA->ids['class_a'], 'amount' => '10.01']);
        $this->save(['scope' => 'class', 'target_id' => $this->schoolA->ids['class_c'], 'amount' => '10.01', 'currency' => 'USD']);
        $this->assertSame(['USD' => 1001, 'ZAR' => 2002], $this->preview()['totals']);
        Enrolment::where('learner_id', $this->schoolA->ids['asha'])->update(['class_section_id' => $this->schoolB->ids['section_a']]);
        $preview = $this->preview();
        $this->assertSame(1, $preview['exceptions']['Homeroom unavailable']);
        $this->assertSame(2, $preview['matched']);
    }

    public function test_preparing_a_future_year_does_not_require_switching_the_header_back(): void
    {
        $year = SessionYear::create(['name' => 'Future fee setup', 'start_date' => '2027-01-01', 'end_date' => '2027-12-31']);
        $input = $this->input(['session_year_id' => $year->id]) + ['expected_account_id' => $this->admin->id, 'expected_school_id' => 1];
        $this->actingAs($this->admin)->withSession(['session_year' => $year->id])->post('/fee-plans/new', $input)->assertStatus(303)->assertSessionMissing('_old_input');
        $this->assertSame((int) $year->id, FeePlan::firstOrFail()->session_year_id);
        $this->assertSame(0, DB::table('ledger_entries')->count());
    }

    public function test_permission_revocation_password_reset_and_support_identity_refuse_receipt_recovery(): void
    {
        $input = $this->input();
        app(FeePlans::class)->save($this->admin, $input);
        $this->admin->forceFill(['password_change_required' => true])->save();
        $this->refuses(fn () => app(FeePlans::class)->save($this->admin, $input), 403);
        $this->admin->forceFill(['password_change_required' => false])->save();
        PlatformStaffIdentity::create(['user_id' => $this->admin->id, 'active' => true]);
        $this->refuses(fn () => app(FeePlans::class)->save($this->admin, $input), 403);
        PlatformStaffIdentity::where('user_id', $this->admin->id)->delete();
        DB::table('role_has_permissions')->where('permission_id', DB::table('permissions')->where('name', 'fee-plans-manage')->value('id'))->delete();
        $this->refuses(fn () => app(FeePlans::class)->save($this->admin, $input), 403);
    }

    public function test_preview_for_1200_learners_batches_queries_and_totals_exactly(): void
    {
        $this->save(['amount' => '10.01']);
        $user = (array) DB::table('users')->where('id', $this->schoolA->ids['asha_user'])->first();
        $student = (array) DB::table('students')->where('id', $this->schoolA->ids['asha'])->first();
        unset($user['id'], $student['id']);
        for ($i = 0; $i < 1197; $i++) {
            $userId = DB::table('users')->insertGetId(array_replace($user, ['email' => 'fee-scale-'.$i.'@synthetic.example.test']));
            $learnerId = DB::table('students')->insertGetId(array_replace($student, ['user_id' => $userId]));
            DB::table('enrolments')->insert(['school_id' => 1, 'learner_id' => $learnerId, 'class_section_id' => $student['class_section_id'],
                'session_year_id' => $this->year, 'starts_on' => '2026-01-01', 'ends_on' => null, 'reason' => 'Synthetic scale preview', 'legacy_dates' => false]);
        }
        DB::flushQueryLog();
        DB::enableQueryLog();
        $started = microtime(true);
        $preview = $this->preview();
        $elapsed = microtime(true) - $started;
        $queries = count(DB::getQueryLog());
        DB::disableQueryLog();
        $this->assertSame(1200, $preview['learners']);
        $this->assertSame(1200, $preview['matched']);
        $this->assertSame(['ZAR' => 1201200], $preview['totals']);
        $this->assertLessThan(80, $queries, 'Preview must batch school/plan/enrolment reads rather than query per learner.');
        file_put_contents(storage_path('logs/fee-preview-scale.json'), json_encode(['learners' => 1200, 'queries' => $queries, 'seconds' => $elapsed], JSON_THROW_ON_ERROR));
    }

    private function input(array $change = []): array
    {
        return array_replace(['operation_id' => (string) Str::uuid(), 'revision' => 0, 'session_year_id' => $this->year,
            'name' => 'Synthetic tuition', 'amount' => '12000.00', 'currency' => 'ZAR', 'scope' => 'all', 'target_id' => null, 'state' => 'active'], $change);
    }

    private function save(array $change = [], ?int $id = null): FeePlan
    {
        return app(FeePlans::class)->save($this->admin, $this->input($change), $id);
    }

    private function preview(): array
    {
        return app(FeeCalculator::class)->preview($this->admin, $this->year, '2026-06-15');
    }

    private function refuses(callable $call, int $status): void
    {
        try {
            $call();
            $this->fail('Expected refusal');
        } catch (ValidationException $error) {
            $this->assertSame(422, $status);
        } catch (ModelNotFoundException $error) {
            $this->assertSame(404, $status);
        } catch (HttpExceptionInterface $error) {
            $this->assertSame($status, $error->getStatusCode());
        }
    }
}
