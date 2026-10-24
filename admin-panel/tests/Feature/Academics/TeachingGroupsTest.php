<?php

declare(strict_types=1);

namespace Tests\Feature\Academics;

use App\Http\Controllers\Api\Parent\ChatController;
use App\Models\Academics\GradeLevel;
use App\Models\Academics\TeachingAllocation;
use App\Models\Academics\TeachingGroup;
use App\Models\ClassSchool;
use App\Models\ClassSessionConfig;
use App\Models\ClassTeacher;
use App\Models\Parents;
use App\Models\People\Enrolment;
use App\Models\Platform\SchoolMembership;
use App\Models\SessionYear;
use App\Models\SubjectTeacher;
use App\Models\Teacher;
use App\Models\Term;
use App\Models\User;
use App\Services\Academics\AcademicAccess;
use App\Services\Academics\AcademicAuthority;
use App\Services\Academics\AcademicProjections;
use App\Services\Academics\TeachingGroups;
use App\Services\People\EnrolmentService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\Concerns\UsesTwoSchools;
use Tests\Concerns\ValidatesOpenApiResponses;
use Tests\TestCase;

class TeachingGroupsTest extends TestCase
{
    use UsesTwoSchools, ValidatesOpenApiResponses;

    private int $grade;

    private int $year;

