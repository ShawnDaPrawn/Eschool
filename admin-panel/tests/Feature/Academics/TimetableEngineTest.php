<?php

declare(strict_types=1);

namespace Tests\Feature\Academics;

use App\Actions\Transfers\TransferClassTimetable;
use App\Models\Academics\BellPeriod;
use App\Models\Academics\BellSchedule;
use App\Models\Academics\GradeLevel;
use App\Models\Academics\Room;
use App\Models\Academics\TeachingAllocation;
use App\Models\Academics\TeachingGroup;
use App\Models\Academics\TimetableLesson;
use App\Models\ClassSchool;
use App\Models\ClassSessionConfig;
use App\Models\People\Enrolment;
use App\Models\Platform\GuardianLink;
use App\Models\Platform\Role;
use App\Models\Platform\SchoolMembership;
use App\Models\SessionYear;
use App\Models\SubjectTeacher;
use App\Models\Teacher;
use App\Models\Term;
use App\Models\Timetable;
use App\Models\User;
use App\Services\Academics\TeachingGroups;
use App\Services\Academics\TimetableConfiguration;
use App\Services\Academics\TimetableEngine;
use App\Services\Academics\TimetableImport;
use App\Services\Academics\TimetableProjection;
use App\Services\Academics\TimetableQueries;
use App\Services\Platform\Tenancy\CurrentSchool;
use App\Services\Platform\Tenancy\SchoolTable;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\Concerns\UsesTwoSchools;
use Tests\Concerns\ValidatesOpenApiResponses;
use Tests\TestCase;

class TimetableEngineTest extends TestCase
{
    use UsesTwoSchools, ValidatesOpenApiResponses;

    private int $year;

    private int $grade;

    private int $teacher;

    private int $group;

    private int $room;

