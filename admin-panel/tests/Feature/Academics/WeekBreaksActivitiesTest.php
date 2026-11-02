<?php

declare(strict_types=1);

namespace Tests\Feature\Academics;

use App\Models\Academics\GradeLevel;
use App\Models\ClassSchool;
use App\Models\ExamTimetable;
use App\Models\People\Enrolment;
use App\Models\Platform\SchoolMembership;
use App\Models\SessionYear;
use App\Models\Teacher;
use App\Models\Term;
use App\Models\User;
use App\Services\Academics\TeachingGroups;
use App\Services\Academics\TimetableConfiguration;
use App\Services\Academics\TimetableEngine;
use App\Services\Activities\ActivityGroups;
use App\Services\Platform\Modules\ModuleGate;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Illuminate\Validation\ValidationException;
use Tests\Concerns\UsesTwoSchools;
use Tests\Concerns\ValidatesOpenApiResponses;
use Tests\TestCase;

/**
 * Breaks and activities in the learner and family week view (Action Plan 1.6).
 * engineering/specs/academics/timetable.md.
 */
class WeekBreaksActivitiesTest extends TestCase
{
    use UsesTwoSchools, ValidatesOpenApiResponses;

    private int $year;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        $this->travelTo(CarbonImmutable::parse('2026-02-10 07:50:00', 'Africa/Johannesburg'));
        SchoolMembership::query()->update(['starts_on' => '2026-01-01']);
        $this->year = $this->schoolA->ids['session_year'];
        $grade = (int) GradeLevel::where('code', '7')->value('id');
        $teacher = (int) Teacher::where('user_id', $this->schoolA->ids['tara_user'])->value('id');
        SessionYear::whereKey($this->year)->update(['start_date' => '2026-01-01', 'end_date' => '2026-12-31']);
        Term::whereKey($this->schoolA->ids['term'])->update(['start_date' => '2026-01-01', 'end_date' => '2026-03-31']);
        ClassSchool::whereKey($this->schoolA->ids['class_a'])->update(['grade_level_id' => $grade]);
        Enrolment::create(['learner_id' => $this->schoolA->ids['asha'], 'session_year_id' => $this->year, 'class_section_id' => $this->schoolA->ids['section_a'], 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Calendar rehearsal']);

        $groups = app(TeachingGroups::class);
        $group = $groups->save($this->superAdmin(), ['name' => 'Maths 7A', 'subject_id' => $this->schoolA->ids['maths'], 'grade_level_id' => $grade, 'session_year_id' => $this->year, 'class_section_id' => $this->schoolA->ids['section_a'], 'active' => true]);
        $dates = ['starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Calendar allocation'];
        $groups->dated($this->superAdmin(), 'teachers', $dates + ['teacher_id' => $teacher], $group->id);
        $groups->dated($this->superAdmin(), 'memberships', $dates + ['learner_id' => $this->schoolA->ids['asha'], 'mark_rule' => 'catch_up'], $group->id);
        $room = (int) app(TimetableConfiguration::class)->room($this->superAdmin(), ['name' => 'Room 1', 'capacity' => 35, 'type' => 'classroom', 'active' => true])->id;
        $period = app(TimetableConfiguration::class)->bells($this->superAdmin(), ['name' => 'Bells', 'section' => 'Senior', 'session_year_id' => $this->year,
            'periods' => [['name' => 'Period 1', 'starts_at' => '08:00', 'ends_at' => '08:45', 'is_break' => false], ['name' => 'Long break', 'starts_at' => '08:45', 'ends_at' => '09:05', 'is_break' => true]]])->periods->firstWhere('is_break', false)->id;
        $engine = app(TimetableEngine::class);
        $preview = $engine->preview($this->superAdmin(), [['session_year_id' => $this->year, 'term_id' => $this->schoolA->ids['term'], 'teaching_group_id' => (int) $group->id, 'class_section_id' => null,
            'teacher_id' => $teacher, 'room_id' => $room, 'bell_period_id' => (int) $period, 'starts_on' => '2026-02-10', 'ends_on' => '2026-03-31', 'weekday' => 2, 'week_pattern' => 'all', 'active' => true]]);
        $this->assertTrue($preview['can_save'], json_encode($preview['conflicts']));
        $engine->save($this->superAdmin(), $preview['rows'], $preview['fingerprint']);

        $this->superAdmin()->givePermissionTo('activity-groups-manage');
        app(ModuleGate::class)->change('groups', true, $this->superAdmin());
        (new ExamTimetable)->forceFill(['exam_id' => $this->schoolA->ids['exam'], 'class_id' => $this->schoolA->ids['class_a'], 'subject_id' => $this->schoolA->ids['maths'], 'total_marks' => 50, 'passing_marks' => 20,
            'date' => '2026-02-20', 'start_time' => '09:00:00', 'end_time' => '11:00:00', 'venue' => 'Hall, east wing', 'session_year_id' => $this->year])->save();
    }

    private function person(string $key): User
    {
        return User::findOrFail($this->schoolA->ids[$key.'_user']);
    }

    private function group(string $name, bool $asha = true): int
    {
        $service = app(ActivityGroups::class);
        $group = $service->save($this->superAdmin(), null, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'session_year_id' => $this->year, 'parent_id' => null, 'name' => $name,
            'discipline' => 'Sport', 'season' => '2026', 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'gender' => 'any', 'minimum_grade' => null, 'maximum_grade' => null,
            'minimum_age' => null, 'maximum_age' => null, 'active' => true]);
        if ($asha) {
            $service->allocation($this->superAdmin(), (int) $group['id'], null, false, ['operation_id' => (string) Str::uuid(), 'revision' => 0, 'starts_on' => '2026-01-01',
                'ends_on' => '2026-12-31', 'reason' => 'Synthetic member', 'learner_id' => $this->schoolA->ids['asha'], 'role' => 'member']);
        }

        return (int) $group['id'];
    }

