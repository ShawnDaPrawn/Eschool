<?php

namespace Tests\Feature;

use App\Exceptions\TooManyLoginFailures;
use App\Models\Staff;
use App\Models\User;
use dacoto\LaravelWizardInstaller\Middleware\ToInstallMiddleware;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Sign-in, passwords and sessions.
 */
class AccountSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedLikeInstaller();
    }

    public function test_default_passwords_must_be_changed_before_using_the_panel(): void
    {
        $this->post('/login', ['email' => 'superadmin@gmail.com', 'password' => 'superadmin']);
        $this->assertAuthenticated();

        $this->get('/home')->assertRedirect('resetpassword');
        $this->getJson('/home/widget/events')->assertForbidden();
        $this->get('/resetpassword')->assertOk();

        $this->postJson('/changePassword', [
            'old_password' => 'superadmin',
            'new_password' => 'superadmin',
            'confirm_password' => 'superadmin',
        ])->assertJson(['error' => true]);

        $this->postJson('/changePassword', [
            'old_password' => 'superadmin',
            'new_password' => 'a-better-password',
            'confirm_password' => 'a-better-password',
        ])->assertJson(['error' => false]);

        // Still signed in (the session was updated with the new password), and free to go.
        $response = $this->get('/home');
        $this->assertAuthenticated();
        $this->assertNotContains($response->headers->get('Location'), [url('resetpassword'), url('login')]);
    }

    public function test_a_teacher_signing_in_with_their_date_of_birth_must_change_it(): void
    {
        $teacher = $this->teacher('17051990', '1990-05-17');

        $this->post('/login', ['email' => $teacher->email, 'password' => '17051990']);
        $this->get('/home')->assertRedirect('resetpassword');

        Auth::logout();
        $this->flushSession();
        $teacher->update(['password' => Hash::make('not-the-default')]);
        $this->post('/login', ['email' => $teacher->email, 'password' => 'not-the-default']);
        $this->assertNotSame(url('resetpassword'), $this->get('/home')->headers->get('Location'));
    }

    public function test_changing_the_password_signs_out_other_sessions_and_the_apps(): void
    {
        $teacher = $this->teacher('secret123');
        $teacher->createToken('teacher-app');

        // A session that signed in before the password changed elsewhere.
        $this->actingAs($teacher)->withSession(['password_hash_web' => Hash::make('old')])->get('/home')->assertRedirect('login');

        $this->actingAs($teacher)->postJson('/changePassword', [
            'old_password' => 'secret123',
            'new_password' => 'brand-new-pass',
            'confirm_password' => 'brand-new-pass',
        ])->assertJson(['error' => false]);
        $this->assertSame(0, $teacher->tokens()->count());
    }

    public function test_a_password_reset_signs_out_of_the_apps(): void
    {
        $teacher = $this->teacher('secret123');
        $teacher->createToken('teacher-app');

        event(new PasswordReset($teacher));

        $this->assertSame(0, $teacher->tokens()->count());
    }

    public function test_wrong_passwords_are_counted_per_account_across_logins(): void
    {
        $teacher = $this->teacher('secret123');

        for ($i = 0; $i < TooManyLoginFailures::MAX_FAILURES - 1; $i++) {
            $this->assertFalse(Auth::attempt(['email' => $teacher->email, 'password' => 'guess'.$i]));
        }
        // The right password clears the count.
        $this->assertTrue(Auth::attempt(['email' => $teacher->email, 'password' => 'secret123']));
        Auth::logout();

        for ($i = 0; $i < TooManyLoginFailures::MAX_FAILURES; $i++) {
            // Any capitalisation is the same account.
            $this->assertFalse(Auth::attempt(['email' => strtoupper($teacher->email), 'password' => 'guess'.$i]));
        }

        try {
            Auth::attempt(['email' => $teacher->email, 'password' => 'secret123']);
            $this->fail('Sign-in was not refused');
        } catch (TooManyLoginFailures $e) {
            $this->assertGreaterThan(3000, $e->seconds);
            // The apps get a message they show; the panel gets a form error.
            $this->assertTrue($e->render(Request::create('/api/teacher/login', 'POST'))->getData(true)['error']);
        }
        $this->assertGuest();

        $this->from('/login')->post('/login', ['email' => $teacher->email, 'password' => 'secret123'])
            ->assertRedirect('/login')->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_panel_and_app_logins_share_one_limit_per_account(): void
    {
        $limit = fn (string $uri, string $email) => RateLimiter::limiter('login')(Request::create($uri, 'POST', ['email' => $email]))[0]->key;

        $this->assertSame($limit('/login', 'Parent@Example.com'), $limit('/api/parent/login', 'parent@example.com'));
        $this->assertContains('throttle:login', Route::getRoutes()->match(Request::create('/login', 'POST'))->gatherMiddleware());
    }

    public function test_the_current_password_is_checked_with_a_limited_post(): void
    {
        $teacher = $this->teacher('secret123');

        $this->actingAs($teacher)->get('/checkPassword?old_password=secret123')->assertStatus(405);
        $this->actingAs($teacher)->postJson('/checkPassword', ['old_password' => 'secret123'])->assertExactJson([1]);
        $this->actingAs($teacher)->postJson('/checkPassword', ['old_password' => 'nope'])->assertExactJson([0]);

        foreach (['checkPassword', 'changePassword'] as $uri) {
            $this->assertContains('throttle:password', Route::getRoutes()->match(Request::create('/'.$uri, 'POST'))->gatherMiddleware());
        }
    }

    public function test_signing_out_needs_a_form_post(): void
    {
        $this->actingAs($this->teacher('secret123'))->get('/logout')->assertStatus(405);
        $this->assertAuthenticated();

        $this->post('/logout')->assertRedirect('/');
        $this->assertGuest();
    }

    public function test_reset_links_point_at_app_url_whatever_the_request_host(): void
    {
        config(['app.url' => 'https://school.example']);
        $this->app->instance('request', Request::create('https://evil.example/password/email', 'POST'));

        $mail = (new ResetPassword('the-token'))->toMail($this->teacher('secret123'));

        $this->assertStringStartsWith('https://school.example/password/reset/the-token?email=', $mail->actionUrl);
    }

    public function test_forwarded_host_and_prefix_headers_are_ignored(): void
    {
        config(['trustedproxy.proxies' => '127.0.0.1']);
        Route::middleware('web')->get('/_url', fn (Request $request) => $request->getSchemeAndHttpHost().'|'.url('/x').'|'.$request->ip());

        $this->withHeaders([
            'X-Forwarded-For' => '203.0.113.9',
            'X-Forwarded-Proto' => 'https',
            'X-Forwarded-Host' => 'evil.example',
            'X-Forwarded-Prefix' => '/evil',
        ])->get('http://localhost/_url')->assertSee('https://localhost|https://localhost/x|203.0.113.9');
    }

    public function test_forgot_password_answers_the_same_for_unknown_addresses(): void
    {
        Notification::fake();
        $teacher = $this->teacher('secret123');

        $known = $this->from('/password/reset')->post('/password/email', ['email' => $teacher->email]);
        $unknown = $this->from('/password/reset')->post('/password/email', ['email' => 'nobody@example.com']);

        $known->assertSessionHasNoErrors();
        $unknown->assertSessionHasNoErrors();
        $this->assertSame($known->getSession()->get('status'), $unknown->getSession()->get('status'));
        Notification::assertSentTo($teacher, ResetPassword::class);
    }

    public function test_the_installer_stays_closed_when_the_database_has_users(): void
    {
        // storage/installed doesn't exist in tests; the users from setUp do.
        $this->assertFileDoesNotExist(storage_path('installed'));
        $this->get('/install')->assertNotFound();
        $this->get('/install/database')->assertNotFound();
        $this->assertTrue((new ToInstallMiddleware)->alreadyInstalled());
    }

    public function test_an_installation_started_on_an_empty_database_can_finish_its_steps(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('users')->delete();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $this->get('/install')->assertOk();
        // The wizard's migration step creates the admin before the last steps.
        $this->teacher('secret123');
        $this->get('/install/server')->assertOk();

        // Someone else, without that session, is refused.
        $this->flushSession();
        $this->get('/install/server')->assertNotFound();
    }

    private function teacher(string $password, ?string $dob = null): User
    {
        $teacher = User::create([
            'first_name' => 'Test',
            'last_name' => 'Teacher',
            'email' => 'teacher.test@example.com',
            'password' => Hash::make($password),
            'gender' => 'Male',
            'mobile' => '0000000000',
            'dob' => $dob,
        ]);
        $teacher->assignRole('Teacher');
        Staff::forceCreate(['user_id' => $teacher->id]);

        return $teacher;
    }
}
