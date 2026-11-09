<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Models\Platform\PlatformAdminGrant;
use App\Models\Platform\PlatformStaffIdentity;
use App\Models\User;
use App\Services\Platform\Identity\MembershipService;
use App\Services\Platform\Saas\PlatformSignIn;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\Concerns\PassesPlatformTwoFactor;
use Tests\Concerns\UsesTwoSchools;
use Tests\TestCase;

class BusinessSignInTest extends TestCase
{
    use PassesPlatformTwoFactor;
    use UsesTwoSchools;

    private const PASSWORD = 'Business-only-synthetic-password-841!';

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        app(CurrentSchool::class)->forget();
    }

    private function owner(): User
    {
        $user = User::query()->create(['first_name' => 'Business', 'last_name' => 'Owner', 'email' => 'owner@business.example', 'mobile' => '', 'password' => self::PASSWORD, 'status' => 1]);
        PlatformStaffIdentity::query()->create(['user_id' => $user->id, 'active' => true]);
        PlatformAdminGrant::query()->create(['user_id' => $user->id, 'active' => true, 'role' => 'owner', 'expires_at' => now()->addDay()]);

        return $user;
    }

    public function test_business_guests_get_a_distinct_private_login_and_recovery_page(): void
    {
        $this->get('/saas/home')->assertRedirect('/saas/login');
        $this->get('/saas/login')->assertOk()->assertSee('Business administration sign in')
            ->assertSee('action="'.route('saas.login.store').'"', false)
            ->assertDontSee('action="'.route('login').'"', false)
            ->assertHeader('Referrer-Policy', 'no-referrer');
        $this->get('/saas/password/reset')->assertOk()->assertSee(route('saas.password.email'));
        $this->get('/login')->assertOk()->assertDontSee('Business administration sign in');
    }

    public function test_business_credentials_are_refused_by_the_school_login_and_require_business_mfa(): void
    {
        $owner = $this->owner();
        $credentials = ['email' => $owner->email, 'password' => self::PASSWORD];
        $this->from('/login')->post('/login', $credentials)->assertRedirect('/login')->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->withSession(['url.intended' => '/home', 'school_id' => 2]);
        $this->keepSession($this->post('/saas/login', $credentials))->assertRedirect('/saas/home');
        $this->assertAuthenticatedAs($owner);
        $this->assertSame($owner->id, session(PlatformSignIn::SESSION_KEY));
        $this->get('/saas/home')->assertRedirect(route('saas.two-factor.enrol'));
        $this->keepSession($this->post('/saas/logout'))->assertRedirect('/saas/login');
        $this->assertGuest();
        $this->get('/saas/home')->assertRedirect('/saas/login');
    }

    public function test_school_administrators_from_either_school_cannot_use_business_sign_in(): void
    {
        foreach ([1, 2] as $school) {
            $admin = User::query()->create(['first_name' => 'School', 'last_name' => 'Admin', 'email' => "school-$school@business.example", 'mobile' => '', 'password' => self::PASSWORD, 'status' => 1]);
            app(MembershipService::class)->grant($admin, $school, 'Super Admin');
            app(CurrentSchool::class)->run($school, fn () => $admin->assignRole('Super Admin'));
            $this->keepSession($this->post('/login', ['email' => $admin->email, 'password' => self::PASSWORD]))->assertRedirect('/home');
            $this->assertAuthenticatedAs($admin);
            $this->from('/saas/login')->post('/saas/login', ['email' => $admin->email, 'password' => self::PASSWORD])
                ->assertRedirect('/saas/login')->assertSessionHasErrors('email');
            $this->assertGuest();
            $this->newBrowserSession();
        }
    }

    public function test_business_sign_in_replaces_a_school_session_and_drops_its_state(): void
    {
        $owner = $this->owner();
        $schoolAdmin = $this->superAdmin();
        $this->actingAs($schoolAdmin)->withSession(['school_id' => 2, 'url.intended' => '/home', 'school_only_state' => 'old']);
        $this->keepSession($this->post('/saas/login', ['email' => $owner->email, 'password' => self::PASSWORD]))->assertRedirect('/saas/home');
        $this->assertAuthenticatedAs($owner);
        $this->assertNull(session('school_only_state'));
        $this->assertNull(session('school_id'));
        $this->assertSame($owner->id, session(PlatformSignIn::SESSION_KEY));
        $this->get('/login')->assertRedirect('/saas/home');
    }

    public function test_stale_school_or_other_account_session_proof_cannot_open_hq(): void
    {
        $owner = $this->owner();
        $this->actingAs($owner)->get('/saas/home')->assertRedirect('/saas/login');
        $this->withSession([PlatformSignIn::SESSION_KEY => $owner->id + 1]);
        $this->get('/saas/two-factor')->assertRedirect('/saas/login');
        $this->getJson('/saas/home')->assertUnauthorized();
    }

    public function test_revoked_expired_and_inactive_business_accounts_cannot_sign_in(): void
    {
        $owner = $this->owner();
        $grant = PlatformAdminGrant::where('user_id', $owner->id)->firstOrFail();
        foreach (['expired', 'revoked', 'inactive', 'unknown-role', 'inactive-identity'] as $state) {
            $owner->forceFill(['status' => $state === 'inactive' ? 0 : 1])->save();
            PlatformStaffIdentity::where('user_id', $owner->id)->update(['active' => $state !== 'inactive-identity']);
            $grant->forceFill(['active' => $state !== 'revoked', 'expires_at' => $state === 'expired' ? now()->subHour() : now()->addDay(), 'role' => $state === 'unknown-role' ? 'unknown' : 'owner'])->save();
            $this->from('/saas/login')->post('/saas/login', ['email' => $owner->email, 'password' => self::PASSWORD])->assertSessionHasErrors('email');
            $this->assertGuest();
        }
    }

    public function test_business_recovery_uses_its_own_url_and_school_recovery_cannot_reset_it(): void
    {
        Notification::fake();
        $owner = $this->owner();
        $this->from('/password/reset')->post('/password/email', ['email' => $owner->email])->assertSessionHas('status');
        Notification::assertNothingSent();
        $this->from('/saas/password/reset')->post('/saas/password/email', ['email' => $owner->email])->assertSessionHas('status');
        Notification::assertSentTo($owner, ResetPassword::class, fn ($notification) => str_contains($notification->toMail($owner)->actionUrl, '/saas/password/reset/'));
        $token = Password::broker()->createToken($owner);
        $new = 'Replacement-business-password-526!';
        $payload = ['token' => $token, 'email' => $owner->email, 'password' => $new, 'password_confirmation' => $new];
        $this->from('/password/reset')->post('/password/reset', $payload)->assertSessionHasErrors('email');
        $this->assertTrue(Hash::check(self::PASSWORD, $owner->fresh()->password));
        $this->post('/saas/password/reset', $payload)->assertRedirect('/saas/login');
        $this->assertTrue(Hash::check($new, $owner->fresh()->password));
        $this->assertGuest();
        $this->get('/saas/home')->assertRedirect('/saas/login');
    }

    public function test_business_recovery_cannot_send_or_consume_school_reset_tokens(): void
    {
        Notification::fake();
        $admin = $this->superAdmin();
        $before = $admin->password;
        $this->from('/saas/password/reset')->post('/saas/password/email', ['email' => $admin->email])->assertSessionHas('status');
        Notification::assertNothingSent();
        $token = Password::broker()->createToken($admin);
        $new = 'Replacement-school-password-526!';
        $this->from('/saas/password/reset')->post('/saas/password/reset', ['token' => $token, 'email' => $admin->email, 'password' => $new, 'password_confirmation' => $new])->assertSessionHasErrors('email');
        $this->assertSame($before, $admin->fresh()->password);
        $this->assertTrue(Password::broker()->tokenExists($admin, $token));
        $this->assertGuest();
    }
}
