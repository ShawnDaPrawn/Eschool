<?php

declare(strict_types=1);

namespace Tests\Feature\Academics;

use App\Models\Academics\BellSchedule;
use App\Models\Academics\BellScheduleOverride;
use App\Models\Academics\GradeLevel;
use App\Models\Academics\TimetableLesson;
use App\Models\ClassSchool;
use App\Models\Notification;
use App\Models\Pastoral\CoverAbsence;
use App\Models\Pastoral\CoverAssignment;
use App\Models\People\Enrolment;
use App\Models\Platform\OutboxEvent;
use App\Models\Platform\SchoolMembership;
use App\Models\SessionYear;
use App\Models\Teacher;
use App\Models\User;
use App\Services\Academics\DayPatterns;
use App\Services\Academics\TeachingGroups;
use App\Services\Academics\TimetableConfiguration;
use App\Services\Academics\TimetableEngine;
use App\Services\Academics\TimetableQueries;
use App\Services\Pastoral\CoverQueries;
use App\Services\Pastoral\ReliefCover;
use App\Services\Platform\Tenancy\CurrentSchool;
use App\Services\Platform\Tenancy\SchoolTable;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\Concerns\UsesTwoSchools;
use Tests\Concerns\ValidatesOpenApiResponses;
use Tests\TestCase;

/**
 * Short days: a bell schedule replaced on one date. Lessons keep their order,
 * take the replacement's times and drop off the end when it has fewer periods.
 * Specification: engineering/specs/academics/short-days.md.
 */
class ShortDaysTest extends TestCase
{
    use UsesTwoSchools, ValidatesOpenApiResponses;

    private int $year;

    private int $grade;

    private int $teacher;

    private int $group;

    private int $room;

    private int $normal;

    private int $short;

    /** @var list<int> teaching period IDs of the normal Senior bells */
    private array $periods;

