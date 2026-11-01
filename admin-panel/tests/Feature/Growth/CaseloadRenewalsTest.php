<?php

declare(strict_types=1);

namespace Tests\Feature\Growth;

use App\Models\Academics\AcademicLeadership;
use App\Models\Academics\GradeLevel;
use App\Models\ClassSchool;
use App\Models\Growth\RiskCaseload;
use App\Models\Growth\RiskObservation;
use App\Models\People\Enrolment;
use App\Models\Platform\SchoolModule;
use App\Models\SessionYear;
use App\Models\Settings;
use App\Models\User;
use App\Services\Growth\CaseloadRenewals;
use App\Services\Growth\Interventions;
use App\Services\Growth\RiskAuthority;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use App\Services\Platform\Tenancy\SchoolRoles;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Tests\Concerns\UsesMasteryScenario;
use Tests\TestCase;

/**
 * Caseload renewals (Action Plan 1.20): last year's open risk caseload
 * grants offered for renewal into the new year, to caseload administrators
 * only. engineering/specs/growth/caseload-renewals.md.
 */
class CaseloadRenewalsTest extends TestCase
{
    use UsesMasteryScenario;

    private SessionYear $next;

    /** @var array<string, int> */
    private array $cases = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpMasteryScenario();
        Bus::fake();
        SchoolModule::updateOrCreate(['module' => 'early_warning'], ['in_package' => true, 'enabled' => true]);
        app(ModuleGate::class)->forget();
        $ids = $this->schoolA->ids;
        // Tara is a teacher and a counsellor; Theo is only a teacher.
        $this->actor()->assignRole(SchoolRoles::COUNSELLOR);
        ClassSchool::whereKey($ids['class_c'])->update(['grade_level_id' => GradeLevel::where('code', '8')->value('id')]);
        $this->next = SessionYear::create(['name' => 'Next year', 'start_date' => '2027-01-01', 'end_date' => '2027-12-31']);
        $grant = fn (string $learner, string $person, string $ends, bool $revoked = false) => (int) RiskCaseload::create([
            'learner_id' => $ids[$learner], 'session_year_id' => $this->year, 'professional_id' => $ids[$person.'_user'],
            'starts_on' => '2026-02-01', 'ends_on' => $ends, 'revoked_at' => $revoked ? now() : null, 'revision' => 1])->id;
        $this->cases = [
            'asha' => $grant('asha', 'tara', '2026-12-31'),
            'asha_ended' => $grant('asha', 'theo', '2026-12-31', true),
            'cara' => $grant('cara', 'tara', '2026-12-31'),
            'ben_expired' => $grant('ben', 'tara', '2026-06-30'),
        ];
        // The new year has started and is current; everyone has a place in it.
        $this->travelTo(now()->setDate(2027, 1, 11));
        Settings::updateOrCreate(['type' => 'session_year'], ['message' => (string) $this->next->id]);
        foreach (['asha' => 'section_a', 'ben' => 'section_a', 'cara' => 'section_c'] as $learner => $room) {
            Enrolment::create(['learner_id' => $ids[$learner], 'session_year_id' => $this->next->id, 'class_section_id' => $ids[$room],
                'starts_on' => '2027-01-01', 'ends_on' => '2027-12-31', 'reason' => 'Synthetic new year']);
        }
        AcademicLeadership::create(['user_id' => $this->superAdmin()->id, 'kind' => 'principal', 'grade_level_id' => null,
            'session_year_id' => $this->next->id, 'starts_on' => '2027-01-01', 'ends_on' => '2027-12-31', 'reason' => 'Synthetic principal']);
        $this->actingAs($this->superAdmin());
    }

    private function denied(callable $call, int $expected = 403): void
    {
        try {
            $call();
            $this->fail('Caseload renewal must be refused.');
        } catch (HttpExceptionInterface $e) {
            $this->assertSame($expected, $e->getStatusCode());
        } catch (ModelNotFoundException) {
            $this->assertSame($expected, 404);
        }
    }

    private function nextYearGrants(): int
    {
        return RiskCaseload::where('session_year_id', $this->next->id)->count();
    }

    private function identity(): array
    {
        return ['expected_account_id' => $this->superAdmin()->id, 'expected_school_id' => app(CurrentSchool::class)->id(), 'complete' => 1];
    }

    public function test_open_cases_are_offered_and_renewed_into_the_new_year_and_closed_ones_never(): void
    {
        $renewals = app(CaseloadRenewals::class);
        $plan = $renewals->plan($this->superAdmin(), $this->next);

        $this->assertSame($this->year, (int) $plan['from']->id);
        $this->assertSame([$this->cases['asha'], $this->cases['cara']], array_column($plan['cases'], 'id'), 'ended and expired grants are not offered');
        $this->assertTrue($plan['cases'][0]['still_eligible']);
        $this->assertSame(0, $plan['unplaced']);
        $this->assertSame(['2027-01-11', '2027-12-31'], [$plan['starts_on'], $plan['ends_on']]);

        $done = $renewals->apply($this->superAdmin(), $this->next, [$this->cases['asha'] => $this->actor()->id, $this->cases['cara'] => ''], $plan['fingerprint']);

        $this->assertSame(['renewed' => 1, 'reassigned' => 0], $done);
        $row = RiskCaseload::where('session_year_id', $this->next->id)->sole();
        $this->assertSame([$this->schoolA->ids['asha'], $this->actor()->id, '2027-01-11', '2027-12-31', null],
            [(int) $row->learner_id, (int) $row->professional_id, $row->starts_on->toDateString(), $row->ends_on->toDateString(), $row->revoked_at]);
        // Tara keeps Asha's case; nobody else gains it, and the ended grant stays ended.
        app(RiskAuthority::class)->scope($this->actor(), $this->schoolA->ids['asha'], $this->next->id, (int) GradeLevel::where('code', '7')->value('id'));
        $this->actor('theo')->assignRole(SchoolRoles::COUNSELLOR);
        $this->denied(fn () => app(RiskAuthority::class)->scope($this->actor('theo'), $this->schoolA->ids['asha'], $this->next->id, (int) GradeLevel::where('code', '7')->value('id')), 404);

        // The same page again renews nothing; the new page shows Asha as done.
        try {
            $renewals->apply($this->superAdmin(), $this->next, [$this->cases['asha'] => $this->actor()->id], $plan['fingerprint']);
            $this->fail('Renewed twice');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('fingerprint', $e->errors());
        }
        $this->assertSame(1, $this->nextYearGrants());
        $again = $renewals->plan($this->superAdmin(), $this->next);
        $this->assertNotSame([], collect($again['cases'])->firstWhere('id', $this->cases['asha'])['renewed']);
    }

    public function test_a_professional_who_lost_access_is_not_kept_and_the_case_can_be_reassigned(): void
    {
        $this->actor()->syncRoles([SchoolRoles::TEACHER]);
        $this->actor('theo')->assignRole(SchoolRoles::COUNSELLOR);
        $plan = app(CaseloadRenewals::class)->plan($this->superAdmin(), $this->next);

        $this->assertFalse($plan['cases'][0]['still_eligible']);
        $this->assertSame([$this->actor('theo')->id], array_keys($plan['professionals']));
        try {
            app(CaseloadRenewals::class)->apply($this->superAdmin(), $this->next, [$this->cases['asha'] => $this->actor()->id], $plan['fingerprint']);
            $this->fail('Renewed to someone without pastoral access');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('cases', $e->errors());
        }
        $this->assertSame(0, $this->nextYearGrants());

        $done = app(CaseloadRenewals::class)->apply($this->superAdmin(), $this->next, [$this->cases['asha'] => $this->actor('theo')->id], $plan['fingerprint']);
        $this->assertSame(['renewed' => 0, 'reassigned' => 1], $done);
        $this->assertSame([$this->actor('theo')->id], RiskCaseload::where('session_year_id', $this->next->id)->pluck('professional_id')->map(fn ($id) => (int) $id)->all());
    }

    public function test_learners_with_no_place_in_the_new_year_are_not_offered(): void
    {
        Enrolment::where('learner_id', $this->schoolA->ids['cara'])->where('session_year_id', $this->next->id)->delete();
        $plan = app(CaseloadRenewals::class)->plan($this->superAdmin(), $this->next);

        $this->assertSame([$this->cases['asha']], array_column($plan['cases'], 'id'));
        $this->assertSame(1, $plan['unplaced']);
        $this->expectException(ValidationException::class);
        app(CaseloadRenewals::class)->apply($this->superAdmin(), $this->next, [$this->cases['cara'] => $this->actor()->id], $plan['fingerprint']);
    }

    public function test_the_panel_offers_and_renews_caseloads_for_the_principal(): void
    {
        $this->get(route('risk-interventions.index', ['mode' => 'assignments']))->assertOk()->assertSee('Renew caseloads for a new year');
        $page = $this->get(route('risk-interventions.renewals', ['year' => $this->next->id]))->assertOk()
            ->assertSee('Renew caseloads for Next year')->assertSee(User::findOrFail($this->schoolA->ids['asha_user'])->full_name)
            ->assertHeader('Cache-Control', 'no-store, private');
        preg_match('/name="fingerprint" value="([a-f0-9]{64})"/', $page->getContent(), $match);
        $this->assertNotEmpty($match);

        $this->post(route('risk-interventions.renewals.store'), $this->identity() + ['year' => $this->next->id, 'fingerprint' => $match[1],
            'cases' => [$this->cases['asha'] => $this->actor()->id, $this->cases['cara'] => $this->actor()->id]])
            ->assertRedirect(route('risk-interventions.renewals', ['year' => $this->next->id]))->assertSessionHas('success');
        $this->assertSame(2, $this->nextYearGrants());

        // Bound to the account and school, and nothing in the query string.
        $this->post(route('risk-interventions.renewals.store', ['year' => $this->next->id]), $this->identity())->assertStatus(422);
        $this->post(route('risk-interventions.renewals.store'), ['year' => $this->next->id, 'fingerprint' => $match[1],
            'expected_account_id' => $this->actor()->id, 'expected_school_id' => 1])->assertForbidden();
        $this->assertSame(2, $this->nextYearGrants());
    }

    public function test_staff_who_do_not_administer_caseloads_see_nothing_and_cannot_renew(): void
    {
        $plan = app(CaseloadRenewals::class)->plan($this->superAdmin(), $this->next);
        $asha = User::findOrFail($this->schoolA->ids['asha_user'])->full_name;
        // Theo has no pastoral access; Tara holds the case but does not administer caseloads.
        foreach (['theo', 'tara'] as $name) {
            $this->denied(fn () => app(CaseloadRenewals::class)->plan($this->actor($name), $this->next));
            $this->denied(fn () => app(CaseloadRenewals::class)->apply($this->actor($name), $this->next, [$this->cases['asha'] => $this->actor()->id], $plan['fingerprint']));
            $this->actingAs($this->actor($name));
            $this->get(route('risk-interventions.renewals', ['year' => $this->next->id]))->assertForbidden()->assertDontSee($asha);
            $this->post(route('risk-interventions.renewals.store'), ['year' => $this->next->id, 'fingerprint' => $plan['fingerprint'],
                'expected_account_id' => $this->actor($name)->id, 'expected_school_id' => app(CurrentSchool::class)->id(), 'complete' => 1,
                'cases' => [$this->cases['asha'] => $this->actor()->id]])->assertForbidden();
        }
        $this->assertSame(0, $this->nextYearGrants());
    }

    public function test_a_grade_head_renews_only_the_cases_of_their_grade(): void
    {
        $grade = (int) GradeLevel::where('code', '7')->value('id');
        AcademicLeadership::create(['user_id' => $this->actor('theo')->id, 'kind' => 'grade_head', 'grade_level_id' => $grade,
            'session_year_id' => $this->next->id, 'starts_on' => '2027-01-01', 'ends_on' => '2027-12-31', 'reason' => 'Synthetic grade head']);
        // Last year's grade comes from the dated early-warning history, as for any retained year.
        RiskObservation::create(['learner_id' => $this->schoolA->ids['asha'], 'grade_level_id' => $grade, 'session_year_id' => $this->year,
            'as_of' => '2026-11-30', 'window_start' => '2026-11-01', 'source' => 'academic', 'score' => null, 'signal_count' => null,
            'reason_code' => 'synthetic', 'evidence' => [], 'fingerprint' => str_repeat('a', 64), 'created_at' => now()]);

        $plan = app(CaseloadRenewals::class)->plan($this->actor('theo'), $this->next);
        $this->assertSame([$this->cases['asha']], array_column($plan['cases'], 'id'), 'Cara is in grade 8');
        try {
            app(CaseloadRenewals::class)->apply($this->actor('theo'), $this->next, [$this->cases['cara'] => $this->actor()->id], $plan['fingerprint']);
            $this->fail('A grade head renewed another grade\'s case');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('cases', $e->errors());
        }
        $this->assertSame(0, $this->nextYearGrants());
        app(CaseloadRenewals::class)->apply($this->actor('theo'), $this->next, [$this->cases['asha'] => $this->actor()->id], $plan['fingerprint']);
        $this->assertSame(1, $this->nextYearGrants());
        // Renewing gives the grade head no caseload of their own.
        $this->assertSame(0, RiskCaseload::where('session_year_id', $this->next->id)->where('professional_id', $this->actor('theo')->id)->count());
    }

    /** Still last year (10 December): the new year's enrolments start on 1 January. Theo heads grade 7 now. */
    private function beforeTheNewYear(bool $appointedNextYear): void
    {
        $this->travelTo(now()->setDate(2026, 12, 10));
        Settings::updateOrCreate(['type' => 'session_year'], ['message' => (string) $this->year]);
        $grade = (int) GradeLevel::where('code', '7')->value('id');
        AcademicLeadership::create(['user_id' => $this->actor('theo')->id, 'kind' => 'grade_head', 'grade_level_id' => $grade,
            'session_year_id' => $this->year, 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Synthetic grade head']);
        if ($appointedNextYear) {
            AcademicLeadership::create(['user_id' => $this->actor('theo')->id, 'kind' => 'grade_head', 'grade_level_id' => $grade,
                'session_year_id' => $this->next->id, 'starts_on' => '2027-01-01', 'ends_on' => '2027-12-31', 'reason' => 'Synthetic grade head next year']);
        }
    }

    public function test_a_grade_head_renews_before_the_new_year_starts_from_its_future_enrolments(): void
    {
        $this->beforeTheNewYear(true);

        $plan = app(CaseloadRenewals::class)->plan($this->actor('theo'), $this->next);
        $this->assertSame([$this->cases['asha']], array_column($plan['cases'], 'id'), 'Asha is in grade 7 on the new year\'s first day');
        $this->assertSame('2027-01-01', $plan['starts_on']);
        app(CaseloadRenewals::class)->apply($this->actor('theo'), $this->next, [$this->cases['asha'] => $this->actor()->id], $plan['fingerprint']);
        $this->assertSame('2027-01-01', RiskCaseload::where('session_year_id', $this->next->id)->sole()->starts_on->toDateString());

        // The grade still decides: moved to grade 8 for the new year, Asha is not the grade 7 head's.
        Enrolment::where('learner_id', $this->schoolA->ids['asha'])->where('session_year_id', $this->next->id)->update(['class_section_id' => $this->schoolA->ids['section_c']]);
        RiskCaseload::where('session_year_id', $this->next->id)->delete();
        $this->assertSame([], app(CaseloadRenewals::class)->plan($this->actor('theo'), $this->next)['cases']);
        $this->denied(fn () => app(Interventions::class)->assign($this->actor('theo'), $this->schoolA->ids['asha'], $this->next->id, $this->actor()->id,
            ['starts_on' => '2027-01-01', 'ends_on' => '2027-12-31', 'expected_revision' => 0], '2027-01-01'));
    }

    public function test_a_grade_head_whose_appointment_ends_with_this_year_renews_nothing_into_the_next(): void
    {
        $this->beforeTheNewYear(false);

        $plan = app(CaseloadRenewals::class)->plan($this->actor('theo'), $this->next);
        $this->assertSame([], $plan['cases'], 'the appointment ends on 31 December');
        try {
            app(CaseloadRenewals::class)->apply($this->actor('theo'), $this->next, [$this->cases['asha'] => $this->actor()->id], $plan['fingerprint']);
            $this->fail('Renewed past the end of the appointment');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('cases', $e->errors());
        }
        $this->denied(fn () => app(Interventions::class)->assign($this->actor('theo'), $this->schoolA->ids['asha'], $this->next->id, $this->actor()->id,
            ['starts_on' => '2027-01-01', 'ends_on' => '2027-12-31', 'expected_revision' => 0], '2027-01-01'));
        $this->assertSame(0, $this->nextYearGrants());
    }

    public function test_the_existing_assignment_endpoints_still_refuse_a_grade_head_for_a_year_to_come(): void
    {
        // Appointed for both years: the renewal page works, the ordinary assignment paths do not change.
        $this->beforeTheNewYear(true);
        $asha = $this->schoolA->ids['asha'];
        $dates = ['starts_on' => '2027-01-01', 'ends_on' => '2027-12-31', 'expected_revision' => 0];
        $this->actingAs($this->actor('theo'));
        $this->post(route('risk-interventions.assign', ['learner' => $asha]), $dates + ['operation_id' => (string) Str::uuid(),
            'expected_account_id' => $this->actor('theo')->id, 'expected_school_id' => app(CurrentSchool::class)->id(),
            'session_year_id' => $this->next->id, 'mode' => 'assignments', 'professional_id' => $this->actor()->id])->assertNotFound();
        $base = '/api/v2/growth/risk/learners/'.$asha;
        $this->putJson($base.'/assignments/'.$this->actor()->id, $dates + ['operation_id' => (string) Str::uuid(), 'session_year_id' => $this->next->id])->assertNotFound();
        $this->getJson($base.'/assignments?session_year_id='.$this->next->id)->assertNotFound();
        $this->getJson($base.'/professionals?session_year_id='.$this->next->id)->assertNotFound();
        $this->denied(fn () => app(Interventions::class)->assign($this->actor('theo'), $asha, $this->next->id, $this->actor()->id, $dates), 404);
        $this->assertSame(0, $this->nextYearGrants());
    }

    public function test_a_grant_outside_its_school_year_is_refused(): void
    {
        foreach ([['2026-12-31', '2027-12-31'], ['2027-01-11', '2028-01-31']] as [$start, $end]) {
            try {
                app(Interventions::class)->assign($this->superAdmin(), $this->schoolA->ids['asha'], $this->next->id, $this->actor()->id,
                    ['starts_on' => $start, 'ends_on' => $end, 'expected_revision' => 0]);
                $this->fail('A grant ran outside its year');
            } catch (ValidationException $e) {
                $this->assertArrayHasKey('starts_on', $e->errors());
            }
        }
        $this->assertSame(0, $this->nextYearGrants());
    }

    public function test_a_lapsed_grant_is_offered_but_not_kept_by_default(): void
    {
        // 11 January: last year's grants ended on 31 December.
        $page = $this->get(route('risk-interventions.renewals', ['year' => $this->next->id]))->assertOk()->assertSee('ended on 2026-12-31');
        $this->assertTrue(app(CaseloadRenewals::class)->plan($this->superAdmin(), $this->next)['cases'][0]['lapsed']);
        preg_match('/<select id="renewal-case-'.$this->cases['asha'].'".*?<\/select>/s', $page->getContent(), $select);
        $this->assertStringNotContainsString('selected', $select[0], 'Do not renew, the first choice, is the default');

        // Before the year ends the same grant is still running and is kept by default.
        $this->beforeTheNewYear(true);
        $this->actingAs($this->actor('theo'));
        $page = $this->get(route('risk-interventions.renewals', ['year' => $this->next->id]))->assertOk()->assertDontSee('ended on 2026-12-31');
        preg_match('/<select id="renewal-case-'.$this->cases['asha'].'".*?<\/select>/s', $page->getContent(), $select);
        $this->assertMatchesRegularExpression('/<option value="'.$this->actor()->id.'"\s+selected/', $select[0]);
    }

    public function test_the_page_defaults_to_the_year_being_prepared(): void
    {
        $this->beforeTheNewYear(true);
        $this->actingAs($this->actor('theo'));
        $this->get(route('risk-interventions.renewals'))->assertOk()->assertSee('Renew caseloads for Next year');
    }

    public function test_a_deleted_year_just_before_is_not_replaced_by_an_older_one(): void
    {
        $older = SessionYear::create(['name' => 'Older year', 'start_date' => '2025-01-01', 'end_date' => '2025-12-31']);
        RiskCaseload::create(['learner_id' => $this->schoolA->ids['asha'], 'session_year_id' => $older->id, 'professional_id' => $this->actor()->id,
            'starts_on' => '2025-02-01', 'ends_on' => '2025-12-31', 'revision' => 1]);
        SessionYear::whereKey($this->year)->delete();

        try {
            app(CaseloadRenewals::class)->plan($this->superAdmin(), $this->next);
            $this->fail('An older year stood in for the deleted one');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('year', $e->errors());
        }
    }

    public function test_a_renewal_cut_short_in_transit_is_refused_and_nothing_is_renewed(): void
    {
        $page = $this->get(route('risk-interventions.renewals', ['year' => $this->next->id]))->assertOk();
        preg_match('/name="fingerprint" value="([a-f0-9]{64})"/', $page->getContent(), $match);
        $this->assertGreaterThan(strrpos($page->getContent(), 'name="cases['), strpos($page->getContent(), 'name="complete"'), 'the marker is the last field');

        // Past the server's input limit the trailing fields, the marker first, are dropped.
        $this->post(route('risk-interventions.renewals.store'), array_diff_key($this->identity(), ['complete' => true]) + ['year' => $this->next->id,
            'fingerprint' => $match[1], 'cases' => [$this->cases['asha'] => $this->actor()->id]])->assertSessionHasErrors('complete');
        $this->assertSame(0, $this->nextYearGrants());
    }

    public function test_another_schools_cases_professionals_and_years_are_refused(): void
    {
        $b = $this->schoolB->ids;
        $foreign = app(CurrentSchool::class)->run(self::SCHOOL_B_ID, fn () => RiskCaseload::create([
            'learner_id' => $b['asha'], 'session_year_id' => $b['session_year'], 'professional_id' => $b['tara_user'],
            'starts_on' => '2026-02-01', 'ends_on' => '2027-12-31', 'revision' => 1]));
        $before = DB::table('risk_caseloads')->count();
        $plan = app(CaseloadRenewals::class)->plan($this->superAdmin(), $this->next);
        $this->assertNotContains((int) $foreign->id, array_column($plan['cases'], 'id'));

        foreach ([[$foreign->id => $this->actor()->id], [$this->cases['asha'] => $b['tara_user']]] as $choices) {
            try {
                app(CaseloadRenewals::class)->apply($this->superAdmin(), $this->next, $choices, $plan['fingerprint']);
                $this->fail('Another school\'s case or professional was renewed');
            } catch (ValidationException $e) {
                $this->assertArrayHasKey('cases', $e->errors());
            }
        }
        $this->get(route('risk-interventions.renewals', ['year' => $b['session_year']]))->assertNotFound();
        $this->post(route('risk-interventions.renewals.store'), $this->identity() + ['year' => $b['session_year'], 'fingerprint' => $plan['fingerprint']])->assertNotFound();
        $this->post(route('risk-interventions.renewals.store'), $this->identity() + ['year' => $this->next->id, 'fingerprint' => $plan['fingerprint'],
            'cases' => [$foreign->id => $this->actor()->id]])->assertSessionHasErrors('cases');
        $this->assertSame($before, DB::table('risk_caseloads')->count(), 'Neither school gains a grant');
        $this->assertSame(1, (int) DB::table('risk_caseloads')->where('id', $foreign->id)->value('revision'));
    }

    public function test_a_page_shown_before_a_grant_changed_is_refused(): void
    {
        $plan = app(CaseloadRenewals::class)->plan($this->superAdmin(), $this->next);
        // Asha's grant is ended after the page was shown.
        app(Interventions::class)->revoke($this->superAdmin(), $this->schoolA->ids['asha'], $this->year, $this->actor()->id, 1);

        try {
            app(CaseloadRenewals::class)->apply($this->superAdmin(), $this->next, [$this->cases['cara'] => $this->actor()->id], $plan['fingerprint']);
            $this->fail('A stale page renewed a case');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('fingerprint', $e->errors());
        }
        $this->assertSame(0, $this->nextYearGrants());
        $this->post(route('risk-interventions.renewals.store'), $this->identity() + ['year' => $this->next->id, 'fingerprint' => $plan['fingerprint'],
            'cases' => [$this->cases['cara'] => $this->actor()->id]])->assertSessionHasErrors('fingerprint');
        $this->assertSame(0, $this->nextYearGrants());
        $this->assertSame([$this->cases['cara']], array_column(app(CaseloadRenewals::class)->plan($this->superAdmin(), $this->next)['cases'], 'id'), 'the ended grant is no longer offered');
    }
}
