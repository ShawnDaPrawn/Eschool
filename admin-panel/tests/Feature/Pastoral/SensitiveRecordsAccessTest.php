<?php

declare(strict_types=1);

namespace Tests\Feature\Pastoral;

use App\Models\Platform\PlatformStaffIdentity;
use App\Models\Platform\Role;
use App\Models\Platform\School;
use App\Models\Platform\SchoolMembership;
use App\Models\User;
use App\Services\Pastoral\SensitiveRecordsAccess;
use App\Services\Platform\Tenancy\CurrentSchool;
use App\Services\Platform\Tenancy\SchoolRoles;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\Concerns\UsesDemoSchool;
use Tests\TestCase;

class SensitiveRecordsAccessTest extends TestCase
{
    use UsesDemoSchool;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDemoSchool();
        app(CurrentSchool::class)->set(1);
    }

    private function person(string $name = 'tara'): User
    {
        return User::findOrFail($this->school->ids[$name.'_user']);
    }

    private function denied(User $actor): void
    {
        try {
            app(SensitiveRecordsAccess::class)->actor($actor);
            $this->fail('Restricted access must be refused.');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }
    }

    public function test_explicit_counsellor_role_and_permission_are_both_required(): void
    {
        $actor = $this->person();
        $this->denied($actor);
        $actor->givePermissionTo(SensitiveRecordsAccess::PERMISSION);
        $this->denied($actor);
        $actor->assignRole(SchoolRoles::COUNSELLOR);
        $this->assertSame($actor->id, app(SensitiveRecordsAccess::class)->actor($actor)->id);
        $actor->revokePermissionTo(SensitiveRecordsAccess::PERMISSION);
        Role::findByName(SchoolRoles::COUNSELLOR)->revokePermissionTo(SensitiveRecordsAccess::PERMISSION);
        $this->denied($actor);
    }

    public function test_platform_staff_cannot_enter_even_with_real_counsellor_assignment(): void
    {
        $actor = $this->person();
        $actor->assignRole(SchoolRoles::COUNSELLOR);
        PlatformStaffIdentity::create(['user_id' => $actor->id]);
        $this->denied($actor);
        PlatformStaffIdentity::where('user_id', $actor->id)->update(['active' => false]);
        $this->assertSame($actor->id, app(SensitiveRecordsAccess::class)->actor($actor)->id);
    }

    public function test_impersonation_and_global_contexts_are_refused(): void
    {
        $actor = $this->person();
        $actor->assignRole(SchoolRoles::SAFEGUARDING);
        app(CurrentSchool::class)->impersonate(School::findOrFail(1));
        $this->denied($actor);
        app(CurrentSchool::class)->allowAcrossSchools();
        $this->denied($actor);
        app(CurrentSchool::class)->forget();
        $this->denied($actor);
    }

    public function test_stale_membership_and_inactive_account_are_refused(): void
    {
        $actor = $this->person();
        $actor->assignRole(SchoolRoles::COUNSELLOR);
        SchoolMembership::where('school_id', 1)->where('user_id', $actor->id)->where('role', SchoolRoles::COUNSELLOR)->update(['status' => 'ended']);
        $this->denied($actor);
        SchoolMembership::where('school_id', 1)->where('user_id', $actor->id)->where('role', SchoolRoles::COUNSELLOR)->update(['status' => 'active']);
        DB::table('users')->where('id', $actor->id)->update(['status' => 0]);
        $this->expectException(ModelNotFoundException::class);
        app(SensitiveRecordsAccess::class)->actor($actor);
    }

    public function test_other_school_counselling_role_cannot_be_reused_in_current_school(): void
    {
        $actor = $this->person();
        $other = School::create(['name' => 'Other pastoral school', 'slug' => 'other-pastoral', 'status' => 'active', 'timezone' => 'Africa/Johannesburg']);
        app(SchoolRoles::class)->seed($other);
        app(CurrentSchool::class)->run($other, fn () => $actor->assignRole(SchoolRoles::COUNSELLOR));
        $this->denied($actor);
    }

    public function test_super_admin_and_facilitator_have_no_implicit_sensitive_access(): void
    {
        $actor = $this->person();
        foreach ([SchoolRoles::SUPER_ADMIN, SchoolRoles::FACILITATOR] as $role) {
            $actor->assignRole($role);
            $this->denied($actor);
        }
        $this->assertTrue(Role::findByName(SchoolRoles::FACILITATOR)->permissions()->doesntExist());
        $this->assertFalse(Role::findByName(SchoolRoles::SUPER_ADMIN)->permissions()->where('name', SensitiveRecordsAccess::PERMISSION)->exists());
    }

    public function test_trusted_operator_identity_command_is_scoped_global_and_grants_no_roles(): void
    {
        $actor = $this->person();
        $before = $actor->roles()->pluck('roles.name')->all();
        $this->assertSame(0, Artisan::call('platform:staff-identity', ['user_id' => $actor->id]));
        $this->assertTrue(PlatformStaffIdentity::where('user_id', $actor->id)->sole()->active);
        $this->assertSame($before, $actor->fresh()->roles()->pluck('roles.name')->all());
        $this->assertSame(0, Artisan::call('platform:staff-identity', ['user_id' => $actor->id, '--revoke' => true]));
        $this->assertFalse(PlatformStaffIdentity::where('user_id', $actor->id)->sole()->active);
        $this->assertSame(1, Artisan::call('platform:staff-identity', ['user_id' => 'invalid']));
        $this->assertSame(1, app(CurrentSchool::class)->id());
    }

    public function test_migration_refuses_to_drop_retained_operator_exclusions(): void
    {
        PlatformStaffIdentity::create(['user_id' => $this->person()->id]);
        $migration = require database_path('migrations/2026_10_25_020000_add_restricted_pastoral_access.php');
        try {
            $migration->down();
            $this->fail('Retained operator exclusions must not be silently erased.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('retained', $e->getMessage());
            $this->assertTrue(Schema::hasTable('platform_staff_identities'));
        }
    }

    public function test_custom_role_configuration_is_not_reset_by_system_role_seed(): void
    {
        $role = Role::findByName(SchoolRoles::COUNSELLOR);
        $role->forceFill(['custom_role' => 1])->save();
        $role->syncPermissions(['curriculum-read']);
        app(SchoolRoles::class)->seed(1);
        $this->assertSame(['curriculum-read'], $role->fresh()->permissions->pluck('name')->all());
        $this->assertSame([], Role::findByName(SchoolRoles::FACILITATOR)->permissions->all());
    }

    public function test_empty_registry_migration_can_roll_back_and_reapply_without_role_loss(): void
    {
        DB::rollBack();
        try {
            $roles = Role::count();
            $migration = require database_path('migrations/2026_10_25_020000_add_restricted_pastoral_access.php');
            $migration->down();
            $this->assertFalse(Schema::hasTable('platform_staff_identities'));
            $migration->up();
            $this->assertTrue(Schema::hasTable('platform_staff_identities'));
            $this->assertSame($roles, Role::count());
        } finally {
            DB::beginTransaction();
        }
    }
}