    /** @var list<int> lesson IDs in period order */
    private array $lessons;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        $this->travelTo(CarbonImmutable::parse('2026-02-10 07:50:00', 'Africa/Johannesburg'));
        SchoolMembership::query()->update(['starts_on' => '2026-01-01']);
        $this->year = $this->schoolA->ids['session_year'];
        $this->grade = (int) GradeLevel::where('code', '7')->value('id');
        $this->teacher = (int) Teacher::where('user_id', $this->schoolA->ids['tara_user'])->value('id');
        SessionYear::whereKey($this->year)->update(['start_date' => '2026-01-01', 'end_date' => '2026-12-31']);
        ClassSchool::whereKey($this->schoolA->ids['class_a'])->update(['grade_level_id' => $this->grade]);
        Enrolment::create(['learner_id' => $this->schoolA->ids['asha'], 'session_year_id' => $this->year, 'class_section_id' => $this->schoolA->ids['section_a'], 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Short day rehearsal']);
        $this->group = $this->makeGroup('Maths 7A', $this->schoolA->ids['maths']);
        $this->room = (int) app(TimetableConfiguration::class)->room($this->superAdmin(), ['name' => 'Room 1', 'capacity' => 35, 'type' => 'classroom', 'active' => true])->id;
        $normal = $this->bells('Normal Senior', [['P1', '07:00', '07:40', false], ['Break', '07:40', '07:50', true], ['P2', '07:50', '08:20', false], ['P3', '08:20', '08:55', false]]);
        $this->normal = (int) $normal->id;
        $this->periods = $normal->periods->where('is_break', false)->pluck('id')->map(fn ($id) => (int) $id)->values()->all();
        $this->short = (int) $this->bells('Short Senior', [['S1', '07:00', '07:25', false], ['Short break', '07:25', '07:35', true], ['S2', '07:35', '08:00', false]])->id;
        $saved = $this->place(array_map(fn ($period) => $this->row(['bell_period_id' => $period]), $this->periods));
        $this->lessons = array_map(fn ($lesson) => (int) $lesson['id'], $saved['lessons']);
    }

    private function makeGroup(string $name, int $subject, ?int $teacher = null): int
    {
        $service = app(TeachingGroups::class);
        $group = $service->save($this->superAdmin(), ['name' => $name, 'subject_id' => $subject, 'grade_level_id' => $this->grade, 'session_year_id' => $this->year, 'class_section_id' => $this->schoolA->ids['section_a'], 'active' => true]);
        $dates = ['starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Short day allocation'];
        $service->dated($this->superAdmin(), 'teachers', $dates + ['teacher_id' => $teacher ?? $this->teacher], $group->id);
        if ($teacher === null) {
            $service->dated($this->superAdmin(), 'memberships', $dates + ['learner_id' => $this->schoolA->ids['asha'], 'mark_rule' => 'catch_up'], $group->id);
        }

        return (int) $group->id;
    }

    private function bells(string $name, array $periods)
    {
        return app(TimetableConfiguration::class)->bells($this->superAdmin(), ['name' => $name, 'section' => 'Senior', 'session_year_id' => $this->year,
            'periods' => array_map(fn ($p) => ['name' => $p[0], 'starts_at' => $p[1], 'ends_at' => $p[2], 'is_break' => $p[3]], $periods)])->load('periods');
    }

    private function row(array $changes = []): array
    {
        return $changes + ['session_year_id' => $this->year, 'term_id' => null, 'teaching_group_id' => $this->group, 'class_section_id' => null,
            'teacher_id' => $this->teacher, 'room_id' => $this->room, 'bell_period_id' => $this->periods[0], 'starts_on' => '2026-02-10', 'ends_on' => '2026-03-31',
            'weekday' => 2, 'week_pattern' => 'all', 'active' => true];
    }

    private function place(array $rows): array
    {
        $engine = app(TimetableEngine::class);
        $preview = $engine->preview($this->superAdmin(), $rows);
        $this->assertTrue($preview['can_save'], json_encode($preview['conflicts']));

        return $engine->save($this->superAdmin(), $preview['rows'], $preview['fingerprint']);
    }

    private function shortDay(string $date = '2026-02-17', ?int $replacement = null, ?User $actor = null): array
    {
        return app(DayPatterns::class)->save($actor ?? $this->superAdmin(), ['on_date' => $date, 'reason' => 'Staff development afternoon',
            'replacements' => [['bell_schedule_id' => $this->normal, 'replacement_bell_schedule_id' => $replacement ?? $this->short]]]);
    }

    private function day(User $actor, string $date, ?int $learner = null): array
    {
        $week = app(TimetableQueries::class)->week($actor, $this->year, CarbonImmutable::parse($date)->startOfWeek()->toDateString(), $learner);

        return array_values(array_filter($week['lessons'], fn ($lesson) => $lesson['date'] === $date));
    }

    public function test_lessons_follow_the_short_bells_in_order_and_the_last_one_drops_off(): void
    {
        $this->shortDay();
        $parent = User::findOrFail($this->schoolA->ids['farid_user']);

        $short = $this->day($parent, '2026-02-17', $this->schoolA->ids['asha']);
        $this->assertSame([['07:00', '07:25', 'S1', 'Short Senior'], ['07:35', '08:00', 'S2', 'Short Senior']],
            array_map(fn ($lesson) => [$lesson['starts_at'], $lesson['ends_at'], $lesson['period_name'], $lesson['day_pattern']], $short));
        $this->assertSame(array_slice($this->lessons, 0, 2), array_column($short, 'id'), 'each lesson keeps its identity');
        $normal = $this->day($parent, '2026-02-24', $this->schoolA->ids['asha']);
        $this->assertSame(['07:00', '07:50', '08:20'], array_column($normal, 'starts_at'), 'other weeks keep the normal bells');
        $this->assertArrayNotHasKey('day_pattern', $normal[0]);

        $engine = app(TimetableEngine::class);
        $third = TimetableLesson::findOrFail($this->lessons[2])->getAttributes();
        $this->assertNotContains('2026-02-17', $engine->dates($third), 'the third lesson does not take place on the short day');
        $this->assertContains('2026-02-24', $engine->dates($third));
        $this->assertTrue($engine->scheduledOn($third, '2026-02-17'));
        $this->assertFalse($engine->occursOn($third, '2026-02-17'));

        // The next-lesson card uses the short-day time.
        $this->travelTo(CarbonImmutable::parse('2026-02-17 07:30:00', 'Africa/Johannesburg'));
        $this->assertSame('07:35', app(TimetableQueries::class)->next($parent, $this->schoolA->ids['asha'])['lesson']['starts_at']);
    }

    public function test_the_learner_parent_and_teacher_are_told_without_personal_details(): void
    {
        $this->shortDay();

        $notice = Notification::where('type', 'academics.timetable_changed')->sole();
        $this->assertSame('Short day on 17 Feb 2026', $notice->title);
        $this->assertStringNotContainsString('Staff development', json_encode($notice));
        $fanout = OutboxEvent::where('type', 'notification.requested')->where('payload->notification_id', $notice->id)->sole();
        foreach (['asha_user', 'farid_user', 'tara_user'] as $person) {
            $this->assertContains((int) $this->schoolA->ids[$person], $fanout->payload['user_ids']);
        }
        $this->assertNotContains((int) $this->schoolA->ids['ben_user'], $fanout->payload['user_ids']);
        $changed = OutboxEvent::where('type', 'academics.timetable_changed')->latest('id')->firstOrFail();
        $this->assertSame($this->lessons, $changed->payload['lesson_ids']);
    }

    public function test_removing_the_short_day_restores_the_normal_bells(): void
    {
        $override = $this->shortDay()[0];

        app(DayPatterns::class)->remove($this->superAdmin(), (int) $override->id, $override->revision);

        $this->assertSame(['07:00', '07:50', '08:20'], array_column($this->day($this->superAdmin(), '2026-02-17'), 'starts_at'));
        $this->assertSame(0, BellScheduleOverride::count());
        $this->assertSame('Normal bells on 17 Feb 2026', Notification::where('type', 'academics.timetable_changed')->latest('id')->firstOrFail()->title);
        $this->expectException(ModelNotFoundException::class);
        app(DayPatterns::class)->remove($this->superAdmin(), (int) $override->id, $override->revision);
    }

    public function test_bells_that_move_a_lesson_onto_another_one_are_refused_and_nothing_is_saved(): void
    {
        $junior = app(TimetableConfiguration::class)->bells($this->superAdmin(), ['name' => 'Junior', 'section' => 'Junior', 'session_year_id' => $this->year,
            'periods' => [['name' => 'J1', 'starts_at' => '07:40', 'ends_at' => '07:50', 'is_break' => false]]])->periods->sole();
        $this->place([$this->row(['teaching_group_id' => $this->makeGroup('Science 7A', $this->schoolA->ids['science']), 'bell_period_id' => $junior->id])]);
        $assembly = $this->bells('Assembly Senior', [['A1', '07:35', '08:15', false]]);

        try {
            $this->shortDay('2026-02-17', (int) $assembly->id);
            $this->fail('A clashing short day was saved');
        } catch (ValidationException $error) {
            $this->assertStringContainsStringIgnoringCase('teacher', $error->errors()['short_day'][0]);
        }
        $this->assertSame(0, BellScheduleOverride::count());
        $this->assertSame(0, Notification::where('type', 'academics.timetable_changed')->count());
        $this->assertSame([], app(DayPatterns::class)->clashesOn('2026-02-17'), 'the saved timetable is unchanged');
    }

    public function test_bells_used_by_a_short_day_cannot_be_edited_without_the_clash_check(): void
    {
        $this->shortDay();
        $schedule = BellSchedule::findOrFail($this->short);

        try {
            app(TimetableConfiguration::class)->bells($this->superAdmin(), ['name' => 'Short Senior', 'section' => 'Senior', 'session_year_id' => $this->year,
                'revision' => $schedule->revision, 'periods' => [['name' => 'S1', 'starts_at' => '06:30', 'ends_at' => '09:00', 'is_break' => false]]], $this->short);
            $this->fail('The bells of a planned short day were edited');
        } catch (HttpException $error) {
            $this->assertSame(409, $error->getStatusCode());
        }
        $this->assertSame(['07:00', '07:35'], array_column($this->day($this->superAdmin(), '2026-02-17'), 'starts_at'));
        $spare = $this->bells('Spare Senior', [['X1', '07:00', '07:30', false]]);
        $edited = app(TimetableConfiguration::class)->bells($this->superAdmin(), ['name' => 'Spare Senior', 'section' => 'Senior', 'session_year_id' => $this->year,
            'revision' => $spare->revision, 'periods' => [['name' => 'X1', 'starts_at' => '07:05', 'ends_at' => '07:30', 'is_break' => false]]], (int) $spare->id);
        $this->assertSame(2, $edited->revision, 'unused bells can still be edited');
    }

    public function test_relief_cover_snapshots_use_short_day_times_and_go_stale_when_a_short_day_is_planned(): void
    {
        $absence = $this->coverFor($this->lessons[1]);
        $this->shortDay();

        $this->assertSame('needs_review', app(CoverQueries::class)->occurrence($this->superAdmin(), $this->coverId($this->lessons[1]))['state'], 'the substitute was told the normal time');
        $absence->refresh();
        app(ReliefCover::class)->change($this->superAdmin(), (int) $absence->id, $absence->revision, 'refresh', 'Short day', (string) Str::uuid());
        $source = app(CoverQueries::class)->occurrence($this->superAdmin(), $this->coverId($this->lessons[1]))['source'];
        $this->assertSame(['07:35', '08:00'], [$source['starts_at'], $source['ends_at']]);
        $this->assertSame('cancelled', CoverAssignment::where('timetable_lesson_id', $this->lessons[2])->sole()->state, 'the lesson that drops off needs no cover');
    }

    public function test_a_short_day_that_moves_covered_lessons_onto_the_substitutes_own_lesson_is_refused(): void
    {
        $substitute = (int) Teacher::where('user_id', $this->schoolA->ids['theo_user'])->value('id');
        $junior = app(TimetableConfiguration::class)->bells($this->superAdmin(), ['name' => 'Junior', 'section' => 'Junior', 'session_year_id' => $this->year,
            'periods' => [['name' => 'J1', 'starts_at' => '07:40', 'ends_at' => '07:50', 'is_break' => false]]])->periods->sole();
        $room = (int) app(TimetableConfiguration::class)->room($this->superAdmin(), ['name' => 'Room 2', 'capacity' => 35, 'type' => 'classroom', 'active' => true])->id;
        $this->place([$this->row(['teaching_group_id' => $this->makeGroup('Science 6B', $this->schoolA->ids['science'], $substitute), 'teacher_id' => $substitute,
            'room_id' => $room, 'bell_period_id' => $junior->id])]);
        $this->coverFor($this->lessons[1], $substitute);

        try {
            $this->shortDay();
            $this->fail('The short day moved the covered lesson onto the substitute\'s own lesson');
        } catch (ValidationException $error) {
            $this->assertStringContainsStringIgnoringCase('teacher', $error->errors()['short_day'][0]);
        }
        $this->assertSame(0, BellScheduleOverride::count());
        $this->assertSame('assigned', CoverAssignment::findOrFail($this->coverId($this->lessons[1]))->state);
    }

    /** Requests cover for the teacher on 17 February and assigns one lesson to a substitute (Theo by default). */
    private function coverFor(int $lesson, ?int $substitute = null): CoverAbsence
    {
        config(['modules.relief_cover' => ['always_on' => true]]);
        SchoolTable::query('timetables')->delete();
        $absence = app(ReliefCover::class)->request($this->superAdmin(), ['session_year_id' => $this->year, 'teacher_id' => $this->teacher,
            'starts_on' => '2026-02-17', 'ends_on' => '2026-02-17', 'reason' => 'Course'], (string) Str::uuid());
        $cover = CoverAssignment::findOrFail($this->coverId($lesson));
        app(ReliefCover::class)->assign($this->superAdmin(), (int) $cover->id, $substitute ?? (int) Teacher::where('user_id', $this->schoolA->ids['theo_user'])->value('id'),
            $cover->revision, (string) Str::uuid());

        return $absence;
    }

    private function coverId(int $lesson): int
    {
        return (int) CoverAssignment::where('timetable_lesson_id', $lesson)->whereDate('on_date', '2026-02-17')->value('id');
    }

    public function test_placements_are_checked_against_short_day_times_and_stale_previews_are_refused(): void
    {
        $engine = app(TimetableEngine::class);
        $junior = app(TimetableConfiguration::class)->bells($this->superAdmin(), ['name' => 'Junior', 'section' => 'Junior', 'session_year_id' => $this->year,
            'periods' => [['name' => 'J1', 'starts_at' => '07:25', 'ends_at' => '07:35', 'is_break' => false]]])->periods->sole();
        $candidate = $this->row(['teaching_group_id' => $this->makeGroup('Science 7A', $this->schoolA->ids['science']), 'bell_period_id' => $junior->id, 'starts_on' => '2026-02-17', 'ends_on' => '2026-02-17']);
        $stale = $engine->preview($this->superAdmin(), [$candidate]);
        $this->assertFalse($stale['can_save'], 'normal bells: P1 runs until 07:40');

        $this->shortDay();
        $preview = app(TimetableEngine::class)->preview($this->superAdmin(), [$candidate]);
        $this->assertTrue($preview['can_save'], 'on the short day the teacher is on break from 07:25 to 07:35');
        $this->assertNotSame($stale['fingerprint'], $preview['fingerprint']);
        $this->place([$candidate]);

        $later = $this->bells('Later Senior', [['L1', '07:20', '08:00', false]]);
        $this->expectException(ValidationException::class);
        app(DayPatterns::class)->save($this->superAdmin(), ['on_date' => '2026-02-17', 'reason' => 'Changed again',
            'replacements' => [['bell_schedule_id' => $this->normal, 'replacement_bell_schedule_id' => $later->id]]]);
    }

    public function test_only_timetable_managers_can_plan_short_days_and_only_for_this_school_and_the_future(): void
    {
        foreach (['tara_user', 'farid_user', 'asha_user'] as $person) {
            try {
                $this->shortDay(actor: User::findOrFail($this->schoolA->ids[$person]));
                $this->fail($person.' planned a short day');
            } catch (HttpException $error) {
                $this->assertSame(403, $error->getStatusCode());
            }
        }
        try {
            $this->shortDay('2026-02-03');
            $this->fail('A past short day was saved');
        } catch (ValidationException $error) {
            $this->assertArrayHasKey('on_date', $error->errors());
        }
        foreach ([[$this->normal, $this->normal], [$this->normal, null]] as [$base, $replacement]) {
            try {
                app(DayPatterns::class)->save($this->superAdmin(), ['on_date' => '2026-02-17', 'reason' => 'Invalid', 'replacements' => [['bell_schedule_id' => $base, 'replacement_bell_schedule_id' => $replacement]]]);
                $this->fail('An invalid replacement was saved');
            } catch (ValidationException $error) {
                $this->assertArrayHasKey('replacements', $error->errors());
            }
        }
        $foreign = app(CurrentSchool::class)->run(2, function () {
            $schedule = BellSchedule::create(['name' => 'Baobab short', 'section' => 'Senior', 'session_year_id' => $this->schoolB->ids['session_year'], 'revision' => 1]);
            $schedule->periods()->create(['name' => 'B1', 'position' => 1, 'starts_at' => '08:00', 'ends_at' => '08:30', 'is_break' => false]);

            return (int) $schedule->id;
        });
        $context = ['session_year_id' => $this->year, 'week_on' => '2026-02-16'];
        $this->actingAs($this->superAdmin(), 'web')->post(route('academic-timetable.short-day'), $context + ['on_date' => '2026-02-17', 'reason' => 'Bypass',
            'replacements' => [['bell_schedule_id' => $this->normal, 'replacement_bell_schedule_id' => $foreign]]])->assertNotFound();
        $this->assertSame(0, BellScheduleOverride::count());
    }

    public function test_the_workbench_plans_shows_and_removes_a_short_day(): void
    {
        $context = ['session_year_id' => $this->year, 'week_on' => '2026-02-16'];
        $this->actingAs($this->superAdmin(), 'web')->get(route('academic-timetable.index', $context))->assertOk()->assertSee('Plan a short day')->assertSee('Bells instead of Normal Senior');
        $this->post(route('academic-timetable.short-day'), $context + ['on_date' => '2026-02-17', 'reason' => 'Sports day',
            'replacements' => [['bell_schedule_id' => $this->normal, 'replacement_bell_schedule_id' => $this->short], ['bell_schedule_id' => $this->short, 'replacement_bell_schedule_id' => null]]])->assertRedirect();
        $override = BellScheduleOverride::sole();
        $this->get(route('academic-timetable.index', $context))->assertOk()->assertSee('Short day: Short Senior instead of Normal Senior')->assertSee('07:35–08:00');
        foreach (['tara_user', 'farid_user'] as $person) {
            $write = $this->actingAs(User::findOrFail($this->schoolA->ids[$person]), 'web')->post(route('academic-timetable.short-day-remove'), $context + ['override_id' => $override->id, 'revision' => $override->revision]);
            $this->assertContains($write->getStatusCode(), [302, 403]);
            $this->assertSame(1, BellScheduleOverride::count());
        }
        $this->actingAs($this->superAdmin(), 'web')->post(route('academic-timetable.short-day-remove'), $context + ['override_id' => $override->id, 'revision' => 99])->assertRedirect()->assertSessionHasErrors('short_day');
        $this->post(route('academic-timetable.short-day-remove'), $context + ['override_id' => $override->id, 'revision' => $override->revision])->assertRedirect();
        $this->assertSame(0, BellScheduleOverride::count());
    }

    public function test_the_family_week_api_reports_short_day_times_within_the_contract(): void
    {
        $this->shortDay();
        $this->app['auth']->forgetGuards();
        $parent = User::findOrFail($this->schoolA->ids['farid_user']);

        $lessons = $this->flushHeaders()->withToken($parent->createToken('short-day')->plainTextToken)
            ->getJson('/api/v2/academics/timetable/week?session_year_id='.$this->year.'&starts_on=2026-02-16&learner_id='.$this->schoolA->ids['asha'])
            ->assertOk()->json('data.lessons');

        $this->assertSame([['07:00', 'Short Senior'], ['07:35', 'Short Senior']], array_map(fn ($lesson) => [$lesson['starts_at'], $lesson['day_pattern']], $lessons));
    }
}