    private int $teacher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        $this->travelTo(now()->setDate(2026, 2, 10));
        SchoolMembership::query()->update(['starts_on' => '2026-01-01']);
        $this->grade = (int) GradeLevel::where('code', '7')->value('id');
        $this->year = $this->schoolA->ids['session_year'];
        $this->teacher = (int) Teacher::where('user_id', $this->schoolA->ids['tara_user'])->value('id');
        SessionYear::whereKey($this->year)->update(['start_date' => '2026-01-01', 'end_date' => '2026-12-31']);
        ClassSchool::whereKey($this->schoolA->ids['class_a'])->update(['grade_level_id' => $this->grade]);
        Enrolment::create(['learner_id' => $this->schoolA->ids['asha'], 'session_year_id' => $this->year, 'class_section_id' => $this->schoolA->ids['section_a'], 'starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Synthetic dated placement']);
    }

    private function group(array $changes = []): TeachingGroup
    {
        return app(TeachingGroups::class)->save($this->superAdmin(), $changes + ['name' => 'Maths 7A', 'subject_id' => $this->schoolA->ids['maths'],
            'grade_level_id' => $this->grade, 'session_year_id' => $this->year, 'class_section_id' => $this->schoolA->ids['section_a'], 'active' => true]);
    }

    private function dates(array $changes = []): array
    {
        return $changes + ['starts_on' => '2026-01-01', 'ends_on' => '2026-12-31', 'reason' => 'Term allocation'];
    }

    private function api(User $user): self
    {
        $this->app['auth']->forgetGuards();

        return $this->flushHeaders()->withToken($user->createToken('groups')->plainTextToken);
    }

    public function test_api_group_and_dated_rows_match_contracts_and_reject_other_profiles_and_schools(): void
    {
        $path = '/api/v2/academics/teaching-groups';
        $this->getJson($path)->assertUnauthorized();
        $this->api($this->superAdmin());
        $group = $this->postJson($path, ['name' => 'API group', 'subject_id' => $this->schoolA->ids['maths'], 'grade_level_id' => $this->grade, 'session_year_id' => $this->year, 'active' => true])->assertOk()->json('data');
        $id = $group['id'];
        $this->getJson($path)->assertOk()->assertJsonPath('data.total', 1);
        $this->getJson("$path/$id")->assertOk()->assertJsonPath('data.class_section_id', null);
        $group['revision'] = 1;
        $group['name'] = 'Updated group';
        $this->putJson("$path/$id", $group)->assertOk()->assertJsonPath('data.revision', 2);
        $this->putJson("$path/$id", $group)->assertConflict();
        $entry = $this->postJson("$path/$id/teachers", $this->dates(['teacher_id' => $this->teacher]))->assertOk()->json('data');
        $this->getJson("$path/$id/teachers")->assertOk()->assertJsonPath('data.total', 1);
        $this->putJson("$path/$id/teachers/{$entry['id']}", $this->dates(['teacher_id' => $this->teacher, 'revision' => 1, 'ends_on' => '2026-06-30']))->assertOk();
        $this->postJson("$path/$id/memberships", $this->dates(['learner_id' => $this->schoolA->ids['asha'], 'mark_rule' => 'catch_up']))->assertOk();
        $this->getJson("$path/$id/memberships")->assertOk()->assertJsonPath('data.total', 1);
        $this->api(User::findOrFail($this->schoolA->ids['tara_user']))->getJson("$path/$id")->assertOk();
        $this->postJson($path, [])->assertForbidden();
        $this->getJson('/api/v2/academics/allocations/leadership')->assertForbidden();
        $this->api(User::findOrFail($this->schoolA->ids['farid_user']))->getJson("$path/$id")->assertForbidden();
        $this->api(User::findOrFail($this->schoolA->ids['asha_user']))->getJson($path)->assertForbidden();
        $this->api($this->superAdmin())->postJson("$path/$id/teachers", $this->dates(['teacher_id' => DB::table('teachers')->where('school_id', 2)->value('id')]))->assertNotFound();
        $this->withHeader('X-School-Id', '2')->getJson("$path/$id")->assertForbidden();
    }

    public function test_api_homeroom_and_leadership_assignments_and_wrong_group_updates_are_scoped(): void
    {
        $this->api($this->superAdmin());
        $path = '/api/v2/academics/allocations';
        $room = $this->postJson("$path/homerooms", $this->dates(['teacher_id' => $this->teacher, 'class_section_id' => $this->schoolA->ids['section_a'], 'session_year_id' => $this->year]))->assertOk()->json('data');
        $this->getJson("$path/homerooms")->assertOk()->assertJsonPath('data.total', 1);
        $this->putJson("$path/homerooms/{$room['id']}", $this->dates(['teacher_id' => $this->teacher, 'class_section_id' => $this->schoolA->ids['section_a'], 'session_year_id' => $this->year, 'revision' => 1, 'ends_on' => '2026-06-30']))->assertOk();
        $principal = $this->postJson("$path/leadership", $this->dates(['user_id' => $this->superAdmin()->id, 'session_year_id' => $this->year, 'kind' => 'principal']))->assertOk()->json('data');
        $this->putJson("$path/leadership/{$principal['id']}", $this->dates(['user_id' => $this->superAdmin()->id, 'session_year_id' => $this->year, 'kind' => 'principal', 'grade_level_id' => null, 'revision' => 1, 'ends_on' => '2026-06-30']))->assertOk();
        $this->getJson("$path/leadership")->assertOk();
        $one = $this->group();
        $two = $this->group(['name' => 'Another group']);
        $entry = app(TeachingGroups::class)->dated($this->superAdmin(), 'memberships', $this->dates(['learner_id' => $this->schoolA->ids['asha'], 'mark_rule' => 'excused']), $one->id);
        $this->putJson("/api/v2/academics/teaching-groups/{$two->id}/memberships/{$entry->id}", $this->dates(['learner_id' => $this->schoolA->ids['asha'], 'mark_rule' => 'excused', 'revision' => 1]))->assertNotFound();
    }

    public function test_revoked_structure_permission_cannot_replay_a_successful_group_write(): void
    {
        $admin = $this->superAdmin();
        $admin->assignRole('Teacher');
        $input = ['name' => 'Retry group', 'subject_id' => $this->schoolA->ids['maths'], 'grade_level_id' => $this->grade, 'session_year_id' => $this->year, 'active' => true];
        $this->api($admin)->withHeader('Idempotency-Key', 'group-retry')->postJson('/api/v2/academics/teaching-groups', $input)->assertOk();
        $this->postJson('/api/v2/academics/teaching-groups', $input)->assertOk()->assertHeader('Idempotent-Replayed', 'true');
        SchoolMembership::where('school_id', 1)->where('user_id', $admin->id)->where('role', 'Super Admin')->update(['ends_on' => '2026-02-09']);
        $this->postJson('/api/v2/academics/teaching-groups', $input)->assertForbidden();
    }

    public function test_panel_structure_forms_and_mapping_use_the_same_scoped_services(): void
    {
        $admin = $this->superAdmin();
        $this->actingAs($admin, 'web')->get(route('teaching-groups.index'))->assertOk()->assertSee('Class grade mappings');
        $this->get(route('teaching-groups.create'))->assertOk();
        $this->put(route('academic-structure.map', $this->schoolA->ids['class_a']), ['grade_level_id' => $this->grade])->assertRedirect();
        $group = $this->group();
        $this->get(route('teaching-groups.show', $group->id))->assertOk()->assertSee('Add learner membership');
        $this->get(route('teaching-groups.edit', $group->id))->assertOk();
        $this->get(route('academic-allocations.index'))->assertOk()->assertSee('Appoint academic leader');
        $this->put(route('academic-structure.map', $this->schoolA->ids['class_a']), ['grade_level_id' => null])->assertConflict();
        $this->actingAs(User::findOrFail($this->schoolA->ids['tara_user']), 'web')->get(route('academic-allocations.index'))->assertForbidden();
    }

    public function test_group_context_and_effective_membership_are_school_scoped_and_audited(): void
    {
        $group = $this->group();
        $entry = app(TeachingGroups::class)->dated($this->superAdmin(), 'memberships', $this->dates(['learner_id' => $this->schoolA->ids['asha'], 'mark_rule' => 'catch_up']), $group->id);
        $this->assertSame(1, (int) $entry->school_id);
        $this->assertSame('catch_up', $entry->mark_rule);
        $this->assertSame(2, DB::table('audit_log')->where('action', 'like', 'academics.%_saved')->count());
        try {
            $this->group(['subject_id' => $this->schoolB->ids['maths']]);
            $this->fail('Foreign subject');
        } catch (ModelNotFoundException) {
        }
        try {
            app(TeachingGroups::class)->dated($this->superAdmin(), 'memberships', $this->dates(['learner_id' => $this->schoolB->ids['asha'], 'mark_rule' => 'excused']), $group->id);
            $this->fail('Foreign learner');
        } catch (ModelNotFoundException) {
        }
    }

    public function test_teacher_cannot_manage_structure_or_read_an_unallocated_group(): void
    {
        $group = $this->group();
        $teacher = User::findOrFail($this->schoolA->ids['tara_user']);
        try {
            app(AcademicAuthority::class)->group($teacher, $group->id);
            $this->fail('Unallocated');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }
        try {
            app(TeachingGroups::class)->save($teacher, []);
            $this->fail('Teacher cannot create');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }
        app(TeachingGroups::class)->dated($this->superAdmin(), 'teachers', $this->dates(['teacher_id' => $this->teacher]), $group->id);
        $this->assertSame($group->id, app(AcademicAuthority::class)->group($teacher, $group->id, true)->id);
    }

    public function test_projection_is_repeatable_and_expired_canonical_authority_is_refused_without_a_refresh(): void
    {
        $group = $this->group();
        $teacher = User::findOrFail($this->schoolA->ids['tara_user']);
        $allocation = app(TeachingGroups::class)->dated($this->superAdmin(), 'teachers', $this->dates(['teacher_id' => $this->teacher, 'ends_on' => '2026-02-10']), $group->id);
        $id = SubjectTeacher::where('teaching_allocation_id', $allocation->id)->sole()->id;
        app(AcademicProjections::class)->refresh();
        $this->assertSame($id, SubjectTeacher::where('teaching_allocation_id', $allocation->id)->sole()->id);
        $this->travelTo(now()->addDay());
        $this->assertFalse(SubjectTeacher::whereKey($id)->exists());
        try {
            app(AcademicAuthority::class)->group($teacher, $group->id, true);
            $this->fail('Ended allocation');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }
        app(AcademicProjections::class)->refresh();
        $this->assertNotNull(DB::table('subject_teachers')->where('id', $id)->value('deleted_at'));
    }

    public function test_future_allocations_allow_planning_but_not_current_capture_and_do_not_project_early(): void
    {
        $group = $this->group();
        $teacher = User::findOrFail($this->schoolA->ids['tara_user']);
        $allocation = app(TeachingGroups::class)->dated($this->superAdmin(), 'teachers', $this->dates(['teacher_id' => $this->teacher, 'starts_on' => '2026-03-01']), $group->id);
        $this->assertSame($group->id, app(AcademicAuthority::class)->group($teacher, $group->id)->id);
        $this->assertFalse(SubjectTeacher::where('teaching_allocation_id', $allocation->id)->exists());
        try {
            app(AcademicAuthority::class)->group($teacher, $group->id, true);
            $this->fail('Not yet allocated');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }
        DB::table('subject_teachers')->where('school_id', 1)->update(['deleted_at' => now()]);
        app(AcademicAccess::class)->context($teacher, 'curriculum-manage', $group->subject_id, $this->grade, $this->year);
        $this->assertTrue(true);
    }

    public function test_overlapping_assignments_and_stale_edits_are_refused_without_losing_history(): void
    {
        $group = $this->group();
        $service = app(TeachingGroups::class);
        $entry = $service->dated($this->superAdmin(), 'teachers', $this->dates(['teacher_id' => $this->teacher]), $group->id);
        try {
            $service->dated($this->superAdmin(), 'teachers', $this->dates(['teacher_id' => $this->teacher]), $group->id);
            $this->fail('Overlap');
        } catch (ValidationException) {
            $this->assertSame(1, TeachingAllocation::count());
        }
        $service->dated($this->superAdmin(), 'teachers', $this->dates(['teacher_id' => $this->teacher, 'ends_on' => '2026-02-09', 'revision' => 1]), $group->id, $entry->id);
        try {
            $service->dated($this->superAdmin(), 'teachers', $this->dates(['teacher_id' => $this->teacher, 'revision' => 1]), $group->id, $entry->id);
            $this->fail('Stale');
        } catch (HttpException $e) {
            $this->assertSame(409, $e->getStatusCode());
        }
        $this->assertSame('2026-02-09', $entry->fresh()->ends_on->toDateString());
    }

    public function test_membership_needs_a_complete_matching_enrolment_and_duplicate_intervals_are_refused(): void
    {
        $group = $this->group();
        $data = $this->dates(['learner_id' => $this->schoolA->ids['asha'], 'mark_rule' => 'excused']);
        Enrolment::where('learner_id', $this->schoolA->ids['asha'])->update(['ends_on' => '2026-06-30']);
        try {
            app(TeachingGroups::class)->dated($this->superAdmin(), 'memberships', $data, $group->id);
            $this->fail('Gap');
        } catch (ValidationException) {
            $this->assertSame(0, $group->memberships()->count());
        }
        $data['ends_on'] = '2026-06-30';
        app(TeachingGroups::class)->dated($this->superAdmin(), 'memberships', $data, $group->id);
        try {
            app(TeachingGroups::class)->dated($this->superAdmin(), 'memberships', $data, $group->id);
            $this->fail('Duplicate');
        } catch (ValidationException) {
            $this->assertSame(1, $group->memberships()->count());
        }
    }

    public function test_grade_head_authority_is_scoped_by_grade_year_dates_and_active_school_membership(): void
    {
        $teacher = User::findOrFail($this->schoolA->ids['tara_user']);
        app(TeachingGroups::class)->dated($this->superAdmin(), 'leadership', $this->dates(['user_id' => $teacher->id, 'kind' => 'grade_head', 'grade_level_id' => $this->grade, 'session_year_id' => $this->year]));
        $authority = app(AcademicAuthority::class);
        $this->assertTrue($authority->leads($teacher, 'grade_head', $this->grade, $this->year));
        $this->assertFalse($authority->leads($teacher, 'grade_head', $this->grade + 1, $this->year));
        $this->assertFalse($authority->leads($teacher, 'principal', $this->grade, $this->year));
        SchoolMembership::where('school_id', 1)->where('user_id', $teacher->id)->update(['status' => 'suspended']);
        try {
            $authority->leads($teacher, 'grade_head', $this->grade, $this->year);
            $this->fail('Suspended');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }
    }

    public function test_projection_adopts_matching_legacy_ids_and_flattens_two_groups_without_duplicate_assignments(): void
    {
        $legacy = $this->schoolA->ids['subject_teacher_a_maths'];
        DB::table('subject_teachers')->where('id', $legacy)->update(['term_id' => null]);
        $one = $this->group();
        $two = $this->group(['name' => 'Maths support']);
        $a = app(TeachingGroups::class)->dated($this->superAdmin(), 'teachers', $this->dates(['teacher_id' => $this->teacher]), $one->id);
        $b = app(TeachingGroups::class)->dated($this->superAdmin(), 'teachers', $this->dates(['teacher_id' => $this->teacher]), $two->id);
        $rows = SubjectTeacher::whereNotNull('teaching_allocation_id')->get();
        $this->assertCount(1, $rows);
        $this->assertSame($legacy, $rows->sole()->id);
        app(TeachingGroups::class)->dated($this->superAdmin(), 'teachers', $this->dates(['teacher_id' => $this->teacher, 'revision' => 1, 'ends_on' => '2026-02-09']), $two->id, $b->id);
        $this->assertSame($legacy, SubjectTeacher::where('teaching_allocation_id', $a->id)->sole()->id);
    }

    public function test_grade_heads_list_their_grade_but_structure_management_is_not_a_capture_grant(): void
    {
        $one = $this->group();
        $two = $this->group(['name' => 'Grade 8 group', 'grade_level_id' => $this->grade + 1, 'class_section_id' => null]);
        $teacher = User::findOrFail($this->schoolA->ids['tara_user']);
        app(TeachingGroups::class)->dated($this->superAdmin(), 'leadership', $this->dates(['user_id' => $teacher->id, 'kind' => 'grade_head', 'grade_level_id' => $this->grade, 'session_year_id' => $this->year]));
        $this->assertSame([$one->id], app(AcademicAuthority::class)->groups($teacher)->pluck('id')->all());
        $this->assertSame($one->id, app(AcademicAuthority::class)->group($teacher, $one->id)->id);
        try {
            app(AcademicAuthority::class)->group($teacher, $two->id);
            $this->fail('Other grade');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }
        try {
            app(AcademicAuthority::class)->group($this->superAdmin(), $one->id, true);
            $this->fail('Management is not teaching capture');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }
    }

    public function test_term_filtered_family_teacher_and_chat_consumers_include_current_dated_assignments(): void
    {
        Term::whereKey($this->schoolA->ids['term'])->update(['start_date' => '2026-01-01', 'end_date' => '2026-03-31']);
        ClassSessionConfig::updateOrCreate(['class_id' => $this->schoolA->ids['class_a'], 'session_year_id' => $this->year], ['include_terms' => true]);
        DB::table('class_subjects')->where('school_id', 1)->where('class_id', $this->schoolA->ids['class_a'])->where('session_year_id', $this->year)->update(['term_id' => $this->schoolA->ids['term']]);
        DB::table('subject_teachers')->where('school_id', 1)->update(['deleted_at' => now()]);
        DB::table('class_teachers')->where('school_id', 1)->update(['deleted_at' => now()]);
        $group = $this->group();
        app(TeachingGroups::class)->dated($this->superAdmin(), 'teachers', $this->dates(['teacher_id' => $this->teacher]), $group->id);
        $this->assertTrue(SubjectTeacher::forTermOrDated($this->schoolA->ids['term'])->where('teacher_id', $this->teacher)->exists());
        $this->api(User::findOrFail($this->schoolA->ids['farid_user']));
        $response = $this->getJson('/api/parent/teachers?child_id='.$this->schoolA->ids['asha'])->assertOk()->assertJsonPath('error', false);
        $this->assertContains($this->teacher, array_column($response->json('data'), 'id'));
        $parent = Parents::where('user_id', $this->schoolA->ids['farid_user'])->firstOrFail();
        $method = new \ReflectionMethod(ChatController::class, 'chatTeacherScope');
        $scope = $method->invoke(app(ChatController::class), $parent, $this->year);
        $this->assertContains($this->teacher, $scope['teacher_ids']);
    }

    public function test_mixed_groups_never_project_whole_class_authority_and_grade_moves_end_incompatible_membership(): void
    {
        $group = $this->group(['class_section_id' => null]);
        $member = app(TeachingGroups::class)->dated($this->superAdmin(), 'memberships', $this->dates(['learner_id' => $this->schoolA->ids['asha'], 'mark_rule' => 'catch_up']), $group->id);
        $allocation = app(TeachingGroups::class)->dated($this->superAdmin(), 'teachers', $this->dates(['teacher_id' => $this->teacher]), $group->id);
        $this->assertFalse(SubjectTeacher::where('teaching_allocation_id', $allocation->id)->exists());
        ClassSchool::whereKey($this->schoolA->ids['class_c'])->update(['grade_level_id' => $this->grade + 1]);
        app(EnrolmentService::class)->place($this->schoolA->ids['asha'], $this->schoolA->ids['section_c'], $this->year, '2026-02-11', 'Moved to another grade');
        $this->assertSame('2026-02-10', $member->fresh()->ends_on->toDateString());
        $this->travelTo(now()->addDay());
        app(AcademicProjections::class)->refresh();
        $this->assertFalse(SubjectTeacher::where('teaching_allocation_id', $allocation->id)->exists());
        $this->assertFalse(DB::table('subject_teachers')->where('teaching_allocation_id', $allocation->id)->where('class_section_id', $this->schoolA->ids['section_c'])->whereNull('deleted_at')->exists());
        $this->assertSame(1, DB::table('audit_log')->where('action', 'academics.membership_enrolment_reconciled')->count());
    }

    public function test_homeroom_projection_has_stable_ids_hidden_source_and_cannot_be_manually_edited(): void
    {
        $entry = app(TeachingGroups::class)->dated($this->superAdmin(), 'homerooms', $this->dates(['teacher_id' => $this->teacher, 'class_section_id' => $this->schoolA->ids['section_a'], 'session_year_id' => $this->year]));
        $projection = ClassTeacher::where('homeroom_allocation_id', $entry->id)->sole();
        $this->assertArrayNotHasKey('homeroom_allocation_id', $projection->toArray());
        try {
            $projection->update(['term_id' => 1]);
            $this->fail('Managed projection');
        } catch (HttpException $e) {
            $this->assertSame(409, $e->getStatusCode());
        }
        app(AcademicProjections::class)->refresh();
        $this->assertSame($projection->id, ClassTeacher::where('homeroom_allocation_id', $entry->id)->sole()->id);
    }
}
