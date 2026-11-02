<?php

declare(strict_types=1);

namespace Tests\Feature\Growth;

use App\Jobs\Growth\EvaluateRisk;
use App\Models\Academics\AcademicLeadership;
use App\Models\Academics\GradeLevel;
use App\Models\ClassSchool;
use App\Models\Growth\LearnerNote;
use App\Models\Growth\RiskObservation;
use App\Models\Growth\UnseenAssignment;
use App\Models\Notification;
use App\Models\PeerTutoring\PeerTutoringSession;
use App\Models\People\Enrolment;
use App\Models\Platform\AuditLog;
use App\Models\Platform\OutboxEvent;
use App\Models\Platform\SchoolModule;
use App\Models\Recognition\AwardPresentation;
use App\Models\Recognition\House;
use App\Models\Recognition\HousePoint;
use App\Models\Recognition\PointCategory;
use App\Models\Settings;
use App\Models\User;
use App\Services\Growth\PositiveContacts;
use App\Services\Growth\RiskRecompute;
use App\Services\Growth\RiskViews;
use App\Services\Growth\UnseenLearners;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Bus;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Tests\Concerns\UsesMasteryScenario;
use Tests\TestCase;

/**
 * "No child unseen" (Action Plan 4.5): an early-warning rule for learners
 * with no recent positive contact, grade-head and principal lists, and the
 * named adult. engineering/specs/growth/risk-interventions.md.
 *
 * Today is Tuesday 10 February 2026; with the default 15 school days
 * (weekdays, no holidays or breaks in the window) the window starts on
 * Wednesday 21 January.
 */
class NoChildUnseenTest extends TestCase
{
    use UsesMasteryScenario;