    private function activitySession(int $group, array $extra = []): array
    {
        return app(ActivityGroups::class)->session($this->superAdmin(), $group, null, $extra + ['operation_id' => (string) Str::uuid(), 'revision' => 0,
            'on_date' => '2026-02-10', 'name' => 'Practice', 'venue' => 'Field', 'status' => 'scheduled']);
    }

    private function week(string $person, ?int $learner = null, int $school = 1): TestResponse
    {
        $this->app['auth']->forgetGuards();
        $tokens = $school === 1 ? $this->schoolA->tokens : $this->schoolB->tokens;

        return $this->getJson('/api/v2/academics/timetable/week?starts_on=2026-02-09'.($learner === null ? '' : '&learner_id='.$learner),
            ['Authorization' => 'Bearer '.$tokens[$person], 'X-School-Id' => (string) $school]);
    }

    public function test_a_familys_week_shows_named_breaks_and_the_learners_activities(): void
    {
        $hockey = $this->group('Hockey');
        $timed = $this->activitySession($hockey, ['starts_at' => '14:30', 'ends_at' => '16:00']);
        $this->assertSame(['14:30', '16:00'], [$timed['after']['starts_at'] ?? $timed['starts_at'] ?? null, $timed['after']['ends_at'] ?? $timed['ends_at'] ?? null]);
        $this->activitySession($hockey, ['on_date' => '2026-02-12', 'name' => 'Match day']);
        $this->activitySession($hockey, ['on_date' => '2026-02-11', 'name' => 'Rained out', 'status' => 'cancelled']);
        $this->activitySession($hockey, ['on_date' => '2026-02-17', 'name' => 'Next week']);
        $this->activitySession($this->group('Chess', false), ['name' => 'Not Asha\'s group']);

        $week = $this->week('farid', $this->schoolA->ids['asha'])->assertOk();
        $this->assertSame([['date' => '2026-02-10', 'name' => 'Long break', 'starts_at' => '08:45', 'ends_at' => '09:05']], $week->json('data.breaks'));
        $activities = $week->json('data.activities');
        $this->assertSame(['Practice', 'Match day'], array_column($activities, 'name'));
        $this->assertSame(['2026-02-10', '14:30', '16:00', 'Hockey', 'Field'], [$activities[0]['date'], $activities[0]['starts_at'], $activities[0]['ends_at'], $activities[0]['group_name'], $activities[0]['venue']]);
        $this->assertNull($activities[1]['starts_at'], 'a session without times is all day');
        $this->assertCount(1, $week->json('data.lessons'));

        // The learner sees the same; a teacher's own week has neither.
        $this->assertSame($activities, $this->week('asha')->assertOk()->json('data.activities'));
        $teacher = $this->week('tara')->assertOk();
        $this->assertSame([], $teacher->json('data.breaks'));
        $this->assertSame([], $teacher->json('data.activities'));
    }

