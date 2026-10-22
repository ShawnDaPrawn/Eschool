<?php

declare(strict_types=1);

namespace Tests\Feature\Tenancy;

use App\Models\Platform\School;
use App\Models\Platform\SchoolMembership;
use App\Models\User;
use App\Services\Platform\Identity\MembershipService;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * school.context and memberships: a signed-in request works in one of the
 * user's active schools, chosen by X-School-Id (the apps) or else the first,
 * and a school the user is not an active member of is refused.
 */
class SchoolContextTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedLikeInstaller();
        School::query()->forceCreate(['id' => 2, 'name' => 'Baobab High', 'slug' => 'baobab-high', 'timezone' => 'Africa/Johannesburg']);
        School::query()->forceCreate(['id' => 3, 'name' => 'Closed School', 'slug' => 'closed-school', 'timezone' => 'Africa/Johannesburg', 'status' => School::SUSPENDED]);

        Route::middleware(['api', 'auth:sanctum', 'school.context'])->get('api/_tenancy/school', fn () => ['school' => app(CurrentSchool::class)->id()]);
        Route::middleware(['api', 'auth:sanctum', 'school.context:optional'])->get('api/_tenancy/optional', fn () => ['school' => app(CurrentSchool::class)->idOrNull()]);

        $this->teacher = User::query()->forceCreate([
            'first_name' => 'Thandi', 'last_name' => 'Mokoena', 'email' => 'thandi@example.com',
            'password' => bcrypt('Secret-Pass-2026'), 'gender' => 'Female', 'mobile' => '0820000000', 'status' => 1,
        ]);
        $this->teacher->assignRole('Teacher');
    }

    public function test_assigning_a_role_grants_the_membership_and_removing_it_ends_it(): void
    {
        $membership = SchoolMembership::query()->where('user_id', $this->teacher->id)->sole();
        $this->assertSame([1, 'Teacher', SchoolMembership::ACTIVE], [$membership->school_id, $membership->role, $membership->status]);
        $this->assertSame(now()->toDateString(), $membership->starts_on?->toDateString());

        $this->teacher->removeRole('Teacher');
        $membership->refresh();
        $this->assertSame(SchoolMembership::ENDED, $membership->status);
        $this->assertSame(now()->toDateString(), $membership->ends_on?->toDateString());
        $this->assertSame([], app(MembershipService::class)->schools($this->teacher->fresh()));
    }

    public function test_the_header_picks_among_the_users_schools_and_anything_else_is_refused(): void
    {
        Sanctum::actingAs($this->teacher);
        $this->getJson('api/_tenancy/school')->assertOk()->assertExactJson(['school' => 1]);

        // Not a member of school 2 yet.
        $this->getJson('api/_tenancy/school', ['X-School-Id' => '2'])->assertForbidden();
        $this->getJson('api/_tenancy/school', ['X-School-Id' => 'two'])->assertForbidden();

        app(MembershipService::class)->grant($this->teacher, 2, 'Teacher');
        $this->getJson('api/_tenancy/school', ['X-School-Id' => '2'])->assertOk()->assertExactJson(['school' => 2]);
        // Without the header: the first school.
        $this->getJson('api/_tenancy/school')->assertOk()->assertExactJson(['school' => 1]);

        // A suspended school is no one's to work in.
        app(MembershipService::class)->grant($this->teacher, 3, 'Teacher');
        $this->getJson('api/_tenancy/school', ['X-School-Id' => '3'])->assertForbidden();
    }

    public function test_a_user_with_no_active_membership_is_refused(): void
    {
        SchoolMembership::query()->where('user_id', $this->teacher->id)->update(['ends_on' => now()->subDay()->toDateString()]);
        Sanctum::actingAs($this->teacher);

        $this->getJson('api/_tenancy/school')->assertForbidden()->assertJsonPath('message', trans('no_school_membership'));
        // Signing out still works.
        $this->getJson('api/_tenancy/optional')->assertOk()->assertExactJson(['school' => null]);

        SchoolMembership::query()->where('user_id', $this->teacher->id)->update(['ends_on' => null, 'starts_on' => now()->addDay()->toDateString()]);
        $this->getJson('api/_tenancy/school')->assertForbidden();
    }

    public function test_the_panel_signs_out_a_user_with_no_active_membership(): void
    {
        $admin = $this->superAdmin();
        SchoolMembership::query()->where('user_id', $admin->id)->update(['status' => SchoolMembership::SUSPENDED]);

        $this->actingAs($admin)->get('/home')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_public_routes_use_the_default_school_or_the_signed_in_users(): void
    {
        Route::middleware(['api', 'school.context:public'])->get('api/_tenancy/public', fn () => ['school' => app(CurrentSchool::class)->id()]);
        app(MembershipService::class)->grant($this->teacher, 2, 'Teacher');
        SchoolMembership::query()->where(['user_id' => $this->teacher->id, 'school_id' => 1])->update(['status' => SchoolMembership::ENDED]);

        $this->getJson('api/_tenancy/public')->assertExactJson(['school' => 1]);
        $this->getJson('api/_tenancy/public', ['X-School-Id' => '2'])->assertExactJson(['school' => 2]);
        // A suspended school's public pages are not served from it.
        $this->getJson('api/_tenancy/public', ['X-School-Id' => '3'])->assertExactJson(['school' => 1]);

        $token = $this->teacher->createToken('test')->plainTextToken;
        $this->withToken($token)->getJson('api/_tenancy/public')->assertExactJson(['school' => 2]);
    }
}