    private int $period;

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
        Term::whereKey($this->schoolA->ids['term'])->update(['start_date' => '2026-01-01', 'end_date' => '2026-03-31']);
        ClassSchool::whereKey($this->schoolA->ids['class_a'])->update(['grade_level_id' => $this->grade]);
        Enrolment::create(['learner_id' => $this->schoolA->ids['asha'], 'session_year_id' => $this->year, 'class_section_id' => $this->schoolA->ids['section_a'], 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Timetable rehearsal']);
        $this->group = $this->makeGroup('Maths 7A', $this->schoolA->ids['maths'], $this->teacher);
        $this->room = $this->makeRoom('Room 1');
        $this->period = $this->makePeriod('08:00', '08:45');
    }

    private function makeGroup(string $name, int $subject, int $teacher): int
    {
        $service = app(TeachingGroups::class);
        $group = $service->save($this->superAdmin(), ['name' => $name, 'subject_id' => $subject, 'grade_level_id' => $this->grade, 'session_year_id' => $this->year, 'class_section_id' => $this->schoolA->ids['section_a'], 'active' => true]);
        $dates = ['starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Timetable allocation'];
        $service->dated($this->superAdmin(), 'teachers', $dates + ['teacher_id' => $teacher], $group->id);
        $service->dated($this->superAdmin(), 'memberships', $dates + ['learner_id' => $this->schoolA->ids['asha'], 'mark_rule' => 'catch_up'], $group->id);

        return (int) $group->id;
    }

    private function makeRoom(string $name): int
    {
        return (int) app(TimetableConfiguration::class)->room($this->superAdmin(), ['name' => $name, 'capacity' => 35, 'type' => 'classroom', 'active' => true])->id;
    }

    private function makePeriod(string $start, string $end): int
    {
        return (int) app(TimetableConfiguration::class)->bells($this->superAdmin(), ['name' => 'Bells '.$start, 'section' => 'Senior', 'session_year_id' => $this->year,
            'periods' => [['name' => 'Period 1', 'starts_at' => $start, 'ends_at' => $end, 'is_break' => false]]])->periods->sole()->id;
    }

    private function row(array $changes = []): array
    {
        return $changes + ['session_year_id' => $this->year, 'term_id' => $this->schoolA->ids['term'], 'teaching_group_id' => $this->group, 'class_section_id' => null,
            'teacher_id' => $this->teacher, 'room_id' => $this->room, 'bell_period_id' => $this->period, 'starts_on' => '2026-02-10', 'ends_on' => '2026-02-10',
            'weekday' => 2, 'week_pattern' => 'all', 'active' => true];
    }

    private function save(array $rows): array
    {
        $engine = app(TimetableEngine::class);
        $preview = $engine->preview($this->superAdmin(), $rows);
        $this->assertTrue($preview['can_save'], json_encode($preview['conflicts']));

        return $engine->save($this->superAdmin(), $preview['rows'], $preview['fingerprint']);
    }

    public function test_placement_week_and_next_room_are_visible_only_to_the_authorized_family_and_teacher(): void
    {
        $saved = $this->save([$this->row()]);
        $this->assertSame('Room 1', $saved['lessons'][0]['room_name']);
        $queries = app(TimetableQueries::class);
        $parent = User::findOrFail($this->schoolA->ids['farid_user']);
        $week = $queries->week($parent, $this->year, '2026-02-09', $this->schoolA->ids['asha']);
        $this->assertCount(1, $week['lessons']);
        $this->assertFalse($week['can_manage']);
        $this->assertArrayNotHasKey('learner_ids', $week['lessons'][0]);
        $this->assertCount(1, $queries->week(User::findOrFail($this->schoolA->ids['tara_user']), $this->year, '2026-02-09')['lessons']);
        $this->assertSame('Room 1', $queries->next($parent, $this->schoolA->ids['asha'])['lesson']['room_name']);
        $this->assertSame([], $queries->week($parent, $this->year, '2026-02-09', $this->schoolA->ids['cara'])['lessons']);
    }

    public function test_overlapping_different_schedules_detect_teacher_room_group_and_shared_learners(): void
    {
        $this->save([$this->row()]);
        $period = $this->makePeriod('08:30', '09:15');
        $preview = app(TimetableEngine::class)->preview($this->superAdmin(), [$this->row(['bell_period_id' => $period])]);
        $this->assertFalse($preview['can_save']);
        $this->assertContains('teacher', $preview['conflicts'][0]['reasons']);
        $this->assertContains('room', $preview['conflicts'][0]['reasons']);
        $this->assertContains('group', $preview['conflicts'][0]['reasons']);
        $otherTeacher = (int) Teacher::where('user_id', $this->schoolA->ids['theo_user'])->value('id');
        $other = $this->makeGroup('Science 7A', $this->schoolA->ids['science'], $otherTeacher);
        $preview = app(TimetableEngine::class)->preview($this->superAdmin(), [$this->row(['teaching_group_id' => $other, 'teacher_id' => $otherTeacher, 'room_id' => $this->makeRoom('Lab'), 'bell_period_id' => $period])]);
        $this->assertContains('learner', $preview['conflicts'][0]['reasons']);
    }

    public function test_alternate_weeks_and_adjacent_times_are_disjoint_but_date_overrides_are_checked(): void
    {
        $one = $this->row(['starts_on' => '2026-02-09', 'ends_on' => '2026-02-20', 'weekday' => 1, 'week_pattern' => 'odd']);
        $two = array_replace($one, ['week_pattern' => 'even']);
        $this->save([$one, $two]);
        $this->assertSame(2, TimetableLesson::count());
        $preview = app(TimetableEngine::class)->preview($this->superAdmin(), [array_replace($one, ['occurs_on' => '2026-02-09'])]);
        $this->assertFalse($preview['can_save']);
        $this->assertSame('2026-02-09', $preview['conflicts'][0]['date']);
    }

    public function test_preview_fingerprints_revision_conflicts_and_cancellation_never_overwrite_another_edit(): void
    {
        $engine = app(TimetableEngine::class);
        $before = $engine->preview($this->superAdmin(), [$this->row()]);
        $this->makeRoom('New room');
        try {
            $engine->save($this->superAdmin(), $before['rows'], $before['fingerprint']);
            $this->fail('Stale preview');
        } catch (HttpException $error) {
            $this->assertSame(409, $error->getStatusCode());
        }
        $this->assertSame(0, TimetableLesson::count());
        $lesson = $this->save([$this->row()])['lessons'][0];
        $this->save([$this->row(['id' => $lesson['id'], 'revision' => 1, 'active' => false])]);
        $this->assertFalse(TimetableLesson::query()->sole()->active);
        $this->assertSame(0, Timetable::whereNotNull('timetable_lesson_id')->count());
        try {
            $engine->preview($this->superAdmin(), [$this->row(['id' => $lesson['id'], 'revision' => 1])]);
            $this->fail('Stale lesson');
        } catch (HttpException $error) {
            $this->assertSame(409, $error->getStatusCode());
        }
    }

    public function test_import_maps_exported_labels_and_refuses_partial_or_unmapped_rows_without_writes(): void
    {
        $input = ['session_year_id' => $this->year, 'csv' => "Class,Teacher,Room,Day,Hour\n7A,Tara,R1,Tuesday,P1\n", 'defaults' => $this->row(),
            'columns' => ['teaching_group_id' => 'Class', 'teacher_id' => 'Teacher', 'room_id' => 'Room', 'weekday' => 'Day', 'bell_period_id' => 'Hour'],
            'values' => ['teaching_group_id' => ['7A' => $this->group], 'teacher_id' => ['Tara' => $this->teacher], 'room_id' => ['R1' => $this->room], 'weekday' => ['Tuesday' => 2], 'bell_period_id' => ['P1' => $this->period]]];
        $preview = app(TimetableImport::class)->preview($this->superAdmin(), $input);
        $this->assertTrue($preview['can_save']);
        $this->assertSame(1, $preview['imported_rows']);
        $this->assertSame(0, TimetableLesson::count());
        $input['csv'] .= "7B,Unknown,R1,Tuesday,P1\n";
        try {
            app(TimetableImport::class)->preview($this->superAdmin(), $input);
            $this->fail('An unmapped export must be refused');
        } catch (ValidationException) {
            $this->assertSame(0, TimetableLesson::count());
        }
    }

    public function test_existing_app_projection_is_stable_and_cannot_be_edited_or_deleted_by_old_writers(): void
    {
        $this->save([$this->row()]);
        $projection = Timetable::whereNotNull('timetable_lesson_id')->sole();
        $this->assertSame('Room 1', $projection->room_name);
        app(TimetableProjection::class)->refresh();
        $this->assertSame($projection->id, Timetable::whereNotNull('timetable_lesson_id')->sole()->id);
        $projection->live_class_url = 'https://school.test/lesson';
        $projection->save();
        foreach ([fn () => $projection->forceFill(['start_time' => '07:30'])->save(), fn () => $projection->fresh()->delete()] as $attempt) {
            try {
                $attempt();
                $this->fail('Protected projection');
            } catch (HttpException $error) {
                $this->assertSame(409, $error->getStatusCode());
            }
        }
        $this->assertSame('08:00', substr(Timetable::whereNotNull('timetable_lesson_id')->sole()->start_time, 0, 5));
    }

    public function test_new_year_carries_legacy_entries_without_reusing_an_old_canonical_calendar(): void
    {
        $this->save([$this->row()]);
        $projection = Timetable::whereNotNull('timetable_lesson_id')->sole();
        $year = SessionYear::create(['name' => '2027', 'start_date' => '2027-01-01', 'end_date' => '2027-12-31']);
        $term = Term::create(['session_year_id' => $year->id, 'name' => 'Term 1', 'start_date' => '2027-01-01', 'end_date' => '2027-03-31', 'grade_from' => 0, 'grade_to' => 12]);
        // A real target assignment ensures the carry is not merely skipped for a missing map.
        $subject = SubjectTeacher::findOrFail($projection->subject_teacher_id);
        $attributes = collect($subject->getAttributes())->except(['id', 'created_at', 'updated_at'])->all();
        $attributes['session_year_id'] = $year->id;
        $attributes['term_id'] = $subject->term_id === null ? null : $term->id;
        $attributes['teaching_allocation_id'] = null;
        SchoolTable::query('subject_teachers')->insert($attributes);
        $legacy = collect($projection->getAttributes())->except(['id', 'created_at', 'updated_at'])->all();
        $legacy['timetable_lesson_id'] = null;
        $legacy['day'] = 'Wednesday';
        SchoolTable::query('timetables')->insert($legacy);
        app(TransferClassTimetable::class)->execute($this->year, $year->id);
        $this->assertGreaterThan(0, Timetable::where('session_year_id', $year->id)->whereNull('timetable_lesson_id')->count());
        $this->assertSame(1, Timetable::where('session_year_id', $year->id)->where('room_name', 'Room 1')->where('day', 'Wednesday')->count());
        $this->assertSame(0, SchoolTable::query('timetables')->where('session_year_id', $year->id)->whereNotNull('timetable_lesson_id')->count());
        // Even an old bulk writer cannot expose a projection against the wrong source year.
        $invalid = collect($projection->getAttributes())->except(['id', 'created_at', 'updated_at'])->all();
        $invalid['session_year_id'] = $year->id;
        SchoolTable::query('timetables')->insert($invalid);
        $this->assertSame(0, Timetable::where('session_year_id', $year->id)->whereNotNull('timetable_lesson_id')->count());
    }

    public function test_new_api_requires_fresh_permissions_and_rejects_other_school_and_other_child_ids(): void
    {
        $path = '/api/v2/academics/timetable';
        $this->getJson($path.'/configuration?session_year_id='.$this->year)->assertUnauthorized();
        $this->api($this->superAdmin())->getJson($path.'/configuration?session_year_id='.$this->year)->assertOk();
        $preview = $this->postJson($path.'/preview', ['rows' => [$this->row()]])->assertOk()->json('data');
        $this->withHeader('Idempotency-Key', 'place-timetable');
        $this->postJson($path.'/commit', ['rows' => $preview['rows'], 'fingerprint' => $preview['fingerprint']])->assertOk();
        $this->api(User::findOrFail($this->schoolA->ids['tara_user']))->postJson($path.'/commit', ['rows' => $preview['rows'], 'fingerprint' => $preview['fingerprint']])->assertForbidden();
        $this->getJson($path.'/configuration?session_year_id='.$this->year)->assertForbidden();
        $this->api(User::findOrFail($this->schoolA->ids['farid_user']))->getJson($path.'/week?session_year_id='.$this->year.'&starts_on=2026-02-09&learner_id='.$this->schoolA->ids['asha'])->assertOk();
        $this->getJson($path.'/week?session_year_id='.$this->year.'&starts_on=2026-02-09&learner_id='.$this->schoolA->ids['ben'])->assertNotFound();
        $this->getJson($path.'/week?session_year_id='.$this->schoolB->ids['session_year'].'&starts_on=2026-02-09&learner_id='.$this->schoolA->ids['asha'])->assertNotFound();
        $this->api($this->superAdmin())->withHeader('X-School-Id', '2')->postJson($path.'/preview', ['rows' => [$this->row()]])->assertForbidden();
    }

    public function test_revoked_allocations_and_inactive_groups_hide_canonical_and_existing_app_rows_without_waiting_for_scheduler(): void
    {
        $this->save([$this->row()]);
        $this->assertSame(1, Timetable::whereNotNull('timetable_lesson_id')->count());
        TeachingAllocation::where('teaching_group_id', $this->group)->update(['ends_on' => '2026-02-09']);
        $this->assertSame(0, Timetable::whereNotNull('timetable_lesson_id')->count());
        $parent = User::findOrFail($this->schoolA->ids['farid_user']);
        $this->assertSame([], app(TimetableQueries::class)->week($parent, $this->year, '2026-02-09', $this->schoolA->ids['asha'])['lessons']);
        TeachingAllocation::where('teaching_group_id', $this->group)->update(['ends_on' => '2026-12-31']);
        TeachingGroup::whereKey($this->group)->update(['active' => false]);
        $this->assertSame(0, Timetable::whereNotNull('timetable_lesson_id')->count());
        $this->assertSame([], app(TimetableQueries::class)->week($parent, $this->year, '2026-02-09', $this->schoolA->ids['asha'])['lessons']);
    }

    public function test_room_period_and_teacher_guards_reject_invalid_foreign_and_unallocated_candidates(): void
    {
        $foreign = app(CurrentSchool::class)->run(2, fn () => Room::create(['name' => 'Baobab room', 'type' => 'classroom', 'active' => true, 'revision' => 1]));
        $unallocated = (int) Teacher::where('user_id', $this->schoolA->ids['theo_user'])->value('id');
        $path = '/api/v2/academics/timetable/preview';
        $this->api($this->superAdmin());
        $this->postJson($path, ['rows' => [$this->row(['room_id' => $foreign->id])]])->assertNotFound();
        $this->postJson($path, ['rows' => [$this->row(['teacher_id' => $unallocated])]])->assertUnprocessable();
        $this->postJson($path, ['rows' => [$this->row(['teaching_group_id' => null, 'class_section_id' => null])]])->assertUnprocessable();
        $this->postJson($path, ['rows' => [$this->row(['starts_on' => '2025-12-31'])]])->assertUnprocessable();
        Room::whereKey($this->room)->update(['active' => false]);
        $this->postJson($path, ['rows' => [$this->row()]])->assertUnprocessable();
        Room::whereKey($this->room)->update(['active' => true]);
        BellPeriod::whereKey($this->period)->update(['is_break' => true]);
        $this->postJson($path, ['rows' => [$this->row()]])->assertUnprocessable();
        $this->assertSame(0, TimetableLesson::count());
    }

    public function test_homeroom_lessons_use_dated_enrolments_and_active_guardian_rights(): void
    {
        app(TeachingGroups::class)->dated($this->superAdmin(), 'homerooms', ['teacher_id' => $this->teacher, 'class_section_id' => $this->schoolA->ids['section_a'],
            'session_year_id' => $this->year, 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Morning homeroom']);
        $this->save([$this->row(['teaching_group_id' => null, 'class_section_id' => $this->schoolA->ids['section_a']])]);
        $parent = User::findOrFail($this->schoolA->ids['farid_user']);
        $path = '/api/v2/academics/timetable/week?session_year_id='.$this->year.'&starts_on=2026-02-09&learner_id='.$this->schoolA->ids['asha'];
        $this->api($parent)->getJson($path)->assertOk()->assertJsonCount(1, 'data.lessons');
        GuardianLink::updateOrCreate(['learner_id' => $this->schoolA->ids['asha'], 'guardian_user_id' => $parent->id], ['relationship' => 'father', 'status' => 'ended', 'can_approve' => false]);
        $this->getJson($path)->assertNotFound();
    }

    public function test_room_and_bell_updates_are_revision_checked_and_do_not_discard_used_periods(): void
    {
        $this->save([$this->row()]);
        $config = app(TimetableConfiguration::class);
        try {
            $config->room($this->superAdmin(), ['name' => 'Closed', 'type' => 'classroom', 'active' => false, 'revision' => 1], $this->room);
            $this->fail('A used future room cannot silently disappear');
        } catch (HttpException $error) {
            $this->assertSame(409, $error->getStatusCode());
        }
        $schedule = BellSchedule::with('periods')->firstOrFail();
        try {
            $config->bells($this->superAdmin(), ['name' => 'Changed', 'section' => 'Senior', 'session_year_id' => $this->year, 'revision' => 1,
                'periods' => [['name' => 'Later', 'starts_at' => '08:30', 'ends_at' => '09:15', 'is_break' => false]]], $schedule->id);
            $this->fail('Used period IDs must retain provenance');
        } catch (HttpException $error) {
            $this->assertSame(409, $error->getStatusCode());
        }
        $this->assertSame($this->period, (int) TimetableLesson::query()->sole()->bell_period_id);
    }

    public function test_every_management_write_denies_a_teacher_and_foreign_configuration_ids_are_not_disclosed(): void
    {
        $path = '/api/v2/academics/timetable';
        $this->api(User::findOrFail($this->schoolA->ids['tara_user']));
        foreach (['rooms', 'bells', 'preview', 'commit', 'import'] as $operation) {
            $this->postJson($path.'/'.$operation, [])->assertForbidden();
        }
        foreach (['rooms', 'bells'] as $operation) {
            $this->putJson($path.'/'.$operation.'/999999', [])->assertForbidden();
        }
        $foreign = app(CurrentSchool::class)->run(2, function () {
            $room = Room::create(['name' => 'Baobab private room', 'type' => 'classroom', 'active' => true, 'revision' => 1]);
            $schedule = BellSchedule::create(['name' => 'Baobab bells', 'section' => 'Senior', 'session_year_id' => $this->schoolB->ids['session_year'], 'revision' => 1]);
            $period = $schedule->periods()->create(['name' => 'Baobab period', 'position' => 1, 'starts_at' => '08:00', 'ends_at' => '08:45', 'is_break' => false]);

            return [(int) $room->id, (int) $schedule->id, (int) $period->id];
        });
        $this->api($this->superAdmin());
        $this->putJson($path.'/rooms/'.$foreign[0], ['name' => 'Renamed', 'type' => 'classroom', 'active' => true, 'revision' => 1])->assertNotFound();
        $this->putJson($path.'/bells/'.$foreign[1], ['name' => 'Renamed', 'section' => 'Senior', 'session_year_id' => $this->year, 'revision' => 1,
            'periods' => [['name' => 'Period', 'starts_at' => '08:00', 'ends_at' => '08:45', 'is_break' => false]]])->assertNotFound();
        foreach ([['bell_period_id' => $foreign[2]], ['teacher_id' => $this->schoolB->ids['tara_teacher']]] as $changes) {
            $this->postJson($path.'/preview', ['rows' => [$this->row($changes)]])->assertNotFound();
        }
        $this->getJson($path.'/configuration?session_year_id='.$this->schoolB->ids['session_year'])->assertNotFound();
        $this->api(User::findOrFail($this->schoolA->ids['farid_user']))->getJson($path.'/next?learner_id='.$this->schoolA->ids['ben'])->assertNotFound();
        $this->getJson($path.'/next?learner_id='.$this->schoolB->ids['asha'])->assertNotFound();
        $this->assertSame(0, TimetableLesson::count());
    }

    public function test_a_revoked_manager_cannot_replay_a_previous_idempotent_placement(): void
    {
        $this->api($this->superAdmin());
        $path = '/api/v2/academics/timetable';
        $preview = $this->postJson($path.'/preview', ['rows' => [$this->row()]])->assertOk()->json('data');
        $input = ['rows' => $preview['rows'], 'fingerprint' => $preview['fingerprint']];
        $this->withHeader('Idempotency-Key', 'replay-placement');
        $this->postJson($path.'/commit', $input)->assertOk();
        Role::where('name', 'Super Admin')->firstOrFail()->revokePermissionTo('academic-structure-manage');
        $this->postJson($path.'/commit', $input)->assertForbidden();
        $this->assertSame(1, TimetableLesson::count());
    }

    public function test_physical_resources_still_clash_when_school_year_ranges_overlap(): void
    {
        $this->save([$this->row()]);
        $firstYear = $this->year;
        $second = SessionYear::create(['name' => 'Alternate section year', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31']);
        $this->year = (int) $second->id;
        Enrolment::create(['learner_id' => $this->schoolA->ids['asha'], 'session_year_id' => $this->year, 'class_section_id' => $this->schoolA->ids['section_a'],
            'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Alternate calendar fixture']);
        $group = $this->makeGroup('Alternate calendar group', $this->schoolA->ids['maths'], $this->teacher);
        $period = $this->makePeriod('08:00', '08:45');
        $row = $this->row(['teaching_group_id' => $group, 'bell_period_id' => $period, 'term_id' => null]);
        $this->year = $firstYear;
        $preview = app(TimetableEngine::class)->preview($this->superAdmin(), [$row]);
        $this->assertFalse($preview['can_save']);
        $this->assertContains('room', $preview['conflicts'][0]['reasons']);
        $this->assertContains('teacher', $preview['conflicts'][0]['reasons']);
    }

    public function test_legacy_lessons_participate_in_clashes_and_old_writers_cannot_overlap_canonical_rows(): void
    {
        $period = $this->makePeriod('09:15', '09:45');
        $preview = app(TimetableEngine::class)->preview($this->superAdmin(), [$this->row(['bell_period_id' => $period])]);
        $this->assertFalse($preview['can_save']);
        $this->assertNull($preview['conflicts'][0]['lesson_id']);
        $this->assertContains('teacher', $preview['conflicts'][0]['reasons']);
        $this->save([$this->row()]);
        $legacy = Timetable::whereNull('timetable_lesson_id')->where('class_section_id', $this->schoolA->ids['section_a'])->where('day', 2)->firstOrFail();
        try {
            $legacy->forceFill(['start_time' => '08:15', 'end_time' => '08:40'])->save();
            $this->fail('Old editors must not bypass canonical clashes');
        } catch (HttpException $error) {
            $this->assertSame(409, $error->getStatusCode());
        }
    }

    public function test_a_year_wide_lesson_remains_visible_in_both_installed_family_timetables_when_terms_are_enabled(): void
    {
        ClassSessionConfig::where('class_id', $this->schoolA->ids['class_a'])->where('session_year_id', $this->year)->update(['include_terms' => 1]);
        DB::table('class_subjects')->where('school_id', 1)->where('class_id', $this->schoolA->ids['class_a'])->where('session_year_id', $this->year)->update(['term_id' => $this->schoolA->ids['term']]);
        $this->save([$this->row(['term_id' => null])]);
        $projection = Timetable::whereNotNull('timetable_lesson_id')->sole();
        $this->assertSame($this->schoolA->ids['term'], (int) $projection->term_id);
        $parent = $this->api(User::findOrFail($this->schoolA->ids['farid_user']))->getJson('/api/parent/timetable?child_id='.$this->schoolA->ids['asha'])->assertOk()->assertJsonPath('error', false)->json('data');
        $this->assertContains('Room 1', array_column($parent, 'room_name'));
        $learner = $this->api(User::findOrFail($this->schoolA->ids['asha_user']))->getJson('/api/student/timetable')->assertOk()->assertJsonPath('error', false)->json('data');
        $this->assertContains('Room 1', array_column($learner, 'room_name'));
    }

    public function test_obsolete_lessons_can_be_cancelled_without_reactivating_their_teacher_or_group(): void
    {
        $lesson = $this->save([$this->row()])['lessons'][0];
        User::whereKey($this->schoolA->ids['tara_user'])->update(['status' => 0]);
        TeachingGroup::whereKey($this->group)->update(['active' => false]);
        $this->assertCount(1, app(TimetableQueries::class)->week($this->superAdmin(), $this->year, '2026-02-09')['lessons']);
        $input = $this->row(['id' => $lesson['id'], 'revision' => 1, 'active' => false]);
        $this->save([$input]);
        $this->assertFalse(TimetableLesson::query()->sole()->active);
        $this->assertSame(2, TimetableLesson::query()->sole()->revision);
    }

    public function test_next_period_searches_authorized_lessons_across_overlapping_calendars(): void
    {
        $this->save([$this->row()]);
        SessionYear::create(['name' => 'Another section calendar', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31']);
        $parent = User::findOrFail($this->schoolA->ids['farid_user']);
        $this->assertSame('Room 1', app(TimetableQueries::class)->next($parent, $this->schoolA->ids['asha'])['lesson']['room_name']);
    }

    public function test_default_family_and_teacher_weeks_include_their_authorized_overlapping_calendars(): void
    {
        $this->save([$this->row()]);
        $firstYear = $this->year;
        $second = SessionYear::create(['name' => 'Alternate section calendar', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31']);
        $this->year = (int) $second->id;
        Enrolment::create(['learner_id' => $this->schoolA->ids['asha'], 'session_year_id' => $this->year, 'class_section_id' => $this->schoolA->ids['section_a'],
            'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Alternate calendar context']);
        $teacher = (int) Teacher::where('user_id', $this->schoolA->ids['theo_user'])->value('id');
        $group = $this->makeGroup('Alternate group', $this->schoolA->ids['maths'], $teacher);
        $period = $this->makePeriod('08:45', '09:00');
        $this->save([$this->row(['term_id' => null, 'teaching_group_id' => $group, 'teacher_id' => $teacher, 'bell_period_id' => $period, 'room_id' => $this->makeRoom('Room 2')])]);
        $this->year = $firstYear;
        $path = '/api/v2/academics/timetable/week';
        $this->api(User::findOrFail($this->schoolA->ids['farid_user']))->getJson($path.'?learner_id='.$this->schoolA->ids['asha'])->assertOk()->assertJsonCount(2, 'data.lessons');
        $this->api(User::findOrFail($this->schoolA->ids['theo_user']))->getJson($path)->assertOk()->assertJsonCount(1, 'data.lessons')->assertJsonPath('data.session_year_id', (int) $second->id);
    }

    public function test_panel_workbench_previews_and_commits_through_the_same_services_and_denies_other_roles(): void
    {
        $context = ['session_year_id' => $this->year, 'week_on' => '2026-02-09'];
        $this->actingAs($this->superAdmin(), 'web')->get(route('academic-timetable.index', $context))->assertOk()->assertSee('Preview placement')->assertSee('Bell schedules');
        $preview = $this->post(route('academic-timetable.preview'), $context + ['lesson' => $this->row()])->assertOk()->viewData('preview');
        $this->assertTrue($preview['can_save']);
        $this->post(route('academic-timetable.commit'), $context + ['payload' => json_encode($preview['rows']), 'fingerprint' => $preview['fingerprint']])->assertRedirect();
        $this->assertSame(1, TimetableLesson::count());
        $this->get(route('academic-timetable.index', $context))->assertOk()->assertSee('Room 1');
        foreach (['tara_user', 'farid_user', 'asha_user'] as $person) {
            $read = $this->actingAs(User::findOrFail($this->schoolA->ids[$person]), 'web')->get(route('academic-timetable.index'));
            $this->assertContains($read->getStatusCode(), [302, 403]);
            $read->assertDontSee('Preview placement');
            $write = $this->post(route('academic-timetable.preview'), $context + ['lesson' => $this->row()]);
            $this->assertContains($write->getStatusCode(), [302, 403]);
            $this->assertSame(1, TimetableLesson::count());
        }
    }

    public function test_panel_room_bell_and_foreign_edit_ids_remain_school_scoped(): void
    {
        $context = ['session_year_id' => $this->year, 'week_on' => '2026-02-09'];
        $this->actingAs($this->superAdmin(), 'web');
        $this->post(route('academic-timetable.room'), $context + ['name' => 'Panel room', 'type' => 'classroom', 'capacity' => 35, 'active' => true])->assertRedirect();
        $this->post(route('academic-timetable.bells'), $context + ['name' => 'Panel bells', 'section' => 'Senior', 'periods' => [
            ['name' => 'Period 1', 'starts_at' => '07:00', 'ends_at' => '07:45', 'is_break' => false],
            ['name' => '', 'starts_at' => '', 'ends_at' => '', 'is_break' => false]]])->assertRedirect();
        $this->assertSame(1, BellSchedule::where('name', 'Panel bells')->sole()->periods->count());
        $foreign = app(CurrentSchool::class)->run(2, fn () => Room::create(['name' => 'Baobab room', 'type' => 'classroom', 'revision' => 1, 'active' => true]));
        $this->get(route('academic-timetable.index', $context + ['room' => $foreign->id]))->assertNotFound();
        $this->post(route('academic-timetable.room'), $context + ['room_id' => $foreign->id, 'revision' => 1, 'name' => 'Bypass', 'type' => 'classroom', 'active' => true])->assertNotFound();
    }

    public function test_panel_import_inspects_maps_and_previews_without_saving_or_rendering_sheet_markup(): void
    {
        $context = ['session_year_id' => $this->year, 'week_on' => '2026-02-09'];
        $csv = "Group,Teacher,Room,Day,Period\nMaths,Tara,<script>alert(1)</script>,Tuesday,P1\n";
        $columns = ['teaching_group_id' => 'Group', 'teacher_id' => 'Teacher', 'room_id' => 'Room', 'weekday' => 'Day', 'bell_period_id' => 'Period'];
        $this->actingAs($this->superAdmin(), 'web')->post(route('academic-timetable.import-map'), $context + ['csv' => $csv])->assertOk()->assertSee('Match sheet columns');
        $this->post(route('academic-timetable.import-map'), $context + ['csv' => $csv, 'columns' => $columns, 'stage' => 'values'])->assertOk()->assertSee('&lt;script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);
        $values = ['teaching_group_id' => ['Maths' => $this->group], 'teacher_id' => ['Tara' => $this->teacher], 'room_id' => ['<script>alert(1)</script>' => $this->room], 'weekday' => ['Tuesday' => 2], 'bell_period_id' => ['P1' => $this->period]];
        $response = $this->post(route('academic-timetable.import-preview'), $context + ['csv' => $csv, 'columns' => $columns, 'values_json' => json_encode($values), 'defaults' => ['starts_on' => '2026-02-10', 'ends_on' => '2026-02-10', 'week_pattern' => 'all']])->assertOk();
        $this->assertTrue($response->viewData('preview')['can_save']);
        $this->assertSame(0, TimetableLesson::count());
        $sheet = app(TimetableImport::class)->inspect($this->superAdmin(), "\xEF\xBB\xBFKamer,Onderwyser\nSaal,Müller\n");
        $this->assertSame(['Kamer', 'Onderwyser'], $sheet['headers']);
        $this->assertSame(['Müller'], $sheet['values']['Onderwyser']);
    }

    public function test_panel_stale_commit_preserves_the_form_and_cancellation_retains_original_placement(): void
    {
        $context = ['session_year_id' => $this->year, 'week_on' => '2026-02-09'];
        $this->actingAs($this->superAdmin(), 'web');
        $preview = $this->post(route('academic-timetable.preview'), $context + ['lesson' => $this->row()])->assertOk()->viewData('preview');
        $this->makeRoom('Changed configuration');
        $this->post(route('academic-timetable.commit'), $context + ['payload' => json_encode($preview['rows']), 'fingerprint' => $preview['fingerprint']])->assertStatus(409)->assertSee('Reload the week');
        $lesson = $this->save([$this->row()])['lessons'][0];
        User::whereKey($this->schoolA->ids['tara_user'])->update(['status' => 0]);
        $cancel = $this->post(route('academic-timetable.preview'), $context + ['lesson' => $this->row(['id' => $lesson['id'], 'revision' => 1, 'active' => false, 'room_id' => null])])->assertOk()->viewData('preview');
        $this->assertSame($this->room, $cancel['rows'][0]['room_id']);
        $this->post(route('academic-timetable.commit'), $context + ['payload' => json_encode($cancel['rows']), 'fingerprint' => $cancel['fingerprint']])->assertRedirect();
        $this->assertFalse(TimetableLesson::query()->sole()->active);
    }

    private function api(User $user): self
    {
        $this->app['auth']->forgetGuards();

        return $this->flushHeaders()->withToken($user->createToken('timetable')->plainTextToken);
    }
}
