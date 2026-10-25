<?php

declare(strict_types=1);

namespace Tests\Feature\Pastoral;

use App\Models\Academics\BellPeriod;
use App\Models\Academics\GradeLevel;
use App\Models\Pastoral\CoverAbsence;
use App\Models\Pastoral\CoverAssignment;
use App\Models\Pastoral\CoverOperation;
use App\Models\Platform\PlatformStaffIdentity;
use App\Models\Platform\Role;
use App\Models\Platform\School;
use App\Models\Platform\SchoolMembership;
use App\Models\SessionYear;
use App\Models\Teacher;
use App\Models\Term;
use App\Models\User;
use App\Services\Academics\TeachingGroups;
use App\Services\Academics\TimetableConfiguration;
use App\Services\Pastoral\CoverAvailability;
use App\Services\Pastoral\CoverQueries;
use App\Services\Pastoral\CoverSources;
use App\Services\Pastoral\ReliefCover;
use App\Services\Platform\Tenancy\CurrentSchool;
use App\Services\Platform\Tenancy\SchoolTable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Concerns\UsesCoverScenario;
use Tests\TestCase;

class ReliefCoverTest extends TestCase
{
    use UsesCoverScenario;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpCoverScenario();
    }

    public function test_own_absence_maps_only_real_occurrences_and_replays_without_duplicates(): void
    {
        $id = (string) Str::uuid();
        $absence = app(ReliefCover::class)->request($this->person(), $this->input(), $id);
        $this->assertSame(1, $absence->revision);
        $this->assertSame($absence->id, app(ReliefCover::class)->request($this->person(), $this->input(), $id)->id);
        $this->assertSame(['2026-02-10', '2026-02-17'], CoverAssignment::orderBy('on_date')->get()->map(fn ($row) => $row->on_date->toDateString())->all());
        $this->assertSame(1, CoverAbsence::count());
        $this->assertSame(1, CoverOperation::count());
        $this->assertStringNotContainsString('Private cover reason', DB::table('cover_absences')->value('reason'));
        $this->assertStringNotContainsString('Private cover reason', json_encode($absence));
        $this->assertSame('Private cover reason', app(CoverQueries::class)->absence($this->person(), $absence->id)['reason']);
        $this->refused(fn () => app(ReliefCover::class)->request($this->person(), array_replace($this->input(), ['reason' => 'Changed']), $id), 409);
    }

    public function test_manager_assigns_only_an_available_teacher_and_substitute_cannot_read_reason(): void
    {
        $absence = $this->request();
        $cover = CoverAssignment::where('cover_absence_id', $absence->id)->orderBy('on_date')->firstOrFail();
        $uuid = (string) Str::uuid();
        $assigned = app(ReliefCover::class)->assign($this->superAdmin(), $cover->id, $this->substitute, 1, $uuid);
        $this->assertSame('assigned', $assigned->state);
        $this->assertSame($assigned->id, app(ReliefCover::class)->assign($this->superAdmin(), $cover->id, $this->substitute, 1, $uuid)->id);
        $view = app(CoverQueries::class)->occurrence($this->person('theo'), $assigned->id);
        $this->assertSame('Cover room', $view['source']['room_name']);
        $this->assertStringNotContainsString('Private cover reason', json_encode($view));
        $this->refused(fn () => app(CoverQueries::class)->absence($this->person('theo'), $absence->id), 403);
        $this->assertSame(1, count(app(CoverQueries::class)->mine($this->person('theo'), '2026-02-10', '2026-02-17')));
        $this->assertSame(1, DB::table('teaching_allocations')->where('teaching_group_id', $this->lesson->teaching_group_id)->count());
    }

    public function test_wrong_role_wrong_owner_and_cross_school_access_are_refused(): void
    {
        $this->refused(fn () => app(ReliefCover::class)->request($this->person('farid'), $this->input(), (string) Str::uuid()), 403);
        $this->refused(fn () => app(ReliefCover::class)->request($this->person('theo'), $this->input(), (string) Str::uuid()), 403);
        $absence = $this->request();
        $this->refused(fn () => app(CoverQueries::class)->absence($this->person('theo'), $absence->id), 403);
        $otherActor = app(CurrentSchool::class)->run(2, fn () => User::findOrFail($this->schoolB->ids['tara_user']));
        $this->expectException(ModelNotFoundException::class);
        app(CurrentSchool::class)->run(2, fn () => app(CoverQueries::class)->absence($otherActor, $absence->id));
    }

    public function test_teachers_cannot_assign_cover_and_stale_revision_cannot_overwrite(): void
    {
        $this->request();
        $cover = CoverAssignment::orderBy('id')->firstOrFail();
        $this->refused(fn () => app(ReliefCover::class)->assign($this->person(), $cover->id, $this->substitute, 1, (string) Str::uuid()), 403);
        $this->refused(fn () => app(ReliefCover::class)->assign($this->superAdmin(), $cover->id, $this->substitute, 2, (string) Str::uuid()), 409);
        $this->refused(fn () => app(ReliefCover::class)->assign($this->superAdmin(), $cover->id, $this->teacher, 1, (string) Str::uuid()), 409);
        $this->assertSame(1, CoverOperation::count());
    }

    public function test_substitute_own_lesson_or_reported_absence_prevents_assignment(): void
    {
        $this->request();
        $cover = CoverAssignment::orderBy('id')->firstOrFail();
        $busy = $this->lesson->replicate();
        $busy->teacher_id = $this->substitute;
        $busy->save();
        $this->refused(fn () => app(ReliefCover::class)->assign($this->superAdmin(), $cover->id, $this->substitute, 1, (string) Str::uuid()), 409);
        $busy->delete();
        app(ReliefCover::class)->request($this->person('theo'), array_replace($this->input(), ['teacher_id' => $this->substitute]), (string) Str::uuid());
        $this->refused(fn () => app(ReliefCover::class)->assign($this->superAdmin(), $cover->id, $this->substitute, 1, (string) Str::uuid()), 409);
    }

    public function test_timetable_change_requires_explicit_refresh_and_clears_stale_assignment(): void
    {
        $absence = $this->request();
        $cover = CoverAssignment::orderBy('id')->firstOrFail();
        app(ReliefCover::class)->assign($this->superAdmin(), $cover->id, $this->substitute, 1, (string) Str::uuid());
        BellPeriod::whereKey($this->lesson->bell_period_id)->update(['ends_at' => '09:00:00']);
        $this->assertFalse(app(CoverSources::class)->valid($cover->fresh()));
        $this->assertSame('needs_review', app(CoverQueries::class)->occurrence($this->person(), $cover->id)['state']);
        $this->refused(fn () => app(CoverQueries::class)->occurrence($this->person('theo'), $cover->id), 403);
        app(ReliefCover::class)->change($this->person(), $absence->id, 1, 'refresh', 'Changed bell time', (string) Str::uuid());
        $this->assertSame('open', $cover->fresh()->state);
        $this->assertNull($cover->fresh()->substitute_teacher_id);
        $this->assertSame('assigned', app(ReliefCover::class)->assign($this->superAdmin(), $cover->id, $this->substitute, $cover->fresh()->revision, (string) Str::uuid())->state);
    }

    public function test_cancellation_retains_private_history_and_releases_cover(): void
    {
        $absence = $this->request();
        $cover = CoverAssignment::orderBy('id')->firstOrFail();
        app(ReliefCover::class)->assign($this->superAdmin(), $cover->id, $this->substitute, 1, (string) Str::uuid());
        app(ReliefCover::class)->change($this->person(), $absence->id, 1, 'cancel', 'Private cancellation', (string) Str::uuid());
        $this->assertSame('cancelled', $absence->fresh()->state);
        $this->assertSame('cancelled', $cover->fresh()->state);
        $this->assertSame(2, CoverAssignment::count());
        $this->assertStringNotContainsString('Private cancellation', DB::table('cover_absences')->value('cancel_reason'));
        $this->assertStringNotContainsString('Private cancellation', json_encode(DB::table('audit_log')->get()));
        $this->assertStringNotContainsString('Private cover reason', json_encode(DB::table('audit_log')->get()));
        $this->assertSame([], app(CoverQueries::class)->mine($this->person('theo'), '2026-02-10', '2026-02-17'));
    }

    public function test_disabled_module_impersonation_and_platform_staff_are_refused_before_replay(): void
    {
        $actor = $this->person();
        $uuid = (string) Str::uuid();
        app(ReliefCover::class)->request($actor, $this->input(), $uuid);
        config(['modules.relief_cover' => ['default' => false]]);
        $this->refused(fn () => app(ReliefCover::class)->request($actor, $this->input(), $uuid), 403);
        config(['modules.relief_cover' => ['always_on' => true]]);
        PlatformStaffIdentity::create(['user_id' => $actor->id]);
        $this->refused(fn () => app(ReliefCover::class)->request($actor, $this->input(), $uuid), 403);
        PlatformStaffIdentity::where('user_id', $actor->id)->delete();
        app(CurrentSchool::class)->impersonate(School::findOrFail(1));
        $this->refused(fn () => app(ReliefCover::class)->request($actor, $this->input(), $uuid), 403);
    }

    public function test_manager_permission_revocation_refuses_even_a_completed_assignment_receipt(): void
    {
        $this->request();
        $cover = CoverAssignment::orderBy('id')->firstOrFail();
        $manager = $this->superAdmin();
        $uuid = (string) Str::uuid();
        app(ReliefCover::class)->assign($manager, $cover->id, $this->substitute, 1, $uuid);
        Role::findByName('Super Admin')->revokePermissionTo('academic-structure-manage');
        $this->refused(fn () => app(ReliefCover::class)->assign($manager, $cover->id, $this->substitute, 1, $uuid), 403);
    }

    public function test_a_substitute_cannot_be_allocated_to_two_overlapping_cover_occurrences(): void
    {
        $this->request();
        $first = CoverAssignment::orderBy('id')->firstOrFail();
        $thirdUser = User::forceCreate(['first_name' => 'Cora', 'last_name' => 'Cover', 'email' => 'cora-cover@example.com',
            'password' => bcrypt('Synthetic-only-password'), 'gender' => 'Female', 'mobile' => '0820000000', 'status' => 1]);
        $thirdUser->assignRole('Teacher');
        $third = Teacher::findOrFail($this->teacher)->replicate();
        $third->user_id = $thirdUser->id;
        $third->save();
        $group = app(TeachingGroups::class)->save($this->superAdmin(), ['name' => 'Other cover group', 'subject_id' => $this->schoolA->ids['maths'],
            'grade_level_id' => GradeLevel::where('code', '7')->value('id'), 'session_year_id' => $this->year, 'class_section_id' => null, 'active' => true]);
        app(TeachingGroups::class)->dated($this->superAdmin(), 'teachers', ['teacher_id' => $this->substitute,
            'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Other allocation'], $group->id);
        $room = app(TimetableConfiguration::class)->room($this->superAdmin(), ['name' => 'Other cover room', 'capacity' => 35, 'type' => 'classroom', 'active' => true]);
        $otherLesson = $this->lesson->replicate();
        $otherLesson->fill(['teacher_id' => $this->substitute, 'teaching_group_id' => $group->id, 'room_id' => $room->id])->save();
        $secondAbsence = app(ReliefCover::class)->request($this->person('theo'), array_replace($this->input(), ['teacher_id' => $this->substitute]), (string) Str::uuid());
        $second = CoverAssignment::where('cover_absence_id', $secondAbsence->id)->orderBy('on_date')->firstOrFail();
        app(ReliefCover::class)->assign($this->superAdmin(), $first->id, $third->id, 1, (string) Str::uuid());
        $this->refused(fn () => app(ReliefCover::class)->assign($this->superAdmin(), $second->id, $third->id, 1, (string) Str::uuid()), 409);
    }

    public function test_legacy_rows_are_not_silently_omitted_from_cover_mapping(): void
    {
        $subjectTeacher = SchoolTable::query('subject_teachers')->where('teacher_id', $this->teacher)->where('session_year_id', $this->year)->value('id');
        SchoolTable::query('timetables')->insert(['session_year_id' => $this->year, 'class_section_id' => $this->schoolA->ids['section_a'],
            'subject_teacher_id' => $subjectTeacher, 'day' => 2, 'day_name' => 'tuesday', 'start_time' => '10:00', 'end_time' => '10:45',
            'created_at' => now(), 'updated_at' => now()]);
        $this->refused(fn () => $this->request(), 409);
        $this->assertSame(0, CoverAbsence::count());
        $this->assertSame(0, CoverOperation::count());
    }

    public function test_retained_cover_rows_refuse_destructive_rollback(): void
    {
        $this->request();
        $migration = require database_path('migrations/2026_10_25_040000_create_relief_cover.php');
        $this->expectException(\RuntimeException::class);
        $migration->down();
    }

    public function test_platform_operator_cannot_be_selected_as_a_substitute(): void
    {
        $this->request();
        $cover = CoverAssignment::orderBy('id')->firstOrFail();
        PlatformStaffIdentity::create(['user_id' => $this->schoolA->ids['theo_user']]);
        $this->refused(fn () => app(ReliefCover::class)->assign($this->superAdmin(), $cover->id, $this->substitute, 1, (string) Str::uuid()), 403);
        $this->assertNull($cover->fresh()->substitute_teacher_id);
    }

    public function test_period_moved_to_another_year_invalidates_the_source(): void
    {
        $this->request();
        $cover = CoverAssignment::orderBy('id')->firstOrFail();
        $year = SessionYear::create(['name' => 'Future cover year', 'start_date' => '2027-01-01', 'end_date' => '2027-12-31']);
        DB::table('bell_schedules')->where('id', BellPeriod::findOrFail($this->lesson->bell_period_id)->bell_schedule_id)->update(['session_year_id' => $year->id]);
        $this->assertFalse(app(CoverSources::class)->valid($cover));
        $this->refused(fn () => app(ReliefCover::class)->assign($this->superAdmin(), $cover->id, $this->substitute, 1, (string) Str::uuid()), 409);
    }

    public function test_other_term_legacy_rows_do_not_block_this_date_window(): void
    {
        $term = Term::create(['session_year_id' => $this->year, 'name' => 'Other term', 'start_date' => '2026-06-01', 'end_date' => '2026-06-30', 'grade_from' => 0, 'grade_to' => 12]);
        $subjectTeacher = SchoolTable::query('subject_teachers')->where('teacher_id', $this->teacher)->where('session_year_id', $this->year)->value('id');
        SchoolTable::query('timetables')->insert(['session_year_id' => $this->year, 'term_id' => $term->id, 'class_section_id' => $this->schoolA->ids['section_a'],
            'subject_teacher_id' => $subjectTeacher, 'day' => 2, 'day_name' => 'tuesday', 'start_time' => '10:00', 'end_time' => '10:45',
            'created_at' => now(), 'updated_at' => now()]);
        $this->assertSame('requested', $this->request()->state);
        $this->refused(fn () => app(ReliefCover::class)->request($this->person(), array_replace($this->input(), ['starts_on' => '2026-06-02', 'ends_on' => '2026-06-02']), (string) Str::uuid()), 409);
    }

    public function test_temporarily_stale_assignment_keeps_its_original_time_reserved(): void
    {
        $this->request();
        $first = CoverAssignment::orderBy('id')->firstOrFail();
        app(ReliefCover::class)->assign($this->superAdmin(), $first->id, $this->substitute, 1, (string) Str::uuid());
        $room = $this->lesson->room;
        $room->update(['name' => 'Temporarily changed']);
        $this->assertFalse(app(CoverSources::class)->valid($first->fresh()));
        // Use the next occurrence as a distinct candidate for the same date;
        // no new permanent teaching allocation is needed for this invariant.
        $other = CoverAssignment::orderBy('id')->skip(1)->firstOrFail();
        $other->on_date = $first->on_date;
        $this->assertFalse(app(CoverAvailability::class)->assignable($this->substitute, $other, $this->lesson));
    }

    public function test_context_change_rolls_back_and_restores_original_school(): void
    {
        $actor = $this->superAdmin();
        $membership = SchoolMembership::where('user_id', $actor->id)->firstOrFail()->replicate();
        $membership->school_id = 2;
        $membership->save();
        CoverOperation::created(function () {
            app(CurrentSchool::class)->set(2);
        });
        try {
            $this->refused(fn () => app(ReliefCover::class)->request($actor, $this->input(), (string) Str::uuid()), 403);
        } finally {
            CoverOperation::flushEventListeners();
        }
        $this->assertSame(1, app(CurrentSchool::class)->id());
        $this->assertSame(0, DB::table('cover_absences')->count());
        $this->assertSame(0, DB::table('cover_operations')->count());
    }
}
