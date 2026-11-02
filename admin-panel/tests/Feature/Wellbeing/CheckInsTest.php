<?php

declare(strict_types=1);

namespace Tests\Feature\Wellbeing;

use App\Models\Academics\AcademicLeadership;
use App\Models\Academics\TeachingGroup;
use App\Models\Growth\RiskCaseload;
use App\Models\Growth\RiskObservation;
use App\Models\People\Enrolment;
use App\Models\Platform\SchoolModule;
use App\Models\Wellbeing\CheckIn;
use App\Services\Growth\CheckInContext;
use App\Services\Growth\RiskRecompute;
use App\Services\Panel\Navigation;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use App\Services\Platform\Tenancy\SchoolRoles;
use App\Services\Wellbeing\CheckIns;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Tests\Concerns\UsesMasteryScenario;
use Tests\TestCase;

/**
 * Short private wellbeing check-ins (Action Plan 4.11): only the learner's
 * authorised pastoral staff schedule, record or read them, and they feed
 * early warning as context only.
 * engineering/specs/growth/check-ins-and-intervention-library.md.
 */
class CheckInsTest extends TestCase
{
    use UsesMasteryScenario;

    private int $grade;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpMasteryScenario();
        Bus::fake();
        foreach (['early_warning', 'wellbeing'] as $module) {
            SchoolModule::updateOrCreate(['module' => $module], ['in_package' => true, 'enabled' => true]);
        }
        app(ModuleGate::class)->forget();
        $this->grade = (int) TeachingGroup::findOrFail($this->group)->grade_level_id;
        // Tara is Asha's counsellor with a dated risk caseload grant; Theo is only a teacher.
        $this->actor()->assignRole(SchoolRoles::COUNSELLOR);
        $this->grant('asha', 'tara');
        Enrolment::create(['learner_id' => $this->schoolA->ids['ben'], 'class_section_id' => $this->schoolA->ids['section_a'], 'session_year_id' => $this->year,
            'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Synthetic check-in fixture']);
    }

    private function grant(string $learner, string $person): RiskCaseload
    {
        return RiskCaseload::create(['learner_id' => $this->schoolA->ids[$learner], 'session_year_id' => $this->year, 'professional_id' => $this->actor($person)->id,
            'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'revision' => 1]);
    }

    private function denied(callable $call, int $expected): void
    {
        try {
            $call();
            $this->fail('Private check-in access must be refused.');
        } catch (HttpExceptionInterface $e) {
            $this->assertSame($expected, $e->getStatusCode());
        } catch (ModelNotFoundException) {
            $this->assertSame($expected, 404);
        }
    }

    private function identity(string $name = 'tara'): array
    {
        $user = $name === 'principal' ? $this->superAdmin() : $this->actor($name);

        return ['expected_account_id' => $user->id, 'expected_school_id' => app(CurrentSchool::class)->id()];
    }

    /** An answered check-in for Asha with a private note. */
    private function answered(int $scale = 2, string $note = 'Breaks are lonely at the moment'): int
    {
        $id = app(CheckIns::class)->schedule($this->actor(), ['learner_ids' => [$this->schoolA->ids['asha']], 'prompt' => 'week', 'due_on' => '2026-02-10'])['ids'][0];
        app(CheckIns::class)->record($this->actor(), $id, ['scale' => $scale, 'note' => $note, 'expected_revision' => 1]);

        return $id;
    }

    public function test_a_pastoral_professional_schedules_records_and_reads_a_private_check_in(): void
    {
        $this->actingAs($this->actor())->get(route('wellbeing-check-ins.index'))->assertOk()->assertSee('Schedule a check-in')
            ->assertSee($this->actor('asha')->full_name)->assertDontSee($this->actor('ben')->full_name)->assertHeader('Cache-Control', 'no-store, private');
        $this->post(route('wellbeing-check-ins.schedule'), $this->identity() + ['learner_ids' => [$this->schoolA->ids['asha']], 'prompt' => 'friends', 'due_on' => '2026-02-10'])
            ->assertRedirect(route('wellbeing-check-ins.index'))->assertSessionHas('success');
        $row = CheckIn::sole();
        $this->assertSame(['scheduled', 'friends', null], [$row->status, $row->prompt, $row->group_id]);

        $this->post(route('wellbeing-check-ins.record', ['checkIn' => $row->id]), $this->identity() + ['expected_revision' => 1, 'scale' => 2, 'note' => '<i>Breaks are lonely</i>'])
            ->assertRedirect(route('wellbeing-check-ins.index'))->assertSessionHas('success');
        $row->refresh();
        $this->assertSame(['answered', 'staff', $this->actor()->id, ['scale' => 2, 'note' => '<i>Breaks are lonely</i>']],
            [$row->status, $row->answer_source, (int) $row->answered_by, $row->answer]);
        $this->assertStringNotContainsString('lonely', (string) DB::table('wellbeing_check_ins')->where('id', $row->id)->value('answer'), 'the answer is encrypted');
        $this->assertStringNotContainsString('lonely', DB::table('audit_log')->where('action', 'like', 'wellbeing.check_in%')->get()->toJson(), 'the audit log has no answer');
        $this->assertArrayNotHasKey('answer', $row->toArray());

        $this->get(route('wellbeing-check-ins.index'))->assertOk()->assertSee('How are things with your friends?')->assertSee('A bit low')
            ->assertSee('&lt;i&gt;Breaks are lonely&lt;/i&gt;', false)->assertSee('Recorded by staff');
        $this->assertContains('wellbeing-check-ins', array_column(app(Navigation::class)->items($this->actor()), 'key'));
    }

    public function test_a_group_check_in_needs_every_learner_on_the_professionals_caseload(): void
    {
        $learners = [$this->schoolA->ids['asha'], $this->schoolA->ids['ben']];
        $this->denied(fn () => app(CheckIns::class)->schedule($this->actor(), ['learner_ids' => $learners, 'prompt' => 'school', 'due_on' => '2026-02-12']), 404);
        $this->assertSame(0, CheckIn::count(), 'all or nothing');

        $this->grant('ben', 'tara');
        $done = app(CheckIns::class)->schedule($this->actor(), ['learner_ids' => $learners, 'prompt' => 'school', 'due_on' => '2026-02-12']);
        $this->assertCount(2, $done['ids']);
        $this->assertSame([$done['group_id']], CheckIn::pluck('group_id')->unique()->values()->all());
        try {
            app(CheckIns::class)->schedule($this->actor(), ['learner_ids' => [$learners[0]], 'prompt' => 'school', 'due_on' => '2026-02-12']);
            $this->fail('The same check-in was scheduled twice');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('learner_ids', $e->errors());
        }
        foreach (['2026-02-09', '2026-05-01'] as $day) {
            try {
                app(CheckIns::class)->schedule($this->actor(), ['learner_ids' => [$learners[0]], 'prompt' => 'today', 'due_on' => $day]);
                $this->fail('A check-in was scheduled in the past or too far ahead');
            } catch (ValidationException $e) {
                $this->assertArrayHasKey('due_on', $e->errors());
            }
        }
        $this->assertSame(2, CheckIn::count());
    }

    public function test_teachers_grade_heads_and_the_principal_never_see_or_change_check_ins(): void
    {
        $id = $this->answered();
        AcademicLeadership::create(['user_id' => $this->actor('theo')->id, 'kind' => 'grade_head', 'grade_level_id' => $this->grade,
            'session_year_id' => $this->year, 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Synthetic grade head']);
        AcademicLeadership::create(['user_id' => $this->superAdmin()->id, 'kind' => 'principal', 'grade_level_id' => null,
            'session_year_id' => $this->year, 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Synthetic principal']);
        // Theo even holds a risk caseload grant for Asha, but no pastoral role.
        $this->grant('asha', 'theo');
        $before = CheckIn::query()->orderBy('id')->get()->toJson();
        app(RiskRecompute::class)->grade($this->year, $this->grade);

        foreach (['theo' => $this->actor('theo'), 'principal' => $this->superAdmin()] as $name => $user) {
            $this->denied(fn () => app(CheckIns::class)->list($user), 403);
            $this->denied(fn () => app(CheckIns::class)->authorized($user, $id), 403);
            $this->denied(fn () => app(CheckIns::class)->schedule($user, ['learner_ids' => [$this->schoolA->ids['asha']], 'prompt' => 'week', 'due_on' => '2026-02-11']), 403);
            $this->assertSame([], app(CheckInContext::class)->forLearners($user, [$this->schoolA->ids['asha']]));
            $this->assertNotContains('wellbeing-check-ins', array_column(app(Navigation::class)->items($user), 'key'));
            $this->actingAs($user)->get(route('wellbeing-check-ins.index'))->assertForbidden()->assertDontSee('Breaks are lonely');
            $this->post(route('wellbeing-check-ins.schedule'), $this->identity($name) + ['learner_ids' => [$this->schoolA->ids['asha']], 'prompt' => 'week', 'due_on' => '2026-02-11'])->assertForbidden();
            $this->post(route('wellbeing-check-ins.cancel', ['checkIn' => $id]), $this->identity($name) + ['expected_revision' => 2])->assertForbidden();
            $this->post(route('wellbeing-check-ins.record', ['checkIn' => $id]), $this->identity($name) + ['expected_revision' => 2, 'scale' => 5])->assertForbidden();
        }
        // The grade head's early warning list carries no check-in context.
        $this->actingAs($this->actor('theo'))->get('/risk-interventions?mode=signals&session_year_id='.$this->year)->assertOk()
            ->assertSee($this->actor('asha')->full_name)->assertDontSee('Check-in context')->assertDontSee('A bit low');

        // Tara as a teacher only, without the counsellor role, is refused too.
        $this->actor()->syncRoles([SchoolRoles::TEACHER]);
        $this->denied(fn () => app(CheckIns::class)->list($this->actor()), 403);
        $this->actingAs($this->actor())->get(route('wellbeing-check-ins.index'))->assertForbidden();
        $this->assertSame($before, CheckIn::query()->orderBy('id')->get()->toJson());
        $this->assertSame(1, CheckIn::count());
    }

    public function test_a_counsellor_without_the_learners_caseload_cannot_read_or_change_their_check_ins(): void
    {
        $id = $this->answered();
        $pending = app(CheckIns::class)->schedule($this->actor(), ['learner_ids' => [$this->schoolA->ids['asha']], 'prompt' => 'today', 'due_on' => '2026-02-11'])['ids'][0];
        // Theo is a counsellor too, but his caseload is Ben.
        $this->actor('theo')->assignRole(SchoolRoles::COUNSELLOR);
        $this->grant('ben', 'theo');
        $theo = $this->actor('theo');

        $this->assertSame([], app(CheckIns::class)->list($theo)['check_ins']);
        $this->denied(fn () => app(CheckIns::class)->authorized($theo, $id), 404);
        $this->denied(fn () => app(CheckIns::class)->record($theo, $pending, ['scale' => 5, 'expected_revision' => 1]), 404);
        $this->denied(fn () => app(CheckIns::class)->cancel($theo, $pending, 1), 404);
        $this->denied(fn () => app(CheckIns::class)->schedule($theo, ['learner_ids' => [$this->schoolA->ids['asha']], 'prompt' => 'week', 'due_on' => '2026-02-12']), 404);
        $this->assertSame([], app(CheckInContext::class)->forLearners($theo, [$this->schoolA->ids['asha']]));
        $this->actingAs($theo)->get(route('wellbeing-check-ins.index'))->assertOk()->assertDontSee('Breaks are lonely')->assertDontSee($this->actor('asha')->full_name);
        $this->post(route('wellbeing-check-ins.record', ['checkIn' => $pending]), $this->identity('theo') + ['expected_revision' => 1, 'scale' => 5])->assertNotFound();
        $this->post(route('wellbeing-check-ins.cancel', ['checkIn' => $pending]), $this->identity('theo') + ['expected_revision' => 1])->assertNotFound();
        $this->assertSame(['scheduled', 1], [CheckIn::findOrFail($pending)->status, CheckIn::findOrFail($pending)->revision]);

        // When Tara's grant ends she loses the answers she recorded.
        RiskCaseload::where('professional_id', $this->actor()->id)->update(['revoked_at' => now()]);
        $this->assertSame([], app(CheckIns::class)->list($this->actor())['check_ins']);
        $this->denied(fn () => app(CheckIns::class)->authorized($this->actor(), $id), 404);
    }

    public function test_another_schools_learners_and_check_ins_are_refused(): void
    {
        $b = $this->schoolB->ids;
        $foreign = app(CurrentSchool::class)->run(self::SCHOOL_B_ID, fn () => CheckIn::create(['learner_id' => $b['asha'], 'session_year_id' => $b['session_year'],
            'scheduled_by' => $b['tara_user'], 'prompt' => 'week', 'due_on' => '2026-02-10', 'status' => 'scheduled', 'revision' => 1]));
        $before = DB::table('wellbeing_check_ins')->where('id', $foreign->id)->first();

        $this->denied(fn () => app(CheckIns::class)->schedule($this->actor(), ['learner_ids' => [$b['asha']], 'prompt' => 'week', 'due_on' => '2026-02-10']), 404);
        $this->denied(fn () => app(CheckIns::class)->record($this->actor(), (int) $foreign->id, ['scale' => 1, 'expected_revision' => 1]), 404);
        $this->denied(fn () => app(CheckIns::class)->cancel($this->actor(), (int) $foreign->id, 1), 404);
        $this->actingAs($this->actor())->post(route('wellbeing-check-ins.schedule'), $this->identity() + ['learner_ids' => [$b['asha']], 'prompt' => 'week', 'due_on' => '2026-02-10'])->assertNotFound();
        $this->post(route('wellbeing-check-ins.record', ['checkIn' => $foreign->id]), $this->identity() + ['expected_revision' => 1, 'scale' => 1])->assertNotFound();
        $this->get(route('wellbeing-check-ins.index'))->assertOk()->assertDontSee(self::SCHOOL_B_MARKER);
        $this->assertSame(0, CheckIn::count(), 'School A gains no check-in');
        $this->assertEquals($before, DB::table('wellbeing_check_ins')->where('id', $foreign->id)->first(), 'School B\'s check-in is unchanged');
    }

    public function test_check_ins_feed_early_warning_as_context_only_and_never_raise_a_risk(): void
    {
        $risk = app(RiskRecompute::class);
        $risk->grade($this->year, $this->grade);
        $observations = RiskObservation::query()->orderBy('id')->get(['id', 'source', 'score', 'signal_count', 'reason_code'])->toJson();
        $this->answered(1, 'Not a good week');
        $risk->grade($this->year, $this->grade);

        $this->assertSame($observations, RiskObservation::query()->orderBy('id')->get(['id', 'source', 'score', 'signal_count', 'reason_code'])->toJson(), 'no observation is added or scored');
        $this->assertNull(RiskObservation::where('source', 'wellbeing')->latest('id')->value('score'));
        $this->assertStringNotContainsString('check', strtolower(RiskObservation::query()->pluck('reason_code')->implode(',')));

        $context = app(CheckInContext::class)->forLearners($this->actor(), [$this->schoolA->ids['asha'], $this->schoolA->ids['ben']]);
        $this->assertSame([$this->schoolA->ids['asha'] => ['answered' => 1, 'latest_scale' => 1, 'latest_on' => '2026-02-10']], $context, 'only the reader\'s own learners');
        $this->actingAs($this->actor())->get('/risk-interventions?mode=signals&session_year_id='.$this->year)->assertOk()
            ->assertSee('Check-in context')->assertSee('Not good')->assertSee('It does not affect the risk score.')->assertDontSee('Not a good week');
    }

    public function test_the_learner_answers_only_their_own_due_check_in(): void
    {
        foreach (['asha', 'ben'] as $name) {
            $this->actor($name)->forceFill(['password_change_required' => false, 'status' => 1])->save();
        }
        $this->grant('ben', 'tara');
        $checkIns = app(CheckIns::class);
        $due = $checkIns->schedule($this->actor(), ['learner_ids' => [$this->schoolA->ids['asha']], 'prompt' => 'today', 'due_on' => '2026-02-10'])['ids'][0];
        $later = $checkIns->schedule($this->actor(), ['learner_ids' => [$this->schoolA->ids['asha']], 'prompt' => 'week', 'due_on' => '2026-02-13'])['ids'][0];
        $bens = $checkIns->schedule($this->actor(), ['learner_ids' => [$this->schoolA->ids['ben']], 'prompt' => 'today', 'due_on' => '2026-02-10'])['ids'][0];

        $this->assertSame([[$due, 'How are you feeling today?']], array_map(fn ($row) => [$row['id'], $row['prompt']], $checkIns->dueForLearner($this->actor('asha'))));
        $this->denied(fn () => $checkIns->answerAsLearner($this->actor('asha'), $bens, ['scale' => 5]), 404);
        $this->denied(fn () => $checkIns->answerAsLearner($this->actor('asha'), $later, ['scale' => 5]), 404);
        $this->denied(fn () => $checkIns->answerAsLearner($this->actor('ben'), $due, ['scale' => 1]), 404);
        $this->denied(fn () => $checkIns->answerAsLearner($this->actor('farid'), $due, ['scale' => 1]), 403);
        $this->denied(fn () => $checkIns->dueForLearner($this->actor('theo')), 403);

        $checkIns->answerAsLearner($this->actor('asha'), $due, ['scale' => 4, 'note' => 'Good day']);
        $this->assertSame(['answered', 'learner', ['scale' => 4, 'note' => 'Good day']], [CheckIn::findOrFail($due)->status, CheckIn::findOrFail($due)->answer_source, CheckIn::findOrFail($due)->answer]);
        $this->denied(fn () => $checkIns->answerAsLearner($this->actor('asha'), $due, ['scale' => 1]), 409);
        $this->assertSame([], $checkIns->dueForLearner($this->actor('asha')));
        $this->assertSame(['scheduled', 'scheduled'], [CheckIn::findOrFail($bens)->status, CheckIn::findOrFail($later)->status]);
        // Learners never open the staff page.
        $this->actingAs($this->actor('asha'))->get(route('wellbeing-check-ins.index'))->assertForbidden();
    }

    public function test_writes_are_bound_to_the_account_conflicts_are_refused_and_the_note_is_never_flashed(): void
    {
        $id = app(CheckIns::class)->schedule($this->actor(), ['learner_ids' => [$this->schoolA->ids['asha']], 'prompt' => 'week', 'due_on' => '2026-02-10'])['ids'][0];
        $this->actingAs($this->actor());
        $this->post(route('wellbeing-check-ins.record', ['checkIn' => $id]), ['expected_account_id' => $this->actor('theo')->id, 'expected_school_id' => 1, 'expected_revision' => 1, 'scale' => 3])->assertForbidden();
        $this->post(route('wellbeing-check-ins.record', ['checkIn' => $id]).'?note=URL_SECRET', $this->identity() + ['expected_revision' => 1, 'scale' => 3])->assertUnprocessable()->assertDontSee('URL_SECRET');
        $this->post(route('wellbeing-check-ins.record', ['checkIn' => $id]), $this->identity() + ['expected_revision' => 1, 'note' => 'Private unflashed note'])
            ->assertRedirect(route('wellbeing-check-ins.index'))->assertSessionHasErrors('scale');
        $this->assertFalse(session()->has('_old_input'));
        $this->post(route('wellbeing-check-ins.record', ['checkIn' => $id]), $this->identity() + ['expected_revision' => 7, 'scale' => 3, 'note' => 'Stale page'])
            ->assertRedirect(route('wellbeing-check-ins.index'))->assertSessionHasErrors('check_in');
        $this->assertSame('scheduled', CheckIn::findOrFail($id)->status);

        $this->post(route('wellbeing-check-ins.cancel', ['checkIn' => $id]), $this->identity() + ['expected_revision' => 1])->assertSessionHas('success');
        $this->assertSame('cancelled', CheckIn::findOrFail($id)->status);
        $this->post(route('wellbeing-check-ins.record', ['checkIn' => $id]), $this->identity() + ['expected_revision' => 2, 'scale' => 3])->assertSessionHasErrors('check_in');
        $this->assertNull(CheckIn::findOrFail($id)->answer);
        $this->assertFalse(session()->has('_old_input'));
    }
}