    public function test_older_clients_keep_saved_session_times_and_explicit_nulls_clear_them(): void
    {
        $hockey = $this->group('Hockey');
        $timed = $this->activitySession($hockey, ['starts_at' => '14:30', 'ends_at' => '16:00']);
        $service = app(ActivityGroups::class);
        $legacy = ['operation_id' => (string) Str::uuid(), 'revision' => $timed['revision'],
            'on_date' => '2026-02-10', 'name' => 'Renamed practice', 'venue' => 'Field', 'status' => 'scheduled'];
        $updated = $service->session($this->superAdmin(), $hockey, $timed['id'], $legacy);
        $this->assertSame(['14:30', '16:00'], [$updated['starts_at'], $updated['ends_at']], 'An older client does not send the new time fields');
        $cleared = $service->session($this->superAdmin(), $hockey, $timed['id'], [
            'operation_id' => (string) Str::uuid(), 'revision' => $updated['revision'], 'starts_at' => null, 'ends_at' => null,
        ] + $legacy);
        $this->assertSame([null, null], [$cleared['starts_at'], $cleared['ends_at']]);
    }

    public function test_rollback_preserves_saved_activity_session_times(): void
    {
        $session = $this->activitySession($this->group('Hockey'), ['starts_at' => '14:30', 'ends_at' => '16:00']);
        $migration = require database_path('migrations/2026_11_02_140000_add_times_to_activity_sessions.php');
        try {
            $migration->down();
            $this->fail('A rollback must not silently discard saved session times.');
        } catch (\RuntimeException $error) {
            $this->assertStringContainsString('Retain saved session times', $error->getMessage());
        }
        $this->assertSame('14:30:00', DB::table('activity_sessions')->where('id', $session['id'])->value('starts_at'));
    }

    public function test_session_times_are_optional_but_must_make_sense(): void
    {
        $hockey = $this->group('Hockey');
        foreach ([['starts_at' => '15:00', 'ends_at' => '14:00'], ['starts_at' => '15:00'], ['ends_at' => '15:00'], ['starts_at' => '3pm', 'ends_at' => '4pm']] as $bad) {
            try {
                $this->activitySession($hockey, $bad);
                $this->fail('Expected validation to refuse '.json_encode($bad));
            } catch (ValidationException) {
                $this->addToAssertionCount(1);
            }
        }
        $this->assertSame([], app(ActivityGroups::class)->detail($this->superAdmin(), $hockey)['sessions'] ?? []);
    }

    public function test_other_families_schools_and_switched_off_groups_see_none_of_it(): void
    {
        $hockey = $this->group('Hockey');
        $this->activitySession($hockey, ['starts_at' => '14:30', 'ends_at' => '16:00']);
        // Wrong owner: Gina is not Asha's guardian.
        $this->assertContains($this->week('gina', $this->schoolA->ids['asha'])->status(), [403, 404, 422]);
        // Wrong school: Farid at School B asking for School A's learner.
        $this->assertContains($this->week('farid', $this->schoolA->ids['asha'], 2)->status(), [403, 404, 422]);
        // The groups module off: no activities, breaks still shown.
        app(ModuleGate::class)->change('groups', false, $this->superAdmin());
        $week = $this->week('farid', $this->schoolA->ids['asha'])->assertOk();
        $this->assertSame([], $week->json('data.activities'));
        $this->assertCount(1, $week->json('data.breaks'));
    }
}