    private int $grade;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpMasteryScenario();
        Bus::fake();
        $this->grade = (int) GradeLevel::where('code', '7')->value('id');
        foreach (['early_warning', 'awards', 'house_points', 'peer_tutoring'] as $module) {
            SchoolModule::updateOrCreate(['module' => $module], ['in_package' => true, 'enabled' => true]);
        }
        app(ModuleGate::class)->forget();
        Enrolment::create(['learner_id' => $this->schoolA->ids['ben'], 'class_section_id' => $this->schoolA->ids['section_a'], 'session_year_id' => $this->year,
            'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Synthetic second learner']);
        foreach (['tara', 'theo'] as $name) {
            $this->actor($name)->forceFill(['password_change_required' => false])->save();
        }
        // Tara heads grade 7; Theo is an ordinary teacher; the super admin is principal.
        AcademicLeadership::create(['user_id' => $this->actor()->id, 'kind' => 'grade_head', 'grade_level_id' => $this->grade,
            'session_year_id' => $this->year, 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Synthetic grade head']);
        AcademicLeadership::create(['user_id' => $this->superAdmin()->id, 'kind' => 'principal', 'grade_level_id' => null,
            'session_year_id' => $this->year, 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Synthetic principal']);
    }

    private function id(string $name): int
    {
        return $this->schoolA->ids[$name];
    }

    private function note(string $learner, string $on, string $visibility = 'staff'): void
    {
        $note = LearnerNote::create(['learner_id' => $this->id($learner), 'subject_id' => $this->subject, 'session_year_id' => $this->year,
            'author_id' => $this->actor()->id, 'visibility' => $visibility, 'body' => 'Private note text '.$visibility]);
        $note->forceFill(['created_at' => $on.' 09:00:00'])->save();
    }

    private function points(string $learner, int $points, string $on, ?int $reverses = null): HousePoint
    {
        $house = House::firstOrCreate(['name' => 'Synthetic house'], ['emblem' => 'star', 'primary_colour' => '#000000', 'secondary_colour' => '#ffffff']);
        $category = PointCategory::firstOrCreate(['name' => 'Synthetic category'], ['default_points' => 5, 'minimum_points' => -10, 'maximum_points' => 10]);

        return HousePoint::create(['learner_id' => $this->id($learner), 'house_id' => $house->id, 'point_category_id' => $category->id, 'session_year_id' => $this->year,
            'grade_level_id' => $this->grade, 'staff_id' => $this->actor()->id, 'house_name' => $house->name, 'category_name' => $category->name, 'points' => $points,
            'on_date' => $on, 'reason' => 'Private point reason', 'public' => false, 'scope_kind' => 'school', 'reverses_id' => $reverses]);
    }

    private function presentation(string $learner, string $on, string $state = 'active'): void
    {
        AwardPresentation::create(['learner_id' => $this->id($learner), 'session_year_id' => $this->year, 'grade_level_id' => $this->grade,
            'presented_by' => $this->actor()->id, 'presented_on' => $on, 'title' => 'Private award title', 'criteria' => 'Synthetic criteria',
            'source_kind' => 'outside', 'state' => $state]);
    }

    private function peer(string $tutor, string $tutee, string $on, string $status): void
    {
        PeerTutoringSession::create(['session_year_id' => $this->year, 'teaching_group_id' => $this->group, 'topic_id' => $this->nodes[$this->keys[1]],
            'tutor_learner_id' => $this->id($tutor), 'tutee_learner_id' => $this->id($tutee), 'supervisor_user_id' => $this->actor()->id,
            'starts_at' => $on.' 12:00:00', 'ends_at' => $on.' 12:30:00', 'venue' => 'Library', 'status' => $status]);
    }

    private function contact(string $learner): array
    {
        return app(PositiveContacts::class)->evaluate([$this->id($learner)], $this->year)['learners'][$this->id($learner)];
    }

    private function denied(callable $call, int $expected = 403): void
    {
        try {
            $call();
            $this->fail('The request must be refused.');
        } catch (HttpExceptionInterface $e) {
            $this->assertSame($expected, $e->getStatusCode());
        } catch (ModelNotFoundException) {
            $this->assertSame($expected, 404);
        }
    }

    private function form(User $actor, array $extra = []): array
    {
        return $extra + ['expected_account_id' => $actor->id, 'expected_school_id' => 1];
    }

    public function test_unseen_learner_is_listed_for_the_grade_head_and_a_named_adult_is_assigned_audited_and_notified(): void
    {
        $this->note('ben', '2026-02-05');
        $this->assertSame('seen', $this->contact('ben')['status']);
        $asha = $this->contact('asha');
        $this->assertSame(['unseen', 'no_positive_contact', null, null], [$asha['status'], $asha['reason'], $asha['last_contact_on'], $asha['school_days_since']]);

        $list = app(UnseenLearners::class)->list($this->actor());
        $this->assertSame(['2026-01-21', ['progress_note'], 1, 2], [$list['window_start'], $list['sources'], $list['total'], $list['roster']]);
        $this->assertSame([$this->id('asha')], array_column($list['learners'], 'learner_id'));
        $this->assertSame([], $list['learners'][0]['kinds']);

        $this->actingAs($this->actor())->get('/risk-interventions/unseen')->assertOk()->assertHeader('Cache-Control', 'no-store, private')
            ->assertSee('Asha Demo')->assertDontSee('Ben Demo')->assertSee('No positive contact recorded this year')->assertDontSee('Private note text');

        $before = UnseenAssignment::count();
        $this->post('/risk-interventions/unseen/learners/'.$this->id('asha').'/adult', $this->form($this->actor(), ['adult_user_id' => $this->actor('theo')->id]))
            ->assertRedirect('/risk-interventions/unseen');
        $row = UnseenAssignment::sole();
        $this->assertSame($before + 1, UnseenAssignment::count());
        $this->assertSame([$this->id('asha'), $this->actor('theo')->id, $this->actor()->id, '2026-02-10', null],
            [(int) $row->learner_id, (int) $row->adult_user_id, (int) $row->assigned_by, $row->assigned_on->toDateString(), $row->cleared_at]);
        $audit = AuditLog::where('action', 'growth.unseen_adult_assigned')->sole();
        $this->assertSame((int) $row->id, (int) $audit->subject_id);
        $this->assertStringNotContainsString('Private note text', json_encode($audit->toArray(), JSON_THROW_ON_ERROR));

        $this->get('/risk-interventions/unseen')->assertOk()->assertSee('Named adult: Theo Demo, since 2026-02-10');
        // Theo, the named adult, sees only the learner he was asked to reach out to.
        $this->actingAs($this->actor('theo'))->get('/risk-interventions/unseen')->assertOk()->assertSee('Learners you were asked to check in with')
            ->assertSee('Asha Demo')->assertDontSee('Learners with no recent positive contact')->assertDontSee('Private note text');
    }

    public function test_a_learner_with_recent_positive_contact_is_not_flagged_and_a_contact_clears_the_flag_and_named_adult(): void
    {
        $this->note('ben', '2026-02-09');
        $this->presentation('asha', '2026-01-20'); // a day before the window
        $asha = $this->contact('asha');
        $this->assertSame(['unseen', '2026-01-20', 15, ['award']], [$asha['status'], $asha['last_contact_on'], $asha['school_days_since'], $asha['kinds']]);
        $this->assertSame(['seen', 1], [$this->contact('ben')['status'], $this->contact('ben')['school_days_since']]);
        app(UnseenLearners::class)->assign($this->actor(), $this->id('asha'), ['adult_user_id' => $this->actor('theo')->id]);

        $this->points('asha', 5, '2026-02-10');
        $this->assertSame(['seen', 0, ['award', 'house_points']], [$this->contact('asha')['status'], $this->contact('asha')['school_days_since'], $this->contact('asha')['kinds']]);
        $this->assertSame([], app(UnseenLearners::class)->list($this->actor())['learners']);
        // Naming an adult for a learner who has been seen is refused.
        $this->denied(fn () => app(UnseenLearners::class)->assign($this->actor(), $this->id('ben'), ['adult_user_id' => $this->actor('theo')->id]), 422);
        // The next early-warning run closes the named-adult request.
        app(RiskRecompute::class)->grade($this->year, $this->grade);
        $row = UnseenAssignment::sole();
        $this->assertSame('contact_recorded', $row->cleared_reason);
        $this->assertNotNull($row->cleared_at);
        $this->assertSame(1, AuditLog::where('action', 'growth.unseen_adult_cleared')->count());
    }

    public function test_switched_off_or_unused_sources_give_unknown_never_unseen(): void
    {
        // No source has any record: unknown for everyone, nobody flagged.
        $this->assertSame(['unknown', 'source_unavailable'], [$this->contact('asha')['status'], $this->contact('asha')['reason']]);
        $list = app(UnseenLearners::class)->list($this->superAdmin());
        $this->assertSame([0, 2, []], [$list['total'], $list['unknown'], $list['sources']]);

        // House points are recorded for Ben, but the module is off: still unknown.
        $this->points('ben', 3, '2026-02-09');
        SchoolModule::where('module', 'house_points')->update(['enabled' => false]);
        app(ModuleGate::class)->forget();
        $this->assertSame('unknown', $this->contact('asha')['status']);
        $this->assertSame(['unknown', []], [$this->contact('ben')['status'], $this->contact('ben')['kinds']]);
        $this->actingAs($this->actor())->get('/risk-interventions/unseen')->assertOk()->assertSee('no source of positive contact was used');

        // Back on, the source is in use: Ben is seen, Asha is unseen.
        SchoolModule::where('module', 'house_points')->update(['enabled' => true]);
        app(ModuleGate::class)->forget();
        $this->assertSame(['seen', 'unseen'], [$this->contact('ben')['status'], $this->contact('asha')['status']]);

        // A source used only before the window does not make anyone unseen.
        HousePoint::query()->toBase()->update(['on_date' => '2026-01-05']);
        $this->assertSame('unknown', $this->contact('asha')['status']);

        // Too few school days in the year so far is unknown too.
        $this->points('ben', 3, '2026-02-09');
        Settings::updateOrCreate(['type' => PositiveContacts::SETTING], ['message' => '60']);
        $this->assertSame(['unknown', 'insufficient_evidence'], [$this->contact('asha')['status'], $this->contact('asha')['reason']]);
    }

    public function test_clinical_negative_reversed_voided_and_cancelled_records_never_count(): void
    {
        $this->note('ben', '2026-02-09');
        $this->note('asha', '2026-02-09', 'counselling');
        $this->points('asha', -3, '2026-02-09');
        $reversed = $this->points('asha', 5, '2026-02-02');
        $this->points('asha', -5, '2026-02-03', (int) $reversed->id);
        $this->presentation('asha', '2026-02-04', 'voided');
        $this->peer('ben', 'asha', '2026-02-05', 'cancelled');
        $archived = LearnerNote::create(['learner_id' => $this->id('asha'), 'subject_id' => $this->subject, 'session_year_id' => $this->year,
            'author_id' => $this->actor()->id, 'visibility' => 'staff', 'body' => 'Private archived note', 'archived_at' => now()]);
        $this->assertNotNull($archived->id);

        $asha = $this->contact('asha');
        $this->assertSame(['unseen', null, []], [$asha['status'], $asha['last_contact_on'], $asha['kinds']]);
        $this->assertSame(['progress_note'], app(PositiveContacts::class)->evaluate([$this->id('asha')], $this->year)['sources']);

        // A completed supervised session counts for both learners.
        $this->peer('ben', 'asha', '2026-02-06', 'completed');
        $this->assertSame(['seen', ['peer_tutoring']], [$this->contact('asha')['status'], $this->contact('asha')['kinds']]);
    }

    public function test_a_teacher_without_grade_head_rights_is_refused_the_list_and_the_assignment(): void
    {
        $this->note('ben', '2026-02-05');
        $theo = $this->actor('theo');
        $this->denied(fn () => app(UnseenLearners::class)->list($theo));
        $this->denied(fn () => app(UnseenLearners::class)->assign($theo, $this->id('asha'), ['adult_user_id' => $theo->id]));
        $this->actingAs($theo)->get('/risk-interventions/unseen')->assertForbidden();
        $this->post('/risk-interventions/unseen/learners/'.$this->id('asha').'/adult', $this->form($theo, ['adult_user_id' => $theo->id]))->assertForbidden();
        $this->post('/risk-interventions/unseen/settings', $this->form($theo, ['school_days' => 10]))->assertForbidden();
        // A grade head of another grade may not see or name for grade 7.
        $other = (int) GradeLevel::where('id', '!=', $this->grade)->value('id');
        AcademicLeadership::create(['user_id' => $theo->id, 'kind' => 'grade_head', 'grade_level_id' => $other, 'session_year_id' => $this->year,
            'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Synthetic other head']);
        $this->denied(fn () => app(UnseenLearners::class)->list($theo, $this->grade));
        $this->denied(fn () => app(UnseenLearners::class)->assign($theo, $this->id('asha'), ['adult_user_id' => $theo->id]));
        $this->assertSame([], app(UnseenLearners::class)->list($theo)['learners']);
        // A grade head cannot change the school days; the principal can.
        $this->denied(fn () => app(UnseenLearners::class)->configure($this->actor(), ['school_days' => 10]));
        $this->actingAs($this->actor())->post('/risk-interventions/unseen/learners/'.$this->id('asha').'/adult?adult_user_id=1', $this->form($this->actor(), ['adult_user_id' => $theo->id]))->assertUnprocessable();
        // Families never reach it.
        $this->actingAs($this->actor('farid'))->get('/risk-interventions/unseen')->assertForbidden();
        $this->assertSame(0, UnseenAssignment::count());
        $this->assertSame(15, app(PositiveContacts::class)->schoolDays());

        $this->actingAs($this->superAdmin())->post('/risk-interventions/unseen/settings', $this->form($this->superAdmin(), ['school_days' => 10]))->assertRedirect();
        $this->assertSame(10, app(PositiveContacts::class)->schoolDays());
        $this->assertTrue(OutboxEvent::where('type', 'growth.risk_rules_configured')->exists());
    }

    public function test_school_b_learners_never_appear_for_school_a_and_cannot_be_assigned(): void
    {
        $b = $this->schoolB->ids;
        $bYear = $b['session_year'];
        $bUnseen = app(CurrentSchool::class)->run(2, function () use ($b, $bYear) {
            ClassSchool::whereKey($b['class_a'])->update(['grade_level_id' => $this->grade]);
            Enrolment::create(['learner_id' => $b['asha'], 'class_section_id' => $b['section_a'], 'session_year_id' => $bYear,
                'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Baobab synthetic placement']);

            return UnseenAssignment::count();
        });
        $this->note('ben', '2026-02-05');
        foreach ([null, $this->grade] as $grade) {
            $ids = array_column(app(UnseenLearners::class)->list($this->superAdmin(), $grade)['learners'], 'learner_id');
            $this->assertSame([$this->id('asha')], $ids);
        }
        $this->actingAs($this->superAdmin())->get('/risk-interventions/unseen')->assertOk()->assertSee('Asha Demo')->assertDontSee(self::SCHOOL_B_MARKER);
        $this->denied(fn () => app(UnseenLearners::class)->assign($this->superAdmin(), $b['asha'], ['adult_user_id' => $this->actor('theo')->id]), 404);
        $this->post('/risk-interventions/unseen/learners/'.$b['asha'].'/adult', $this->form($this->superAdmin(), ['adult_user_id' => $this->actor('theo')->id]))->assertNotFound();
        $this->assertSame(0, UnseenAssignment::count());
        $this->assertSame($bUnseen, app(CurrentSchool::class)->run(2, fn () => UnseenAssignment::count()));
    }

    public function test_an_adult_from_another_school_is_refused(): void
    {
        $this->note('ben', '2026-02-05');
        $foreign = $this->schoolB->ids['theo_user'];
        try {
            app(UnseenLearners::class)->assign($this->actor(), $this->id('asha'), ['adult_user_id' => $foreign]);
            $this->fail('An adult from another school must be refused.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('adult_user_id', $e->errors());
        }
        // A learner or parent of this school is not a staff member either.
        foreach (['ben_user', 'farid_user'] as $person) {
            $this->expectsValidation(fn () => app(UnseenLearners::class)->assign($this->actor(), $this->id('asha'), ['adult_user_id' => $this->schoolA->ids[$person]]));
        }
        $this->actingAs($this->actor())->post('/risk-interventions/unseen/learners/'.$this->id('asha').'/adult', $this->form($this->actor(), ['adult_user_id' => $foreign]))
            ->assertSessionHasErrors('adult_user_id');
        $this->assertSame(0, UnseenAssignment::count());
        $this->assertSame(0, Notification::where('type', UnseenLearners::NOTICE)->count());
    }

    private function expectsValidation(callable $call): void
    {
        try {
            $call();
            $this->fail('The adult must be refused.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('adult_user_id', $e->errors());
        }
    }

    public function test_the_notification_goes_only_to_the_named_adult_and_names_no_learner(): void
    {
        $this->note('ben', '2026-02-05');
        $before = OutboxEvent::where('type', 'notification.requested')->count();
        app(UnseenLearners::class)->assign($this->actor(), $this->id('asha'), ['adult_user_id' => $this->actor('theo')->id]);
        $notice = Notification::where('type', UnseenLearners::NOTICE)->sole();
        $this->assertSame('/risk-interventions/unseen', $notice->delivery_payload['link']);
        $this->assertStringNotContainsString('Asha', $notice->title.$notice->message);
        $this->assertSame($before + 1, OutboxEvent::where('type', 'notification.requested')->count());
        $fanout = OutboxEvent::where('type', 'notification.requested')->where('payload->notification_id', $notice->id)->sole();
        $this->assertSame([$this->actor('theo')->id], $fanout->payload['user_ids']);
        $this->assertArrayHasKey(UnseenLearners::NOTICE, config('notification_types'));
        $this->assertSame('early_warning', config('notification_types')[UnseenLearners::NOTICE]['module']);

        // Naming a different adult closes the first request and notifies only the new adult.
        app(UnseenLearners::class)->assign($this->actor(), $this->id('asha'), ['adult_user_id' => $this->superAdmin()->id]);
        $this->assertSame(['reassigned', null], UnseenAssignment::orderBy('id')->pluck('cleared_reason')->all());
        $latest = Notification::where('type', UnseenLearners::NOTICE)->latest('id')->firstOrFail();
        $this->assertSame([$this->superAdmin()->id], OutboxEvent::where('type', 'notification.requested')->where('payload->notification_id', $latest->id)->sole()->payload['user_ids']);
    }

    public function test_the_hourly_risk_evaluate_command_records_the_same_unseen_rule(): void
    {
        $events = collect(app(Schedule::class)->events())->filter(fn ($e) => str_contains((string) $e->command, 'risk:evaluate'));
        $this->assertCount(1, $events);
        $this->note('ben', '2026-02-05');
        $this->assertSame(0, Artisan::call('risk:evaluate', ['school' => 1]));
        Bus::assertDispatched(EvaluateRisk::class, fn (EvaluateRisk $job) => $job->schoolId === 1 && $job->yearId === $this->year && $job->gradeId === $this->grade);
        Bus::assertNotDispatched(EvaluateRisk::class, fn (EvaluateRisk $job) => $job->schoolId === 2);

        app()->call([new EvaluateRisk(1, $this->year, $this->grade), 'handle']);
        $rows = RiskObservation::where('source', 'unseen')->get()->keyBy('learner_id');
        $this->assertSame(['no_positive_contact', 100, null], [$rows[$this->id('asha')]->reason_code, $rows[$this->id('asha')]->score, $rows[$this->id('asha')]->signal_count]);
        $this->assertSame(['positive_contact_recorded', 0, 3], [$rows[$this->id('ben')]->reason_code, $rows[$this->id('ben')]->score, $rows[$this->id('ben')]->signal_count]);
        $this->assertSame($this->contact('asha')['reason'], $rows[$this->id('asha')]->reason_code);
        $evidence = $rows[$this->id('ben')]->evidence;
        foreach (['school_days' => 15, 'window_start' => '2026-01-21', 'sources' => ['progress_note'], 'kinds' => ['progress_note'], 'last_contact_on' => '2026-02-05'] as $key => $value) {
            $this->assertSame($value, $evidence[$key], $key);
        }
        $this->assertStringNotContainsString('Private note text', json_encode($evidence, JSON_THROW_ON_ERROR));
        // The signals contract keeps its four sources.
        $signals = app(RiskViews::class)->list($this->actor(), $this->year);
        $this->assertNotContains('unseen', collect($signals['learners'])->flatMap(fn ($l) => array_column($l['signals'], 'source'))->unique()->all());

        // With no source in use the job records unknown, and School B gets nothing.
        LearnerNote::query()->toBase()->delete();
        app()->call([new EvaluateRisk(1, $this->year, $this->grade), 'handle']);
        $this->assertSame('source_unavailable', RiskObservation::where('source', 'unseen')->where('learner_id', $this->id('asha'))->latest('id')->value('reason_code'));
        $this->assertSame(0, app(CurrentSchool::class)->run(2, fn () => RiskObservation::where('source', 'unseen')->count()));

        // Early warning off: the job does nothing.
        $count = RiskObservation::count();
        SchoolModule::where('module', 'early_warning')->update(['enabled' => false]);
        app(ModuleGate::class)->forget();
        app()->call([new EvaluateRisk(1, $this->year, $this->grade), 'handle']);
        $this->assertSame($count, RiskObservation::count());
        $this->denied(fn () => app(UnseenLearners::class)->list($this->actor()));
    }
}
