<?php

declare(strict_types=1);

namespace Tests\Feature\Academics;

use App\Models\Academics\AcademicRatingLevel;
use App\Models\Academics\GradeLevel;
use App\Models\ClassSchool;
use App\Models\Platform\SchoolMembership;
use App\Models\User;
use App\Services\Academics\AcademicReferences;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\Concerns\UsesTwoSchools;
use Tests\Concerns\ValidatesOpenApiResponses;
use Tests\TestCase;

class AcademicReferencesTest extends TestCase
{
    use UsesTwoSchools, ValidatesOpenApiResponses;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
    }

    private function path(?int $id = null): string
    {
        return '/api/v2/academics/classes/'.($id ?? $this->schoolA->ids['class_a']).'/grade-level';
    }

    private function asPerson(string $key): self
    {
        $this->app['auth']->forgetGuards();

        return $this->flushHeaders()->withToken(User::findOrFail($this->schoolA->ids[$key])->createToken('references')->plainTextToken);
    }

    private function asAdmin(): self
    {
        $this->app['auth']->forgetGuards();

        return $this->flushHeaders()->withToken($this->superAdmin()->createToken('references')->plainTextToken);
    }

    public function test_reference_has_the_complete_grade_and_phase_mapping_for_every_profile(): void
    {
        foreach (['asha_user', 'farid_user', 'tara_user'] as $key) {
            $reply = $this->asPerson($key)->getJson('/api/v2/academics/reference')->assertOk()
                ->assertHeader('Cache-Control', 'no-store, private')->assertJsonCount(13, 'data.grades')->assertJsonCount(7, 'data.rating_scale');
            $this->assertSame(['R', '1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12'], array_column($reply->json('data.grades'), 'code'));
            $this->assertSame(['Foundation', 'Foundation', 'Foundation', 'Foundation', 'Intermediate', 'Intermediate', 'Intermediate', 'Senior', 'Senior', 'Senior', 'FET', 'FET', 'FET'], array_column($reply->json('data.grades'), 'phase'));
        }
        $this->assertSame(13, GradeLevel::query()->count());
        $this->assertSame(7, AcademicRatingLevel::query()->count());
    }

    public function test_rating_intervals_cover_decimal_percentages_once_and_match_all_boundaries(): void
    {
        $ratings = AcademicRatingLevel::query()->orderBy('level')->get()->toArray();
        $this->assertSame([0, 3000, 4000, 5000, 6000, 7000, 8000], array_column($ratings, 'min_basis_points'));
        $this->assertSame([2999, 3999, 4999, 5999, 6999, 7999, 10000], array_column($ratings, 'max_basis_points'));
        foreach (range(0, 10000) as $basisPoints) {
            $matches = array_filter($ratings, fn ($rating) => $basisPoints >= $rating['min_basis_points'] && $basisPoints <= $rating['max_basis_points']);
            $this->assertCount(1, $matches, "Rating gap/overlap at $basisPoints basis points");
        }
    }

    public function test_admin_can_explicitly_map_and_clear_with_audit_without_touching_legacy_grades(): void
    {
        $id = $this->schoolA->ids['class_a'];
        $class = ClassSchool::findOrFail($id);
        $legacyGrades = DB::table('grades')->get()->toArray();
        $legacyClass = $class->toArray();
        $this->assertNull($class->grade_level_id);
        $this->asAdmin()->getJson($this->path())->assertOk()->assertJsonPath('data.grade_level', null);
        $grade = GradeLevel::query()->where('code', '7')->sole();
        $this->putJson($this->path(), ['grade_level_id' => $grade->id, 'school_id' => 2, 'name' => 'Do not rename'])
            ->assertOk()->assertJsonPath('data.class_id', $id)->assertJsonPath('data.grade_level_id', $grade->id)
            ->assertJsonPath('data.grade_level.phase', 'Senior');
        $this->getJson($this->path())->assertOk()->assertJsonPath('data.grade_level.code', '7');
        $audit = DB::table('audit_log')->where('action', 'academics.class_grade_mapped')->sole();
        $this->assertSame(1, (int) $audit->school_id);
        $this->assertSame($id, (int) $audit->subject_id);
        $this->assertSame($this->superAdmin()->id, (int) $audit->actor_id);
        $this->assertSame(['grade_level_id' => null], json_decode($audit->before, true));
        $this->assertSame(['grade_level_id' => $grade->id], json_decode($audit->after, true));
        $this->putJson($this->path(), ['grade_level_id' => $grade->id])->assertOk();
        $this->assertSame(1, DB::table('audit_log')->where('action', 'academics.class_grade_mapped')->count());
        $this->assertSame($legacyClass, $class->fresh()->toArray()); // v1 shape and all legacy attributes.
        $this->assertEquals($legacyGrades, DB::table('grades')->get()->toArray());
        $this->putJson($this->path(), ['grade_level_id' => null])->assertOk()->assertJsonPath('data.grade_level', null);
        $this->assertNull($class->fresh()->grade_level_id);
        $this->assertSame(2, DB::table('audit_log')->where('action', 'academics.class_grade_mapped')->count());
        $this->assertNull(DB::table('classes')->where('id', $this->schoolB->ids['class_a'])->value('grade_level_id'));
    }

    public function test_teacher_parent_and_learner_cannot_read_or_change_class_mappings(): void
    {
        foreach (['asha_user', 'farid_user', 'tara_user'] as $key) {
            $this->asPerson($key)->getJson($this->path())->assertForbidden();
            $this->putJson($this->path(), ['grade_level_id' => 1])->assertForbidden();
            $this->putJson($this->path(), ['grade_level_id' => 'invalid'])->assertForbidden();
        }
        $this->assertSame(0, DB::table('classes')->whereNotNull('grade_level_id')->count());
        $this->assertSame(0, DB::table('audit_log')->where('action', 'academics.class_grade_mapped')->count());
    }

    public function test_foreign_unknown_and_deleted_class_ids_are_refused(): void
    {
        $this->asAdmin();
        foreach ([$this->schoolB->ids['class_a'], 999999] as $id) {
            $this->getJson($this->path($id))->assertNotFound();
            $this->putJson($this->path($id), ['grade_level_id' => 1])->assertNotFound();
        }
        DB::table('classes')->where('id', $this->schoolA->ids['class_a'])->update(['deleted_at' => now()]);
        $this->getJson($this->path())->assertNotFound();
        $this->putJson($this->path(), ['grade_level_id' => 1])->assertNotFound();
        $this->assertSame(0, DB::table('classes')->whereNotNull('grade_level_id')->count());
    }

    public function test_missing_invalid_and_nonexistent_grade_ids_never_write(): void
    {
        $this->asAdmin();
        foreach ([[], ['grade_level_id' => 'x'], ['grade_level_id' => 999999], ['grade_level_id' => 1.5], ['grade_level_id' => []]] as $body) {
            $this->putJson($this->path(), $body)->assertUnprocessable()->assertJsonPath('code', 'validation_failed');
        }
        $this->assertSame(0, DB::table('classes')->whereNotNull('grade_level_id')->count());
    }

    public function test_class_list_permission_can_read_but_cannot_write(): void
    {
        $teacher = User::findOrFail($this->schoolA->ids['tara_user']);
        $teacher->givePermissionTo('class-list');
        $this->asPerson('tara_user')->getJson($this->path())->assertOk()->assertJsonPath('data.grade_level_id', null);
        $this->putJson($this->path(), ['grade_level_id' => 1])->assertForbidden();
    }

    public function test_expired_admin_role_is_not_reused_while_teacher_membership_remains(): void
    {
        $admin = $this->superAdmin();
        $admin->assignRole('Teacher');
        $admin->load('roles');
        SchoolMembership::query()->where('school_id', 1)->where('user_id', $admin->id)->where('role', 'Super Admin')
            ->update(['ends_on' => now()->subDay()->toDateString()]);
        $this->asAdmin()->getJson($this->path())->assertForbidden();
        $this->putJson($this->path(), ['grade_level_id' => 1])->assertForbidden();
        $this->getJson('/api/v2/academics/reference')->assertOk();
    }

    public function test_same_actor_at_two_schools_cannot_reuse_the_other_schools_admin_role(): void
    {
        $admin = $this->superAdmin();
        app(CurrentSchool::class)->run(2, fn () => $admin->assignRole('Teacher'));
        $admin->load('roles');
        $this->asAdmin()->withHeader('X-School-Id', '2')->getJson('/api/v2/academics/reference')->assertOk();
        $this->putJson($this->path($this->schoolB->ids['class_a']), ['grade_level_id' => 1])->assertForbidden();
        $this->getJson($this->path($this->schoolB->ids['class_a']))->assertForbidden();
        $this->withHeader('X-School-Id', '1')->putJson($this->path(), ['grade_level_id' => 1])->assertOk();
    }

    public function test_school_context_and_active_account_are_required(): void
    {
        $this->getJson('/api/v2/academics/reference')->assertUnauthorized();
        $this->getJson($this->path())->assertUnauthorized();
        $this->putJson($this->path(), ['grade_level_id' => 1])->assertUnauthorized();
        $this->asAdmin()->withHeader('X-School-Id', '2')->getJson('/api/v2/academics/reference')->assertForbidden();
        $admin = $this->superAdmin();
        $admin->status = 0;
        $admin->save();
        $this->asAdmin()->putJson($this->path(), ['grade_level_id' => 1])->assertNotFound();
    }

    public function test_service_rechecks_authority_without_http_middleware(): void
    {
        $this->expectException(HttpException::class);
        app(AcademicReferences::class)->map(User::findOrFail($this->schoolA->ids['tara_user']), $this->schoolA->ids['class_a'], ['grade_level_id' => 1]);
    }

    public function test_idempotency_replays_a_mapping_without_new_audit_and_rejects_changed_payload(): void
    {
        $this->asAdmin()->withHeader('Idempotency-Key', 'academic-map-test')
            ->putJson($this->path(), ['grade_level_id' => 1])->assertOk();
        $this->putJson($this->path(), ['grade_level_id' => 1])->assertOk()->assertHeader('Idempotent-Replayed', 'true');
        $this->putJson($this->path(), ['grade_level_id' => 2])->assertUnprocessable();
        $this->assertSame(1, DB::table('audit_log')->where('action', 'academics.class_grade_mapped')->count());
    }

    public function test_idempotent_replay_rechecks_revoked_class_permission_and_inactive_accounts(): void
    {
        $admin = $this->superAdmin();
        $admin->assignRole('Teacher');
        $this->asAdmin()->withHeader('Idempotency-Key', 'revoked-map-test')
            ->putJson($this->path(), ['grade_level_id' => 1])->assertOk();
        SchoolMembership::query()->where('school_id', 1)->where('user_id', $admin->id)->where('role', 'Super Admin')
            ->update(['ends_on' => now()->subDay()->toDateString()]);
        $this->putJson($this->path(), ['grade_level_id' => 1])->assertForbidden();
        $this->assertSame(1, DB::table('audit_log')->where('action', 'academics.class_grade_mapped')->count());
        $admin->status = 0;
        $admin->save();
        $this->putJson($this->path(), ['grade_level_id' => 1])->assertNotFound();
    }
}
