<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Exceptions\TooManyLoginFailures;
use App\Http\Middleware\EnforcePasswordChange;
use App\Models\Platform\Role;
use App\Models\Platform\StaffInvitation;
use App\Models\User;
use App\Services\Platform\Identity\AppTokens;
use App\Services\Platform\Identity\DateOfBirthPasswords;
use App\Services\Platform\Identity\StaffInvitations;
use Database\Seeders\AddSuperAdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;
use Tests\Concerns\ValidatesOpenApiResponses;
use Tests\Support\SignInFixtures;
use Tests\TestCase;

class AuthenticationBoundaryTest extends TestCase
{
    use RefreshDatabase;
    use SignInFixtures;
    use ValidatesOpenApiResponses;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedLikeInstaller();
        $this->buildSignInSchool();
    }

    public function test_failed_app_logins_do_not_lock_every_other_account(): void
    {
        $teacher = $this->person('teacher');
        for ($i = 0; $i < TooManyLoginFailures::MAX_FAILURES; $i++) {
            $this->assertFalse(Auth::attempt(['id' => $teacher->id, 'password' => 'wrong']));
        }

        $this->assertTrue(Auth::attempt(['id' => $this->person('parent')->id, 'password' => 'secret123']));
        Auth::logout();
        $this->expectException(TooManyLoginFailures::class);
        Auth::attempt(['email' => strtoupper($teacher->email), 'password' => 'secret123']);
    }

    public function test_successful_app_login_clears_that_accounts_failed_attempts(): void
    {
        $teacher = $this->person('teacher');
        for ($i = 0; $i < TooManyLoginFailures::MAX_FAILURES - 1; $i++) {
            $this->assertFalse(Auth::attempt(['id' => $teacher->id, 'password' => 'wrong']));
        }
        $this->assertTrue(Auth::attempt(['id' => $teacher->id, 'password' => 'secret123']));
        Auth::logout();
        $this->assertFalse(Auth::attempt(['id' => $teacher->id, 'password' => 'wrong']));
        $this->assertTrue(Auth::attempt(['id' => $teacher->id, 'password' => 'secret123']));
    }

    public function test_login_aliases_share_the_same_per_minute_limit(): void
    {
        $teacher = $this->person('teacher');
        $teacher->forceFill(['handle' => 'tom.fixture'])->save();
        $key = fn (string $login) => RateLimiter::limiter('login')(Request::create('/api/teacher/login', 'POST', ['email' => $login]))[0]->key;

        $this->assertSame($key('tom@example.com'), $key('TOM.FIXTURE'));
        $this->assertNotSame($key('tom@example.com'), $key('pam@example.com'));
    }

    public function test_disabled_parent_cannot_sign_in_or_receive_a_token(): void
    {
        $parent = $this->person('parent');
        $parent->forceFill(['status' => 0])->save();

        $this->postJson('/api/parent/login', ['email' => 'pam@example.com', 'password' => 'secret123'])
            ->assertJson(['error' => true])->assertJsonMissingPath('token');
        $this->assertSame(0, $parent->tokens()->count());
    }

    public function test_disabled_accounts_cannot_reuse_tokens_on_shared_v2_routes(): void
    {
        foreach (['parent', 'teacher', 'learner'] as $profile) {
            $user = $this->person($profile);
            $token = $user->createToken('test')->plainTextToken;
            $user->forceFill(['status' => 0])->save();
            $this->app['auth']->forgetGuards();
            $this->withToken($token)->getJson('/api/v2/me/context')->assertUnauthorized()->assertJsonPath('code', 'unauthenticated');
        }
    }

    public function test_disabled_school_administrator_cannot_sign_in(): void
    {
        $this->superAdmin()->forceFill(['status' => 0, 'password' => Hash::make('Admin-password-26')])->save();
        $this->post('/login', ['email' => 'superadmin@gmail.com', 'password' => 'Admin-password-26']);
        $this->assertGuest();
    }

    public function test_disabling_an_account_ends_its_existing_panel_session(): void
    {
        $admin = $this->superAdmin();
        $admin->forceFill(['password' => Hash::make('Admin-password-26')])->save();
        $this->actingAs($admin);
        $admin->forceFill(['status' => 0])->save();
        $this->get('/home')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_required_password_change_cannot_be_bypassed_by_a_header_or_v2_route(): void
    {
        $user = $this->person('learner');
        $user->forceFill(['password_change_required' => true])->save();
        $token = $user->createToken(AppTokens::STUDENT)->plainTextToken;

        foreach (['/api/student/get-profile-data', '/api/v2/me/profile'] as $path) {
            foreach ([null, '0', '1'] as $header) {
                $this->app['auth']->forgetGuards();
                $this->flushHeaders();
                if ($header !== null) {
                    $this->withHeader(EnforcePasswordChange::HEADER, $header);
                }
                $this->withToken($token)->getJson($path)->assertForbidden()
                    ->assertJsonPath('code', str_starts_with($path, '/api/v2/') ? 'forbidden' : EnforcePasswordChange::CODE);
            }
        }
    }

    public function test_flagging_an_existing_panel_session_requires_password_change(): void
    {
        $teacher = $this->person('teacher');
        $this->actingAs($teacher);
        $teacher->forceFill(['password_change_required' => true])->save();
        $this->get('/home')->assertRedirect('/resetpassword');
    }

    public function test_required_password_change_preserves_vendor_portal_sign_out(): void
    {
        $user = $this->person('teacher');
        $user->syncRoles('Vendor');
        $user->forceFill(['password_change_required' => true])->save();
        $this->actingAs($user)->withSession(['portal_app' => AppTokens::VENDOR])->post('/vendor-counter/sign-out', [
            'expected_account_id' => $user->id, 'expected_school_id' => 1,
        ])->assertRedirect('/vendor-counter/sign-in');
        $this->assertGuest();
    }

    public function test_panel_login_with_a_default_password_also_restricts_the_api(): void
    {
        $teacher = $this->person('teacher');
        $teacher->forceFill(['password' => Hash::make('12041990')])->save();
        $this->post('/login', ['email' => $teacher->email, 'password' => '12041990']);

        $this->assertTrue($teacher->fresh()->password_change_required);
        $this->getJson('/api/v2/me/profile')->assertForbidden()->assertJsonPath('code', 'forbidden');
    }

    public function test_reusing_a_temporary_password_does_not_clear_the_required_change(): void
    {
        $teacher = $this->person('teacher');
        $teacher->forceFill(['password' => Hash::make('Temporary-password-26'), 'password_change_required' => true])->save();
        $token = $teacher->createToken(AppTokens::TEACHER)->plainTextToken;
        $this->withToken($token)->postJson('/api/change-password', [
            'current_password' => 'Temporary-password-26', 'new_password' => 'Temporary-password-26',
            'new_confirm_password' => 'Temporary-password-26',
        ])->assertJsonPath('error', true);
        $this->assertTrue($teacher->fresh()->password_change_required);
    }

    public function test_new_teachers_and_staff_choose_passwords_from_single_use_invitations(): void
    {
        Queue::fake();
        $role = Role::create(['name' => 'Office staff', 'guard_name' => 'web', 'custom_role' => 1]);
        foreach (['teachers' => [], 'staff' => ['role_id' => $role->id]] as $path => $extra) {
            $email = $path.'-new@example.com';
            $this->actingAs($this->superAdmin())->postJson('/'.$path, $extra + [
                'first_name' => 'New', 'last_name' => 'Colleague', 'email' => $email, 'gender' => 'Female',
                'mobile' => '0821234567', 'dob' => '1990-04-12', 'qualification' => 'B.Ed',
                'current_address' => '1 Road', 'permanent_address' => '1 Road',
            ])->assertOk()->assertJsonPath('error', false);
            $user = User::where('email', $email)->sole();
            $this->assertFalse(DateOfBirthPasswords::stillInUse($user));
            $this->assertTrue($user->password_change_required);
            $invitation = StaffInvitation::where('user_id', $user->id)->sole();
            $this->assertNull($invitation->used_at);

            $this->app['auth']->forgetGuards();
            $this->flushSession();
            $url = app(StaffInvitations::class)->url($invitation->encrypted_token);
            $this->post($url, ['password' => 'Chosen-staff-password-26', 'password_confirmation' => 'Chosen-staff-password-26'])
                ->assertRedirect('/login');
            $this->assertTrue(Hash::check('Chosen-staff-password-26', $user->fresh()->password));
            $this->assertFalse($user->fresh()->password_change_required);
            $this->get($url)->assertNotFound();
        }
    }

    public function test_reseeding_never_restores_a_known_administrator_password(): void
    {
        $admin = $this->superAdmin();
        $admin->forceFill(['email' => 'chosen-admin@example.com', 'password' => Hash::make('Chosen-admin-password-26')])->save();

        $this->seed(AddSuperAdminSeeder::class);

        $this->assertSame('chosen-admin@example.com', $admin->fresh()->email);
        $this->assertTrue(Hash::check('Chosen-admin-password-26', $admin->fresh()->password));
    }

    public function test_password_validation_never_flashes_old_or_new_credentials(): void
    {
        $this->actingAs($this->person('teacher'))->from('/resetpassword')->post('/changePassword', [
            'old_password' => 'secret123', 'new_password' => 'short', 'confirm_password' => 'short',
        ])->assertRedirect('/resetpassword')->assertSessionHasErrors('new_password');

        foreach (['old_password', 'new_password', 'confirm_password'] as $field) {
            $this->assertArrayNotHasKey($field, session()->getOldInput());
        }
    }

    public function test_reset_delivery_failure_does_not_reveal_a_registered_email(): void
    {
        Notification::shouldReceive('send')->once()->andThrow(new \RuntimeException('Synthetic mail failure'));
        $known = $this->postJson('/password/email', ['email' => 'tom@example.com'])->assertOk();
        $unknown = $this->postJson('/password/email', ['email' => 'unknown@example.com'])->assertOk();

        $this->assertSame($known->json(), $unknown->json());
    }
}
