<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Jobs\Platform\SendMail;
use App\Mail\Platform\StaffInvitationMail;
use App\Models\Platform\AuditLog;
use App\Models\Platform\OutboxEvent;
use App\Models\Platform\PlatformAdminGrant;
use App\Models\Platform\PlatformStaffIdentity;
use App\Models\Platform\School;
use App\Models\Platform\SchoolMembership;
use App\Models\Platform\StaffInvitation;
use App\Models\SessionYear;
use App\Models\Settings;
use App\Models\User;
use App\Services\Platform\Identity\MembershipService;
use App\Services\Platform\Identity\StaffInvitations;
use App\Services\Platform\Ledger\AccountType;
use App\Services\Platform\Ledger\LedgerService;
use App\Services\Platform\Ledger\Money;
use App\Services\Platform\Ledger\Posting;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Saas\PlatformStaffAccess;
use App\Services\Platform\Tenancy\CurrentSchool;
use App\Services\SettingsConfigService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
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

    private function seedCanonicalFeeHistory(): int
    {
        return app(CurrentSchool::class)->run(2, function (): int {
            $ledger = app(LedgerService::class);
            $cash = $ledger->openAccount(AccountType::CashDesk, 'synthetic:saas:cash', 'ZAR');
            $family = $ledger->openAccount(AccountType::Family, 'synthetic:saas:family', 'ZAR');

            return $ledger->post('synthetic:saas:receipt', 'synthetic:saas:receipt', 'Synthetic retained receipt', [
                Posting::debit($cash, Money::ofCents(100)), Posting::credit($family, Money::ofCents(100)),
            ]);
        });
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
        $this->get('/saas/password')->assertForbidden();
        $this->postJson('/saas/password', [])->assertForbidden();
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
            $this->assertContains(str_starts_with($route->getName() ?? '', 'saas.password') ? 'platform.staff:password' : 'platform.staff', $middleware, $route->uri());
            $this->assertContains('auth', $middleware, $route->uri());
            $this->assertNotContains('school.context', $middleware, $route->uri());
            if (! str_starts_with($route->getName() ?? '', 'saas.password')) {
                $this->assertNotEmpty(array_filter($middleware, fn ($name) => is_string($name) && str_starts_with($name, 'platform.can:')), $route->uri().' names the ability its role must hold');
            }
        }
        $this->assertSame(45, $count);
    }

    public function test_a_role_free_platform_operator_can_sign_in_without_receiving_school_access(): void
    {
        $operator = User::query()->create(['first_name' => 'Platform', 'last_name' => 'Operator', 'email' => 'role-free@platform.example', 'mobile' => '', 'password' => 'Synthetic-operator-login-937!', 'status' => 1]);
        PlatformStaffIdentity::query()->create(['user_id' => $operator->id]);
        PlatformAdminGrant::query()->create(['user_id' => $operator->id, 'active' => true, 'expires_at' => now()->addHour()]);

        $this->post('/login', ['email' => $operator->email, 'password' => 'Synthetic-operator-login-937!'])->assertRedirect('/saas');
        $this->assertAuthenticatedAs($operator);
        $this->get('/saas')->assertOk();
        $this->assertSame(0, SchoolMembership::query()->where('user_id', $operator->id)->count());
        $this->assertFalse(app(CurrentSchool::class)->run(1, fn () => $operator->fresh()->hasRole('Super Admin')));
        $this->assertNotSame(200, $this->get('/school-modules')->getStatusCode());
    }

    public function test_package_downgrade_keeps_existing_financial_records_readable_only(): void
    {
        $staff = $this->staff();
        $entry = $this->seedCanonicalFeeHistory();
        $count = DB::table('fees_paids')->where('school_id', 2)->count();
        $this->assertGreaterThan(0, $count);
        $this->actingAs($staff)->put('/saas/schools/2/package', ['package_key' => 'core'])->assertRedirect();

        app(CurrentSchool::class)->run(2, function (): void {
            app(ModuleGate::class)->forget();
            $this->assertTrue(app(ModuleGate::class)->allows('fees'));
            $this->assertFalse(app(ModuleGate::class)->writable('fees'));
        });
        $this->assertSame($count, DB::table('fees_paids')->where('school_id', 2)->count());
        $this->assertDatabaseHas('ledger_entries', ['id' => $entry, 'school_id' => 2]);
        $this->assertNull(School::query()->findOrFail(1)->package_key);
        auth()->forgetGuards();
        $this->withToken($this->schoolB->tokens['asha'])->withHeader('X-School-Id', '2')->getJson('/api/student/fees-paid-list')->assertOk()->assertJsonPath('code', 410)->assertJsonPath('data', null);
    }

    public function test_role_free_sign_in_still_refuses_missing_expired_revoked_or_unsettled_authority(): void
    {
        foreach (['identity_missing', 'grant_missing', 'expired', 'revoked', 'inactive'] as $case) {
            auth()->forgetGuards();
            app(CurrentSchool::class)->forget();
            $password = $case === 'default_password' ? 'superadmin' : 'Synthetic-operator-login-937!';
            $operator = User::query()->create(['first_name' => 'Platform', 'last_name' => 'Refused', 'email' => $case.'@operator.example', 'mobile' => '', 'password' => $password, 'status' => $case === 'inactive' ? 0 : 1]);
            if ($case === 'password_pending') {
                $operator->forceFill(['password_change_required' => true])->save();
            }
            if ($case !== 'identity_missing') {
                PlatformStaffIdentity::query()->create(['user_id' => $operator->id]);
            }
            if ($case !== 'grant_missing') {
                PlatformAdminGrant::query()->create(['user_id' => $operator->id, 'active' => $case !== 'revoked', 'expires_at' => $case === 'expired' ? now()->subMinute() : now()->addHour()]);
            }
            $this->post('/login', ['email' => $operator->email, 'password' => $password])->assertRedirect('/login');
            $this->assertGuest();
        }
    }

    public function test_the_created_school_admin_chooses_a_password_from_their_invitation_and_opens_their_home(): void
    {
        $response = $this->actingAs($this->staff())->post('/saas/schools', $this->newSchool())->assertOk();
        $path = (string) parse_url($response->viewData('invitationUrl'), PHP_URL_PATH);
        $admin = $response->viewData('admin');
        auth()->forgetGuards();
        app(CurrentSchool::class)->forget();

        $this->get($path)->assertOk()->assertSee('Marula Primary');
        $this->post($path, ['password' => 'Synthetic-new-school-password-746!', 'password_confirmation' => 'Synthetic-new-school-password-746!'])->assertRedirect(route('login'));
        $this->assertFalse($admin->fresh()->password_change_required);
        $this->assertNotNull($admin->fresh()->email_verified_at);
        $this->post('/login', ['email' => $admin->email, 'password' => 'Synthetic-new-school-password-746!'])->assertRedirect('/home');
        $this->assertAuthenticatedAs($admin);
        $this->get('/home')->assertOk();
        $this->getJson('/home/widget/fees-collection')->assertNotFound();
        foreach (['attendance', 'assessment', 'leaves', 'events'] as $widget) {
            $this->getJson('/home/widget/'.$widget)->assertOk();
        }
        $this->get('/saas')->assertForbidden();
    }

    public function test_staff_can_resend_an_unaccepted_administrator_invitation_but_not_after_acceptance(): void
    {
        $response = $this->actingAs($this->staff())->post('/saas/schools', $this->newSchool())->assertOk();
        $first = (string) parse_url($response->viewData('invitationUrl'), PHP_URL_PATH);
        $school = School::query()->where('slug', 'marula-primary')->firstOrFail();
        $this->get('/saas/schools/'.$school->id)->assertOk()->assertSee('Send the invitation again')->assertDontSee('lerato@marula.example');

        $this->post('/saas/schools/'.$school->id.'/administrator-invitation')->assertRedirect('/saas/schools/'.$school->id);
        $this->assertSame(2, StaffInvitation::acrossSchools()->where('school_id', $school->id)->count());
        auth()->forgetGuards();
        $this->get($first)->assertNotFound();
        $latest = StaffInvitation::acrossSchools()->where('school_id', $school->id)->latest('id')->firstOrFail();
        $second = (string) parse_url(app(StaffInvitations::class)->url((string) $latest->encrypted_token), PHP_URL_PATH);
        $this->post($second, ['password' => 'Synthetic-new-school-password-746!', 'password_confirmation' => 'Synthetic-new-school-password-746!'])->assertRedirect(route('login'));

        $this->actingAs($this->staff())->get('/saas/schools/'.$school->id)->assertOk()->assertDontSee('Send the invitation again');
        $this->post('/saas/schools/'.$school->id.'/administrator-invitation')->assertRedirect();
        $this->assertSame(2, StaffInvitation::acrossSchools()->where('school_id', $school->id)->count());
        $this->post('/saas/schools/999999/administrator-invitation')->assertNotFound();
    }

    public function test_school_counts_only_include_current_memberships_of_active_people(): void
    {
        $this->actingAs($this->staff());
        $before = collect($this->get('/saas')->assertOk()->viewData('schools'))->keyBy('id')[2];
        $members = SchoolMembership::query()->where('school_id', 2)->where('role', 'Student')->orderBy('id')->limit(3)->get();
        $this->assertCount(3, $members);
        $members[0]->update(['starts_on' => now('Africa/Johannesburg')->addDay()->toDateString()]);
        $members[1]->update(['ends_on' => now('Africa/Johannesburg')->subDay()->toDateString()]);
        User::query()->whereKey($members[2]->user_id)->update(['status' => 0]);
        $after = collect($this->get('/saas')->assertOk()->viewData('schools'))->keyBy('id')[2];
        $this->assertSame($before['roles']['Student'] - 3, $after['roles']['Student']);
        $this->assertSame($before['people'] - 3, $after['people']);
        School::query()->whereKey(2)->update(['status' => School::SUSPENDED]);
        $suspended = collect($this->get('/saas')->assertOk()->viewData('schools'))->keyBy('id')[2];
        $this->assertSame(0, $suspended['people']);
    }

    public function test_a_school_timezone_change_cannot_revive_an_expired_platform_grant(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-26 12:00:00', 'UTC'));
        $staff = $this->staff();
        PlatformAdminGrant::query()->where('user_id', $staff->id)->update(['expires_at' => now()->utc()->subMinute()]);
        $timezone = date_default_timezone_get();
        try {
            app(CurrentSchool::class)->run(1, fn () => Settings::query()->updateOrCreate(['type' => 'time_zone'], ['message' => 'Pacific/Honolulu']));
            SettingsConfigService::apply();
            config(['app.timezone' => 'Pacific/Honolulu']);
            date_default_timezone_set('Pacific/Honolulu');
            $this->assertFalse(app(PlatformStaffAccess::class)->allowed($staff));
            PlatformAdminGrant::query()->where('user_id', $staff->id)->update(['expires_at' => now()->utc()->addMinute()]);
            $this->assertTrue(app(PlatformStaffAccess::class)->allowed($staff));
            config(['app.timezone' => 'Pacific/Kiritimati']);
            date_default_timezone_set('Pacific/Kiritimati');
            $this->assertTrue(app(PlatformStaffAccess::class)->allowed($staff));
        } finally {
            config(['app.timezone' => $timezone]);
            date_default_timezone_set($timezone);
            $this->travelBack();
        }
    }

    // ---- setting a school up ----

    public function test_a_school_is_set_up_with_roles_and_its_first_administrator(): void
    {
        Queue::fake();
        $staff = $this->staff();
        $before = [1 => $this->moduleRows(1), 2 => $this->moduleRows(2), 'members' => SchoolMembership::query()->count()];

        $response = $this->actingAs($staff)->post('/saas/schools', $this->newSchool())->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertSee('Marula Primary');
        $url = $response->viewData('invitationUrl');
        $token = basename((string) parse_url($url, PHP_URL_PATH));

        $school = School::query()->where('slug', 'marula-primary')->firstOrFail();
        $this->assertSame('core', $school->package_key);
        $this->assertSame('active', $school->status);

        $admin = User::query()->where('email', 'lerato@marula.example')->firstOrFail();
        // Nobody knows the starting password: the administrator chooses one from the emailed link.
        $this->assertTrue($admin->password_change_required);
        $response->assertSee($url, false)->assertDontSee('One-time password');
        $invitation = StaffInvitation::acrossSchools()->where('user_id', $admin->id)->sole();
        $this->assertSame([(int) $school->id, StaffInvitation::SCHOOL_ADMIN], [(int) $invitation->school_id, $invitation->purpose]);
        $this->assertSame(hash('sha256', $token), $invitation->token_hash);
        // The test transaction never commits, so the after-commit send is replayed.
        app(StaffInvitations::class)->deliver($invitation);
        $mail = Queue::pushed(SendMail::class)->first(fn ($job) => $job->mailable instanceof StaffInvitationMail);
        $this->assertSame('lerato@marula.example', $mail->mailable->to[0]['address']);
        $this->assertTrue($mail->mailable->firstAdministrator);

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

        // Audited with the staff member, and the link's secret is nowhere in the log.
        $row = AuditLog::query()->where('action', 'platform_school_created')->latest('id')->firstOrFail();
        $this->assertSame($staff->id, $row->actor_id);
        $this->assertSame($school->id, $row->school_id);
        $this->assertSame($admin->id, $row->after['first_admin_user_id']);
        $this->assertStringNotContainsString($token, json_encode(AuditLog::query()->get()->toArray()));
        $this->assertStringNotContainsString($token, json_encode(DB::table('sessions')->get()));
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

    public function test_the_new_administrator_can_open_the_panel_home_and_sees_nothing_of_other_schools(): void
    {
        $this->actingAs($this->staff())->post('/saas/schools', $this->newSchool())->assertOk();
        $admin = User::query()->where('email', 'lerato@marula.example')->firstOrFail();

        auth()->forgetGuards();
        $this->actingAs($admin)->get('/home')->assertOk()->assertDontSee('Baobab')->assertDontSee('Jacaranda');

        // The school has its own current year and settings, and the staff member's session year was not touched.
        $school = School::query()->where('slug', 'marula-primary')->firstOrFail();
        $year = DB::table('session_years')->where('school_id', $school->id)->first();
        $this->assertSame(1, (int) $year->default);
        $this->assertDatabaseHas('settings', ['school_id' => $school->id, 'type' => 'session_year', 'message' => (string) $year->id]);
        $this->assertDatabaseHas('settings', ['school_id' => $school->id, 'type' => 'school_name', 'message' => 'Marula Primary']);
        $this->assertSame(1, DB::table('session_years')->where('school_id', 1)->where('default', 1)->count());
        $this->assertSame(1, DB::table('session_years')->where('default', 1)->where('school_id', 2)->count());
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

    public function test_a_downgrade_keeps_retained_fee_records_readable_but_not_writable(): void
    {
        $staff = $this->staff();
        $this->actingAs($staff);
        $this->put('/saas/schools/2/package', ['package_key' => 'standard'])->assertRedirect();
        $this->put('/saas/schools/2/modules/fees', ['enabled' => 1])->assertRedirect();
        $entry = $this->seedCanonicalFeeHistory();
        $this->assertGreaterThan(0, DB::table('fees_paids')->where('school_id', 2)->count());
        app(CurrentSchool::class)->run(2, fn () => $this->assertTrue(app(ModuleGate::class)->writable('fees')));

        $this->put('/saas/schools/2/package', ['package_key' => 'core'])->assertRedirect();

        app(CurrentSchool::class)->run(2, function () {
            $state = app(ModuleGate::class)->state('fees');
            $this->assertTrue($state['enabled']);
            $this->assertTrue($state['read_only']);
            $this->assertFalse($state['in_package']);
        });
        $this->app['auth']->forgetGuards();
        $this->withToken($this->schoolB->tokens['asha'])->getJson('/api/student/fees-paid-list')->assertOk()->assertJsonPath('code', 410)->assertJsonPath('data', null);
        $this->postJson('/api/student/store-fees', [])->assertOk()->assertJsonPath('code', 410);
        $this->assertDatabaseHas('ledger_entries', ['id' => $entry, 'school_id' => 2]);
        // Another school's fees were never touched.
        app(CurrentSchool::class)->run(1, fn () => $this->assertTrue(app(ModuleGate::class)->writable('fees')));
    }

    public function test_a_downgrade_leaves_a_school_without_fee_records_alone(): void
    {
        $this->actingAs($this->staff())->post('/saas/schools', $this->newSchool(['package_key' => 'standard']))->assertOk();
        $school = School::query()->where('slug', 'marula-primary')->firstOrFail();

        $this->put('/saas/schools/'.$school->id.'/package', ['package_key' => 'core'])->assertRedirect();

        $this->assertDatabaseMissing('school_modules', ['school_id' => $school->id, 'module' => 'fees', 'read_only' => true]);
    }

    // ---- signing in as an operator ----

    private function operatorWithoutRoles(string $email = 'operator@platform.example', bool $identity = true, bool $grant = true, bool $changePassword = false): User
    {
        $user = User::query()->create(['first_name' => 'Pat', 'last_name' => 'Operator', 'email' => $email, 'mobile' => '', 'password' => 'a-long-password-1', 'status' => 1]);
        $user->forceFill(['password_change_required' => $changePassword])->save();
        $identity && PlatformStaffIdentity::query()->create(['user_id' => $user->id]);
        $grant && PlatformAdminGrant::query()->create(['user_id' => $user->id, 'active' => true, 'expires_at' => now()->addHour()]);

        return $user;
    }

    public function test_an_operator_with_no_school_role_signs_in_and_lands_on_the_platform_area(): void
    {
        $operator = $this->operatorWithoutRoles();

        $this->post('/login', ['email' => $operator->email, 'password' => 'a-long-password-1'])->assertRedirect('/saas');
        $this->assertAuthenticatedAs($operator);
        $this->get('/saas')->assertOk()->assertSee('Baobab High');
        // Their account holds no school role or membership.
        $this->assertSame(0, SchoolMembership::query()->where('user_id', $operator->id)->count());
    }

    public function test_a_role_less_account_without_both_keys_is_still_refused_at_sign_in(): void
    {
        foreach ([
            $this->operatorWithoutRoles('identity@platform.example', identity: true, grant: false),
            $this->operatorWithoutRoles('grant@platform.example', identity: false, grant: true),
            $this->operatorWithoutRoles('neither@platform.example', identity: false, grant: false),
        ] as $user) {
            $this->post('/login', ['email' => $user->email, 'password' => 'a-long-password-1'])->assertRedirect('/login');
            $this->assertGuest();
        }
    }

    public function test_an_operator_who_must_change_a_password_is_kept_out_of_the_platform_area_until_they_do(): void
    {
        $operator = $this->operatorWithoutRoles(changePassword: true);

        $this->post('/login', ['email' => $operator->email, 'password' => 'a-long-password-1']);
        // The panel's forced-change rule sends them to the change form; /saas itself is closed to them either way.
        $this->get('/saas')->assertRedirect(route('saas.password'))->assertHeader('Cache-Control', 'no-store, private');
        $this->assertTrue(app(PlatformStaffAccess::class)->operatesPlatform($operator));
        $this->assertFalse(app(PlatformStaffAccess::class)->allowed($operator));
        $this->get('/saas/password')->assertOk()->assertHeader('Cache-Control', 'no-store, private');
        $this->post('/saas/password', ['old_password' => 'a-long-password-1', 'new_password' => 'short', 'confirm_password' => 'mismatch'], ['Accept' => 'text/html'])->assertUnprocessable()->assertSessionMissing('_old_input');
        $this->postJson('/saas/password', ['old_password' => 'a-long-password-1', 'new_password' => 'Synthetic-rotated-platform-password-674!', 'confirm_password' => 'Synthetic-rotated-platform-password-674!'])->assertOk()->assertJsonPath('error', false);
        $this->assertFalse($operator->fresh()->password_change_required);
        $this->get('/saas')->assertOk();
        $this->assertSame(0, SchoolMembership::query()->where('user_id', $operator->id)->count());
        $this->assertDatabaseMissing('model_has_roles', ['model_id' => $operator->id, 'model_type' => User::class]);
    }

    public function test_default_password_operator_must_replace_it_before_entering_saas(): void
    {
        $operator = $this->operatorWithoutRoles();
        $operator->password = 'superadmin';
        $operator->save();
        $this->post('/login', ['email' => $operator->email, 'password' => 'superadmin'])->assertRedirect('/saas');
        $this->assertTrue($operator->fresh()->password_change_required);
        $this->assertFalse(app(PlatformStaffAccess::class)->allowed($operator));
        $this->get('/saas')->assertRedirect(route('saas.password'))->assertHeader('Cache-Control', 'no-store, private');
        $this->get('/saas/password')->assertOk();
        $this->postJson('/saas/password', ['old_password' => 'superadmin', 'new_password' => 'Synthetic-rotated-platform-password-674!', 'confirm_password' => 'Synthetic-rotated-platform-password-674!'])->assertOk()->assertJsonPath('error', false);
        $this->get('/saas')->assertOk();
        $this->assertSame(0, SchoolMembership::query()->where('user_id', $operator->id)->count());
    }

    public function test_own_platform_password_cannot_bypass_fresh_identity_grant_or_impersonation(): void
    {
        $operator = $this->operatorWithoutRoles(changePassword: true);
        $this->actingAs($operator);
        foreach (['expired', 'identity_revoked', 'grant_revoked'] as $case) {
            PlatformStaffIdentity::query()->where('user_id', $operator->id)->update(['active' => $case !== 'identity_revoked']);
            PlatformAdminGrant::query()->where('user_id', $operator->id)->update(['active' => $case !== 'grant_revoked', 'expires_at' => $case === 'expired' ? now()->utc()->subMinute() : now()->utc()->addHour()]);
            $this->get('/saas/password')->assertForbidden()->assertHeader('Cache-Control', 'no-store, private');
            $this->postJson('/saas/password', ['old_password' => 'a-long-password-1', 'new_password' => 'Synthetic-rotated-platform-password-674!', 'confirm_password' => 'Synthetic-rotated-platform-password-674!'])->assertForbidden();
            $this->assertTrue($operator->fresh()->password_change_required);
        }
        PlatformStaffIdentity::query()->where('user_id', $operator->id)->update(['active' => true]);
        PlatformAdminGrant::query()->where('user_id', $operator->id)->update(['active' => true, 'expires_at' => now()->utc()->addHour()]);
        app(CurrentSchool::class)->impersonate(School::query()->findOrFail(1));
        try {
            $this->assertFalse(app(PlatformStaffAccess::class)->operatesPlatform($operator));
        } finally {
            app(CurrentSchool::class)->forget();
        }
        $this->assertSame(0, SchoolMembership::query()->where('user_id', $operator->id)->count());
    }

    public function test_no_year_home_guard_survives_removal_of_the_editable_onboarding_preset(): void
    {
        $response = $this->actingAs($this->staff())->post('/saas/schools', $this->newSchool())->assertOk();
        $admin = $response->viewData('admin');
        $school = $response->viewData('school');
        app(CurrentSchool::class)->run($school, function (): void {
            Settings::query()->where('type', 'session_year')->get()->each->delete();
            SessionYear::query()->update(['default' => 0]);
            SessionYear::query()->get()->each->delete();
        });
        $admin->forceFill(['password_change_required' => false])->save();
        auth()->forgetGuards();
        $this->actingAs($admin)->get('/home')->assertOk();
        foreach (['attendance', 'assessment', 'leaves', 'events'] as $widget) {
            $this->getJson('/home/widget/'.$widget)->assertOk();
        }
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
