<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Models\Platform\AuditLog;
use App\Models\Platform\OutboxEvent;
use App\Models\Platform\PlatformAdminGrant;
use App\Models\Platform\PlatformStaffIdentity;
use App\Models\Platform\School;
use App\Models\Platform\SchoolMembership;
use App\Models\User;
use App\Services\Platform\Identity\MembershipService;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Saas\PlatformStaffAccess;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Tests\Concerns\UsesTwoSchools;
use Tests\TestCase;

/**
 * The platform admin area (/saas): who may open it, what it shows, and that
 * everything it changes stays inside the school it was aimed at.
 * engineering/specs/platform/saas-panel.md.
 */
class SaasPanelTest extends TestCase
{
    use UsesTwoSchools;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        // A real signed-in /saas request has no school: school-owned queries throw unless the code says otherwise.
        app(CurrentSchool::class)->forget();
    }

    private function staff(): User
    {
        $user = $this->superAdmin();
        PlatformStaffIdentity::query()->updateOrCreate(['user_id' => $user->id], ['active' => true]);
        PlatformAdminGrant::query()->updateOrCreate(['user_id' => $user->id], ['active' => true, 'expires_at' => now()->addHour()]);

        return $user;
    }

    /** @return array<string, string> */
    private function newSchool(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Marula Primary',
            'slug' => '',
            'timezone' => 'Africa/Johannesburg',
            'package_key' => 'core',
            'admin_first_name' => 'Lerato',
            'admin_last_name' => 'Dlamini',
            'admin_email' => 'Lerato@Marula.Example',
            'admin_mobile' => '082 000 0000',
        ], $overrides);
    }

    private function moduleRows(int $schoolId): int
    {
        return DB::table('school_modules')->where('school_id', $schoolId)->count();
    }

    // ---- who may open it ----

    public function test_platform_staff_see_every_school_with_counts_and_never_a_person(): void
    {
        $this->actingAs($this->staff());

        $this->get('/saas')->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertSee('Baobab High')
            ->assertSee('Jacaranda')
            ->assertDontSee('@')
            ->assertViewHas('schools', fn (array $schools) => count($schools) === 2
                && $schools[0]['roles']['Student'] > 0 && $schools[1]['roles']['Student'] > 0);
        $this->get('/saas/schools/2')->assertOk()->assertSee('Baobab High')->assertDontSee('@');
        $this->get('/saas/schools/create')->assertOk();
    }

    public function test_a_school_super_admin_without_the_identity_is_refused_everywhere(): void
    {
        $this->actingAs($this->superAdmin());

        $this->get('/saas')->assertForbidden();
        $this->get('/saas/schools/create')->assertForbidden();
        $this->get('/saas/schools/2')->assertForbidden();
        $this->post('/saas/schools', $this->newSchool())->assertForbidden();
        $this->put('/saas/schools/2/package', ['package_key' => 'standard'])->assertForbidden();
        $this->put('/saas/schools/2/status', ['status' => 'suspended', 'reason' => 'Testing the refusal path'])->assertForbidden();
        $this->put('/saas/schools/2/modules/chat', ['enabled' => 0])->assertForbidden();

        $this->assertDatabaseMissing('schools', ['slug' => 'marula-primary']);
        $this->assertSame('active', School::query()->findOrFail(2)->status);
        $this->assertNull(School::query()->findOrFail(2)->package_key);
    }

    public function test_a_refusal_does_not_say_whether_a_school_exists(): void
    {
        $this->actingAs($this->superAdmin());

        $this->get('/saas/schools/2')->assertForbidden();
        $this->get('/saas/schools/987654')->assertForbidden();
    }

    public function test_guests_are_sent_to_sign_in(): void
    {
        $this->get('/saas')->assertRedirect('/login');
        $this->post('/saas/schools', $this->newSchool())->assertRedirect('/login');
    }

    public function test_a_revoked_inactive_or_unsettled_account_loses_access_at_once(): void
    {
        $staff = $this->staff();
        $this->actingAs($staff)->get('/saas')->assertOk();

        PlatformStaffIdentity::query()->where('user_id', $staff->id)->update(['active' => false]);
        $this->get('/saas')->assertForbidden();

        PlatformStaffIdentity::query()->where('user_id', $staff->id)->update(['active' => true]);
        $this->get('/saas')->assertOk();

        PlatformAdminGrant::query()->where('user_id', $staff->id)->update(['active' => false]);
        $this->get('/saas')->assertForbidden();

        PlatformAdminGrant::query()->where('user_id', $staff->id)->update(['active' => true]);
        $this->get('/saas')->assertOk();
        $this->travel(2)->hours();
        $this->get('/saas')->assertForbidden();
        $this->travelBack();
        $this->get('/saas')->assertOk();

        User::query()->whereKey($staff->id)->update(['password_change_required' => true]);
        $this->get('/saas')->assertForbidden();

        User::query()->whereKey($staff->id)->update(['password_change_required' => false, 'status' => 0]);
        $this->get('/saas')->assertForbidden();
    }

    public function test_acting_inside_a_school_removes_platform_access(): void
    {
        $staff = $this->staff();
        $access = app(PlatformStaffAccess::class);
        $this->assertTrue($access->allowed($staff));

        app(CurrentSchool::class)->impersonate(School::query()->findOrFail(1));
        try {
            $this->assertFalse($access->allowed($staff));
        } finally {
            app(CurrentSchool::class)->forget();
        }
    }

    public function test_the_identity_alone_or_the_grant_alone_opens_nothing(): void
    {
        $identityOnly = User::query()->create(['first_name' => 'Ida', 'last_name' => 'Only', 'email' => 'identity@platform.example', 'mobile' => '', 'password' => 'a-long-password-1', 'status' => 1]);
        PlatformStaffIdentity::query()->create(['user_id' => $identityOnly->id]);
        $this->actingAs($identityOnly)->get('/saas')->assertForbidden();

        $grantOnly = User::query()->create(['first_name' => 'Gus', 'last_name' => 'Only', 'email' => 'grant@platform.example', 'mobile' => '', 'password' => 'a-long-password-1', 'status' => 1]);
        PlatformAdminGrant::query()->create(['user_id' => $grantOnly->id, 'active' => true, 'expires_at' => now()->addHour()]);
        $this->actingAs($grantOnly)->get('/saas')->assertForbidden();
        $this->post('/saas/schools', $this->newSchool())->assertForbidden();
        $this->assertDatabaseMissing('schools', ['slug' => 'marula-primary']);
    }

    public function test_access_grants_no_school_permission(): void
    {
        $nobody = User::query()->create(['first_name' => 'Pat', 'last_name' => 'Operator', 'email' => 'operator@platform.example', 'mobile' => '', 'password' => 'a-long-password-1', 'status' => 1]);
        PlatformStaffIdentity::query()->create(['user_id' => $nobody->id]);
        PlatformAdminGrant::query()->create(['user_id' => $nobody->id, 'active' => true, 'expires_at' => now()->addHour()]);

        $this->actingAs($nobody)->get('/saas')->assertOk();
        $this->assertNotSame(200, $this->get('/school-modules')->getStatusCode());
        $this->assertNotSame(200, $this->get('/ai-settings')->getStatusCode());
    }

    public function test_every_saas_route_is_gated_and_none_works_in_a_school(): void
    {
        $count = 0;
        foreach (Route::getRoutes() as $route) {
            if (! str_starts_with($route->uri(), 'saas')) {
                continue;
            }
            $count++;
            $middleware = $route->gatherMiddleware();
            $this->assertContains('platform.staff', $middleware, $route->uri());
            $this->assertContains('auth', $middleware, $route->uri());
            $this->assertNotContains('school.context', $middleware, $route->uri());
        }
        $this->assertSame(7, $count);
    }

    // ---- setting a school up ----

    public function test_a_school_is_set_up_with_roles_and_its_first_administrator(): void
    {
        $staff = $this->staff();
        $before = [1 => $this->moduleRows(1), 2 => $this->moduleRows(2), 'members' => SchoolMembership::query()->count()];

        $response = $this->actingAs($staff)->post('/saas/schools', $this->newSchool())->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertSee('Marula Primary');
        $password = $response->viewData('password');

        $school = School::query()->where('slug', 'marula-primary')->firstOrFail();
        $this->assertSame('core', $school->package_key);
        $this->assertSame('active', $school->status);

        $admin = User::query()->where('email', 'lerato@marula.example')->firstOrFail();
        $this->assertTrue($admin->password_change_required);
        $this->assertTrue(Hash::check($password, $admin->password));
        $response->assertSee($password);

        // One membership: Super Admin of the new school only.
        $this->assertSame(1, SchoolMembership::query()->where('user_id', $admin->id)->count());
        $this->assertDatabaseHas('school_memberships', ['user_id' => $admin->id, 'school_id' => $school->id, 'role' => 'Super Admin', 'status' => 'active']);
        $this->assertSame($before['members'] + 1, SchoolMembership::query()->count());

        // The school's system roles exist for it alone, and its profile defaults were seeded.
        foreach (['Super Admin', 'Teacher', 'Parent', 'Student'] as $role) {
            $this->assertDatabaseHas('roles', ['school_id' => $school->id, 'name' => $role]);
        }
        $this->assertGreaterThan(0, DB::table('profile_field_categories')->where('school_id', $school->id)->count());

        // Other schools were not touched.
        $this->assertSame($before[1], $this->moduleRows(1));
        $this->assertSame($before[2], $this->moduleRows(2));

        // Audited with the staff member, and the password is nowhere in the log.
        $row = AuditLog::query()->where('action', 'platform_school_created')->latest('id')->firstOrFail();
        $this->assertSame($staff->id, $row->actor_id);
        $this->assertSame($school->id, $row->school_id);
        $this->assertSame($admin->id, $row->after['first_admin_user_id']);
        $this->assertStringNotContainsString($password, json_encode(AuditLog::query()->get()->toArray()));
        $this->assertStringNotContainsString($password, json_encode(DB::table('sessions')->get()));
    }

    public function test_the_new_administrator_sees_only_their_own_school_and_never_the_platform(): void
    {
        $this->actingAs($this->staff())->post('/saas/schools', $this->newSchool())->assertOk();
        $admin = User::query()->where('email', 'lerato@marula.example')->firstOrFail();
        $school = School::query()->where('slug', 'marula-primary')->firstOrFail();

        auth()->forgetGuards();
        $this->actingAs($admin)->get('/saas')->assertForbidden();
        $this->get('/saas/schools/1')->assertForbidden();

        $active = app(MembershipService::class)->active($admin);
        $this->assertSame([$school->id], $active->pluck('school_id')->all());
    }

    public function test_a_name_gets_a_unique_short_name_when_none_is_given(): void
    {
        $this->actingAs($this->staff());
        $this->post('/saas/schools', $this->newSchool())->assertOk();
        $this->post('/saas/schools', $this->newSchool(['admin_email' => 'second@marula.example']))->assertOk();

        $this->assertSame(['marula-primary', 'marula-primary-2'], School::query()->where('name', 'Marula Primary')->orderBy('id')->pluck('slug')->all());
    }

    public function test_bad_input_creates_nothing(): void
    {
        $this->actingAs($this->staff());
        $schools = School::query()->count();
        $users = User::query()->count();
        $taken = $this->superAdmin()->email;

        foreach ([
            ['admin_email' => $taken],
            ['admin_email' => 'not-an-email'],
            ['slug' => 'baobab-high'],
            ['slug' => 'Not A Slug'],
            ['package_key' => 'free-for-all'],
            ['timezone' => 'Mars/Olympus'],
            ['name' => ''],
            ['admin_mobile' => '<script>'],
        ] as $bad) {
            $this->post('/saas/schools', $this->newSchool($bad))->assertSessionHasErrors(array_keys($bad));
        }

        $this->assertSame($schools, School::query()->count());
        $this->assertSame($users, User::query()->count());
    }

    // ---- package, status, modules ----

    public function test_a_package_change_reaches_only_that_school_and_is_audited(): void
    {
        $staff = $this->staff();
        School::query()->whereKey(2)->update(['package_key' => 'core']);
        $before = OutboxEvent::query()->count();

        $this->actingAs($staff)->put('/saas/schools/2/package', ['package_key' => 'standard'])->assertRedirect('/saas/schools/2');

        $this->assertSame('standard', School::query()->findOrFail(2)->package_key);
        $this->assertNull(School::query()->findOrFail(1)->package_key);
        $row = AuditLog::query()->where('action', 'platform_school_package_changed')->firstOrFail();
        $this->assertSame([2, $staff->id, 'core', 'standard'], [$row->school_id, $row->actor_id, $row->before['package_key'], $row->after['package_key']]);
        $this->assertGreaterThan($before, OutboxEvent::query()->count());
        app(CurrentSchool::class)->run(2, fn () => $this->assertTrue(app(ModuleGate::class)->state('fees')['in_package']));
    }

    public function test_an_unknown_package_is_refused(): void
    {
        $this->actingAs($this->staff())->put('/saas/schools/2/package', ['package_key' => 'platinum'])->assertSessionHasErrors('package_key');
        $this->assertNull(School::query()->findOrFail(2)->package_key);
    }

    public function test_suspending_a_school_closes_its_memberships_not_its_data_and_can_be_undone(): void
    {
        $staff = $this->staff();
        $baobab = User::query()->findOrFail($this->schoolB->ids['tara_user']);
        $jacaranda = User::query()->findOrFail($this->schoolA->ids['tara_user']);
        $records = DB::table('students')->where('school_id', 2)->count();
        $this->assertSame([2], app(MembershipService::class)->active($baobab)->pluck('school_id')->all());

        $this->actingAs($staff)->put('/saas/schools/2/status', ['status' => 'suspended', 'reason' => 'Fees unpaid for two terms'])->assertRedirect('/saas/schools/2');

        $this->assertSame('suspended', School::query()->findOrFail(2)->status);
        $this->assertTrue((new MembershipService)->active($baobab)->isEmpty());
        $this->assertSame([1], (new MembershipService)->active($jacaranda)->pluck('school_id')->all());
        $this->assertSame($records, DB::table('students')->where('school_id', 2)->count());
        $row = AuditLog::query()->where('action', 'platform_school_status_changed')->firstOrFail();
        $this->assertSame([2, $staff->id, 'active', 'suspended', 'Fees unpaid for two terms'], [$row->school_id, $row->actor_id, $row->before['status'], $row->after['status'], $row->after['reason']]);

        $this->put('/saas/schools/2/status', ['status' => 'active', 'reason' => 'Account settled in full'])->assertRedirect();
        $this->assertSame([2], (new MembershipService)->active($baobab)->pluck('school_id')->all());
    }

    public function test_a_status_change_needs_a_real_reason_and_never_touches_the_default_school(): void
    {
        $this->actingAs($this->staff());

        $this->put('/saas/schools/2/status', ['status' => 'suspended', 'reason' => 'short'])->assertSessionHasErrors('reason');
        $this->put('/saas/schools/2/status', ['status' => 'deleted', 'reason' => 'A perfectly long reason'])->assertSessionHasErrors('status');
        $this->put('/saas/schools/1/status', ['status' => 'suspended', 'reason' => 'Trying to lock out sign-in'])->assertSessionHasErrors('status');

        $this->assertSame(['active', 'active'], School::query()->orderBy('id')->pluck('status')->all());
        $this->assertSame(0, AuditLog::query()->where('action', 'platform_school_status_changed')->count());
    }

    public function test_a_module_switch_stays_in_the_school_it_was_aimed_at(): void
    {
        $staff = $this->staff();
        School::query()->whereKey(2)->update(['package_key' => 'core']);
        $jacarandaRows = $this->moduleRows(1);

        $this->actingAs($staff)->put('/saas/schools/2/modules/chat', ['enabled' => 0])->assertRedirect('/saas/schools/2');

        $this->assertDatabaseHas('school_modules', ['school_id' => 2, 'module' => 'chat', 'enabled' => false, 'changed_by' => $staff->id]);
        $this->assertSame($jacarandaRows, $this->moduleRows(1));
        app(CurrentSchool::class)->run(1, fn () => $this->assertTrue(app(ModuleGate::class)->allows('chat')));
        app(CurrentSchool::class)->run(2, fn () => $this->assertFalse(app(ModuleGate::class)->allows('chat')));
        $this->assertTrue(AuditLog::query()->where('action', 'platform_school_module_changed')->where('school_id', 2)->where('actor_id', $staff->id)->exists());

        $this->put('/saas/schools/2/modules/chat', ['enabled' => 1])->assertRedirect();
        app(CurrentSchool::class)->run(2, fn () => $this->assertTrue(app(ModuleGate::class)->allows('chat')));
    }

    public function test_module_rules_still_apply_to_platform_staff(): void
    {
        $this->actingAs($this->staff());
        School::query()->whereKey(2)->update(['package_key' => 'core']);

        // Required modules stay on; a module outside the package is not switched on by a switch alone.
        $this->put('/saas/schools/2/modules/people', ['enabled' => 0])->assertSessionHasErrors('module');
        $this->put('/saas/schools/2/modules/fees', ['enabled' => 1])->assertSessionHasErrors('module');
        $this->put('/saas/schools/2/modules/not-a-module', ['enabled' => 1])->assertNotFound();
        $this->put('/saas/schools/2/modules/chat', ['enabled' => 'maybe'])->assertSessionHasErrors('enabled');
    }

    public function test_a_school_that_does_not_exist_is_not_found_for_staff(): void
    {
        $this->actingAs($this->staff());

        $this->get('/saas/schools/987654')->assertNotFound();
        $this->get('/saas/schools/abc')->assertNotFound();
        $this->put('/saas/schools/987654/package', ['package_key' => 'core'])->assertNotFound();
        $this->put('/saas/schools/987654/status', ['status' => 'suspended', 'reason' => 'No such school at all'])->assertNotFound();
        $this->put('/saas/schools/987654/modules/chat', ['enabled' => 0])->assertNotFound();
    }

    // ---- the operator console grant ----

    public function test_the_console_grants_and_revokes_access_only_to_a_registered_operator(): void
    {
        $user = $this->superAdmin();

        $this->artisan('platform:admin-access', ['user_id' => $user->id])->assertFailed();
        $this->assertDatabaseMissing('platform_admin_grants', ['user_id' => $user->id]);

        PlatformStaffIdentity::query()->create(['user_id' => $user->id]);
        $this->artisan('platform:admin-access', ['user_id' => $user->id, '--hours' => 2])->assertSuccessful();
        $grant = PlatformAdminGrant::query()->where('user_id', $user->id)->firstOrFail();
        $this->assertTrue($grant->active);
        $this->assertEqualsWithDelta(now()->addHours(2)->timestamp, $grant->expires_at->timestamp, 5);
        $this->assertTrue(AuditLog::query()->where('subject_type', PlatformAdminGrant::class)->exists());
        $this->actingAs($user)->get('/saas')->assertOk();

        $this->artisan('platform:admin-access', ['user_id' => $user->id, '--revoke' => true])->assertSuccessful();
        $this->assertFalse($grant->fresh()->active);
        $this->get('/saas')->assertForbidden();
    }

    public function test_the_console_refuses_bad_users_and_expiries(): void
    {
        $user = $this->superAdmin();
        PlatformStaffIdentity::query()->create(['user_id' => $user->id]);

        foreach ([['user_id' => 'abc'], ['user_id' => 987654], ['user_id' => $user->id, '--hours' => 0], ['user_id' => $user->id, '--hours' => 9000], ['user_id' => $user->id, '--hours' => 'soon']] as $arguments) {
            $this->artisan('platform:admin-access', $arguments)->assertFailed();
        }
        $this->assertSame(0, PlatformAdminGrant::query()->count());
    }
}
