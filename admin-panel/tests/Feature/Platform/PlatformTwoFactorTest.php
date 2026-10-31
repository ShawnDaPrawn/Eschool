<?php

declare(strict_types=1);

namespace Tests\Feature\Platform;

use App\Models\Platform\AuditLog;
use App\Models\Platform\PlatformAdminGrant;
use App\Models\Platform\PlatformStaffIdentity;
use App\Models\Platform\PlatformStaffTwoFactor;
use App\Models\User;
use App\Services\Platform\Saas\PlatformTwoFactor;
use App\Services\Platform\Saas\Totp;
use App\Services\Platform\Tenancy\CurrentSchool;
use Carbon\CarbonImmutable;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\Concerns\PassesPlatformTwoFactor;
use Tests\Concerns\UsesTwoSchools;
use Tests\TestCase;

/**
 * Mandatory two-factor sign-in for platform staff (Action Plan SA.2): an
 * authenticator app set up before any /saas page opens, a challenge in each
 * new session, single-use recovery codes, Owner and console resets, and
 * school accounts untouched. engineering/specs/platform/hq-two-factor.md.
 */
class PlatformTwoFactorTest extends TestCase
{
    use PassesPlatformTwoFactor;
    use UsesTwoSchools;

    private const PASSWORD = 'Synthetic-two-factor-password-815!';

    /** Every new page and form of this step. */
    private const ROUTES = [
        ['get', '/saas/two-factor'],
        ['post', '/saas/two-factor'],
        ['get', '/saas/two-factor/challenge'],
        ['post', '/saas/two-factor/challenge'],
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTwoSchools();
        app(CurrentSchool::class)->forget();
        // A fixed clock, so time steps are known: 08:00:00 is the start of a step.
        $this->travelTo(CarbonImmutable::parse('2026-11-02 08:00:00', 'UTC'));
    }

    private function staff(string $role, string $email = ''): User
    {
        $email = $email !== '' ? $email : $role.'.2fa@platform.example';
        $user = app(CurrentSchool::class)->acrossSchools(fn () => User::query()->create(['first_name' => ucfirst($role), 'last_name' => 'Operator', 'email' => $email, 'mobile' => '', 'password' => self::PASSWORD, 'status' => 1]));
        PlatformStaffIdentity::query()->create(['user_id' => $user->id]);
        PlatformAdminGrant::query()->create(['user_id' => $user->id, 'active' => true, 'role' => $role, 'expires_at' => now()->addDay()]);

        return $user;
    }

    /** A staff member whose app is already set up with a known secret. */
    private function enrolled(string $role, string $email = ''): array
    {
        $user = $this->staff($role, $email);
        $secret = Totp::generateSecret();
        PlatformStaffTwoFactor::query()->create(['user_id' => $user->id, 'secret' => $secret, 'confirmed_at' => now(), 'recovery_codes' => []]);

        return [$user, $secret];
    }

    private function signIn(User $user): void
    {
        auth()->forgetGuards();
        $this->keepSession($this->post('/login', ['email' => $user->email, 'password' => self::PASSWORD]))->assertRedirect('/saas');
    }

    private function signOut(): void
    {
        $this->keepSession($this->post('/logout'));
        auth()->forgetGuards();
    }

    private function codeNow(string $secret, int $offset = 0): string
    {
        return Totp::code($secret, Totp::step(now()->getTimestamp()) + $offset);
    }

    /** Six digits that are no code of this secret near now. */
    private function wrongCode(string $secret): string
    {
        $near = [$this->codeNow($secret, -1), $this->codeNow($secret), $this->codeNow($secret, 1)];
        $n = 0;
        while (in_array(sprintf('%06d', $n), $near, true)) {
            $n++;
        }

        return sprintf('%06d', $n);
    }

    private function audits(string $action, User $user): int
    {
        return AuditLog::query()->where('action', $action)->where('after->user_id', $user->id)->count();
    }

    /** @return array{0: string, 1: list<string>} the secret and the recovery codes, through the pages */
    private function enrolThroughPages(User $user): array
    {
        $this->signIn($user);
        $secret = (string) $this->get('/saas/two-factor')->assertOk()->viewData('secret');
        $codes = $this->keepSession($this->post('/saas/two-factor', ['current_password' => self::PASSWORD, 'two_factor_code' => Totp::code($secret, Totp::step(now()->getTimestamp()))]))->assertOk()->viewData('codes');

        return [$secret, $codes];
    }

    public function test_a_staff_member_without_two_factor_sets_it_up_before_any_hq_page_and_sees_recovery_codes_once(): void
    {
        $owner = $this->staff('owner');
        $this->signIn($owner);

        // Every HQ page and form waits for the setup; nothing is written.
        $this->get('/saas')->assertRedirect(route('saas.two-factor.enrol'));
        $this->get('/saas/schools/2')->assertRedirect(route('saas.two-factor.enrol'));
        $this->getJson('/saas/audit')->assertForbidden();
        $staffCount = PlatformStaffIdentity::query()->count();
        $this->post('/saas/staff', ['first_name' => 'Never', 'last_name' => 'Invited', 'email' => 'never@platform.example', 'role' => 'support', 'days' => 30, 'reason' => 'Should never be invited'])
            ->assertRedirect(route('saas.two-factor.enrol'));
        $this->assertSame($staffCount, PlatformStaffIdentity::query()->count());
        $this->get('/saas/two-factor/challenge')->assertRedirect(route('saas.two-factor.enrol'));

        $page = $this->get('/saas/two-factor')->assertOk()->assertSee('Set up two-factor sign-in')->assertSee('<svg', false)->assertDontSee('Platform staff</a>', false);
        $secret = (string) $page->viewData('secret');
        $this->assertMatchesRegularExpression('/^[A-Z2-7]{32}$/', $secret);
        $this->assertStringContainsString('otpauth://totp/NavigateEd%20HQ:'.rawurlencode($owner->email).'?secret='.$secret, (string) $page->viewData('uri'));
        // Opening the page again keeps the same secret, so a scan is not lost.
        $this->assertSame($secret, $this->get('/saas/two-factor')->viewData('secret'));
        $this->assertNull(PlatformStaffTwoFactor::query()->where('user_id', $owner->id)->value('confirmed_at'));

        $this->post('/saas/two-factor', ['current_password' => self::PASSWORD, 'two_factor_code' => $this->wrongCode($secret)])->assertSessionHasErrors('two_factor_code')->assertSessionMissing('_old_input.two_factor_code');
        $this->assertNull(PlatformStaffTwoFactor::query()->where('user_id', $owner->id)->value('confirmed_at'));
        $this->assertSame(1, $this->audits('platform_two_factor_failed', $owner));

        // Setting it up always asks for the password again: a session alone cannot attach an app.
        $this->post('/saas/two-factor', ['current_password' => 'Not-the-password-1!', 'two_factor_code' => $this->codeNow($secret)])
            ->assertSessionHasErrors('current_password')->assertSessionMissing('_old_input.current_password');
        $this->post('/saas/two-factor', ['two_factor_code' => $this->codeNow($secret)])->assertSessionHasErrors('current_password');
        $this->assertNull(PlatformStaffTwoFactor::query()->where('user_id', $owner->id)->value('confirmed_at'));
        $this->assertSame(1, AuditLog::query()->where('action', 'platform_two_factor_failed')->where('after->during', 'enrolment_password')->count());
        $done = $this->keepSession($this->post('/saas/two-factor', ['current_password' => self::PASSWORD, 'two_factor_code' => $this->codeNow($secret)]))->assertOk()->assertSee('Two-factor sign-in is on');
        $codes = $done->viewData('codes');
        $this->assertCount(10, $codes);
        $this->assertCount(10, array_unique($codes));
        foreach ($codes as $code) {
            $this->assertMatchesRegularExpression('/^[A-Z2-7]{4}(-[A-Z2-7]{4}){3}$/', $code);
            $done->assertSee($code);
        }
        $row = PlatformStaffTwoFactor::query()->where('user_id', $owner->id)->firstOrFail();
        $this->assertNotNull($row->confirmed_at);
        $this->assertSame(Totp::step(now()->getTimestamp()), $row->last_used_step);
        // Only keyed hashes are kept, never the codes.
        $stored = (string) DB::table('platform_staff_two_factor')->where('user_id', $owner->id)->value('recovery_codes');
        $this->assertCount(10, (array) json_decode($stored, true));
        foreach ($codes as $code) {
            $this->assertStringNotContainsString($code, $stored);
            $this->assertStringNotContainsString(str_replace('-', '', $code), $stored);
        }
        $this->assertSame(1, $this->audits('platform_two_factor_enrolled', $owner));

        // This session has passed: HQ opens, and the setup page does not show again.
        $this->get('/saas')->assertOk()->assertSee('Platform staff');
        $this->get('/saas/two-factor')->assertRedirect(route('saas.two-factor.challenge'));
        $this->get('/saas/two-factor/challenge')->assertRedirect(route('saas.index'));
    }

    public function test_each_new_session_passes_the_challenge_before_any_hq_page(): void
    {
        [$support, $secret] = $this->enrolled('support');
        $this->signIn($support);

        $this->get('/saas')->assertRedirect(route('saas.two-factor.challenge'));
        $this->get('/saas/audit?action=platform_two_factor_passed')->assertRedirect(route('saas.two-factor.challenge'));
        $this->get('/saas/two-factor')->assertRedirect(route('saas.two-factor.challenge'));
        $this->get('/saas/two-factor/challenge')->assertOk()->assertSee('Code from your app, or a recovery code')->assertDontSee('Audit log</a>', false);

        // After the challenge, the page they asked for.
        $this->keepSession($this->post('/saas/two-factor/challenge', ['two_factor_code' => $this->codeNow($secret)]))
            ->assertRedirect(url('/saas/audit?action=platform_two_factor_passed'));
        $this->get('/saas/audit?action=platform_two_factor_passed')->assertOk()->assertSee('Two-factor challenge passed');
        $this->assertSame(1, $this->audits('platform_two_factor_passed', $support));

        // The passed state belongs to this session id: another session holding the same data is challenged.
        $this->withCookie((string) config('session.cookie'), Str::random(40));
        $this->get('/saas')->assertRedirect(route('saas.two-factor.challenge'));
        $this->keepSession($this->post('/saas/two-factor/challenge', ['two_factor_code' => $this->codeNow($secret, 1)]))->assertRedirect(route('saas.index'));
        $this->get('/saas')->assertOk();

        // Signing out ends it: the next sign-in is challenged again.
        $this->signOut();
        $this->signIn($support);
        $this->get('/saas')->assertRedirect(route('saas.two-factor.challenge'));

        // A password change ends it too.
        $this->travel(30)->seconds();
        $this->keepSession($this->post('/saas/two-factor/challenge', ['two_factor_code' => $this->codeNow($secret, 1)]))->assertRedirect(route('saas.index'));
        $this->get('/saas')->assertOk();
        $this->postJson('/saas/password', ['old_password' => self::PASSWORD, 'new_password' => 'Synthetic-rotated-two-factor-password-226!', 'confirm_password' => 'Synthetic-rotated-two-factor-password-226!'])
            ->assertOk()->assertJsonPath('error', false);
        $this->get('/saas')->assertRedirect(route('saas.two-factor.challenge'));
        $this->assertNull(session(PlatformTwoFactor::SESSION_KEY));
    }

    public function test_an_operator_who_must_change_their_password_passes_the_challenge_first(): void
    {
        [$operator, $secret] = $this->enrolled('operations');
        $operator->forceFill(['password_change_required' => true])->save();
        auth()->forgetGuards();
        $this->keepSession($this->post('/login', ['email' => $operator->email, 'password' => self::PASSWORD]))->assertRedirect('/saas');

        $this->get('/saas')->assertRedirect(route('saas.password'));
        $this->get('/saas/password')->assertRedirect(route('saas.two-factor.challenge'));
        $this->postJson('/saas/password', ['old_password' => self::PASSWORD, 'new_password' => 'Synthetic-rotated-two-factor-password-226!', 'confirm_password' => 'Synthetic-rotated-two-factor-password-226!'])->assertForbidden();
        $this->assertTrue($operator->fresh()->password_change_required);
        $this->keepSession($this->post('/saas/two-factor/challenge', ['two_factor_code' => $this->codeNow($secret)]))->assertRedirect(url('/saas/password'));
        $this->get('/saas/password')->assertOk();
    }

    public function test_a_wrong_or_reused_code_is_refused_and_attempts_are_rate_limited(): void
    {
        [$staff, $secret] = $this->enrolled('finance');
        $this->signIn($staff);

        $this->post('/saas/two-factor/challenge', ['two_factor_code' => $this->wrongCode($secret)])->assertSessionHasErrors('two_factor_code')->assertSessionMissing('_old_input.two_factor_code');
        $this->post('/saas/two-factor/challenge', ['two_factor_code' => 'not a code'])->assertSessionHasErrors('two_factor_code');
        $this->get('/saas')->assertRedirect(route('saas.two-factor.challenge'));
        $this->assertSame(1, $this->audits('platform_two_factor_failed', $staff));

        $code = $this->codeNow($secret);
        $this->keepSession($this->post('/saas/two-factor/challenge', ['two_factor_code' => $code]))->assertRedirect(route('saas.index'));
        $this->signOut();
        $this->signIn($staff);
        // The same code, or the one before it, never works twice.
        $this->post('/saas/two-factor/challenge', ['two_factor_code' => $code])->assertSessionHasErrors('two_factor_code');
        $this->post('/saas/two-factor/challenge', ['two_factor_code' => $this->codeNow($secret, -1)])->assertSessionHasErrors('two_factor_code');
        $this->get('/saas')->assertRedirect(route('saas.two-factor.challenge'));
        $this->assertSame(3, $this->audits('platform_two_factor_failed', $staff));

        // Five refused codes a minute: then even the right one waits.
        for ($i = 0; $i < 3; $i++) {
            $this->post('/saas/two-factor/challenge', ['two_factor_code' => $this->wrongCode($secret)])->assertSessionHasErrors('two_factor_code');
        }
        $this->assertSame(6, $this->audits('platform_two_factor_failed', $staff));
        $next = $this->codeNow($secret, 1);
        $this->post('/saas/two-factor/challenge', ['two_factor_code' => $next])->assertSessionHasErrors(['two_factor_code' => 'Too many codes tried. Wait 60 seconds and try again.']);
        $this->get('/saas')->assertRedirect(route('saas.two-factor.challenge'));
        $this->assertSame(Totp::step(now()->getTimestamp()), PlatformStaffTwoFactor::query()->where('user_id', $staff->id)->value('last_used_step'));

        $this->travel(61)->seconds();
        $this->keepSession($this->post('/saas/two-factor/challenge', ['two_factor_code' => $this->codeNow($secret)]))->assertRedirect(route('saas.index'));
        $this->get('/saas')->assertOk();
    }

    public function test_a_recovery_code_works_once(): void
    {
        $staff = $this->staff('content');
        [, $codes] = $this->enrolThroughPages($staff);
        $this->signOut();
        $this->signIn($staff);

        $this->keepSession($this->post('/saas/two-factor/challenge', ['two_factor_code' => $codes[3]]))
            ->assertRedirect(route('saas.index'))->assertSessionHas('success', 'You used a recovery code. 9 are left.');
        $this->get('/saas')->assertOk();
        $this->assertSame(1, $this->audits('platform_two_factor_recovery_code_used', $staff));
        $this->assertCount(9, (array) PlatformStaffTwoFactor::query()->where('user_id', $staff->id)->firstOrFail()->recovery_codes);

        $this->signOut();
        $this->signIn($staff);
        $this->post('/saas/two-factor/challenge', ['two_factor_code' => $codes[3]])->assertSessionHasErrors('two_factor_code')->assertSessionMissing('_old_input.two_factor_code');
        $this->get('/saas')->assertRedirect(route('saas.two-factor.challenge'));
        $this->assertCount(9, (array) PlatformStaffTwoFactor::query()->where('user_id', $staff->id)->firstOrFail()->recovery_codes);
        // Typed in lower case without dashes, another code still works.
        $this->keepSession($this->post('/saas/two-factor/challenge', ['two_factor_code' => strtolower(str_replace('-', ' ', $codes[7]))]))->assertRedirect(route('saas.index'));
        $this->assertCount(8, (array) PlatformStaffTwoFactor::query()->where('user_id', $staff->id)->firstOrFail()->recovery_codes);
    }

    public function test_an_owner_resets_another_staff_members_two_factor_with_a_reason_but_not_their_own(): void
    {
        $owner = $this->staff('owner');
        [$support] = $this->enrolled('support');
        DB::table('sessions')->insert(['id' => Str::random(40), 'user_id' => $support->id, 'ip_address' => '127.0.0.1', 'user_agent' => 'test', 'payload' => '', 'last_activity' => now()->getTimestamp()]);

        $this->actingAsHq($owner);
        $this->get('/saas/staff')->assertOk()->assertSee('Two-factor sign-in set up')->assertSee('Reset two-factor sign-in')
            ->assertSee(route('saas.staff.two-factor.reset', $support->id))->assertDontSee(route('saas.staff.two-factor.reset', $owner->id));

        $this->post('/saas/staff/'.$support->id.'/two-factor/reset', ['reason' => 'short'])->assertSessionHasErrors('reason');
        $this->assertTrue(PlatformStaffTwoFactor::query()->where('user_id', $support->id)->exists());

        $this->post('/saas/staff/'.$support->id.'/two-factor/reset', ['reason' => 'Lost their phone on the school visit'])->assertRedirect('/saas/staff')->assertSessionHas('success');
        $this->assertFalse(PlatformStaffTwoFactor::query()->where('user_id', $support->id)->exists());
        $this->assertSame(0, DB::table('sessions')->where('user_id', $support->id)->count());
        $audit = AuditLog::query()->where('action', 'platform_two_factor_reset')->latest('id')->firstOrFail();
        $this->assertSame(['user_id' => $support->id, 'via' => 'hq', 'reason' => 'Lost their phone on the school visit', 'changed_by' => $owner->id], $audit->after);
        $this->assertSame($owner->id, (int) $audit->actor_id);
        $this->assertNull($audit->school_id);
        $this->get('/saas/audit?action=platform_two_factor_reset')->assertOk()->assertSee('Two-factor sign-in reset')->assertSee('Lost their phone on the school visit');

        // Nothing to reset now; and nobody resets their own here.
        $this->post('/saas/staff/'.$support->id.'/two-factor/reset', ['reason' => 'Lost their phone on the school visit'])->assertStatus(409);
        $audits = AuditLog::query()->where('action', 'platform_two_factor_reset')->count();
        $this->post('/saas/staff/'.$owner->id.'/two-factor/reset', ['reason' => 'Trying to reset my own two-factor'])->assertForbidden();
        $this->assertTrue(PlatformStaffTwoFactor::query()->where('user_id', $owner->id)->whereNotNull('confirmed_at')->exists());
        $this->assertSame($audits, AuditLog::query()->where('action', 'platform_two_factor_reset')->count());
        $this->post('/saas/staff/'.$this->superAdmin()->id.'/two-factor/reset', ['reason' => 'Not platform staff at all'])->assertNotFound();

        // They set it up again at their next sign-in.
        $this->signIn($support);
        $this->get('/saas')->assertRedirect(route('saas.two-factor.enrol'));
    }

    public function test_a_staff_member_who_is_not_an_owner_cannot_reset_two_factor(): void
    {
        [$support] = $this->enrolled('support');
        foreach (['operations', 'support', 'finance', 'content', 'sales'] as $role) {
            $actor = $this->staff($role, $role.'.reset@platform.example');
            $this->actingAsHq($actor);
            $before = AuditLog::query()->where('action', 'platform_two_factor_reset')->count();
            $this->post('/saas/staff/'.$support->id.'/two-factor/reset', ['reason' => 'Should never be allowed for '.$role])->assertForbidden();
            $this->get('/saas/staff')->assertForbidden();
            $this->assertTrue(PlatformStaffTwoFactor::query()->where('user_id', $support->id)->whereNotNull('confirmed_at')->exists());
            $this->assertSame($before, AuditLog::query()->where('action', 'platform_two_factor_reset')->count());
        }
    }

    public function test_the_console_break_glass_resets_an_owners_own_two_factor_with_a_reason(): void
    {
        [$owner] = $this->enrolled('owner');
        $question = 'Why is this reset needed? (10 to 500 characters, kept in the audit log)';
        $confirm = "Reset two-factor sign-in for {$owner->email}? They are signed out everywhere and set it up again at their next sign-in.";

        $this->artisan('platform:reset-two-factor', ['email' => 'nobody@platform.example'])->assertFailed();
        $this->artisan('platform:reset-two-factor', ['email' => $this->superAdmin()->email])->assertFailed();
        $this->artisan('platform:reset-two-factor', ['email' => $owner->email])->expectsQuestion($question, 'short')->assertFailed();
        $this->artisan('platform:reset-two-factor', ['email' => $owner->email])->expectsQuestion($question, 'The only Owner lost their phone')->expectsConfirmation($confirm, 'no')->assertFailed();
        $this->assertTrue(PlatformStaffTwoFactor::query()->where('user_id', $owner->id)->exists());
        $this->assertSame(0, AuditLog::query()->where('action', 'platform_two_factor_reset')->count());

        $this->artisan('platform:reset-two-factor', ['email' => strtoupper($owner->email)])->expectsQuestion($question, 'The only Owner lost their phone')->expectsConfirmation($confirm, 'yes')->assertSuccessful();
        $this->assertFalse(PlatformStaffTwoFactor::query()->where('user_id', $owner->id)->exists());
        $audit = AuditLog::query()->where('action', 'platform_two_factor_reset')->firstOrFail();
        $this->assertSame('system', $audit->actor_type);
        $this->assertSame(['user_id' => $owner->id, 'via' => 'console', 'reason' => 'The only Owner lost their phone'], $audit->after);
    }

    public function test_a_session_from_before_a_reset_signs_in_again_whatever_the_session_driver(): void
    {
        // File sessions (the default driver): deleting database session rows signs nobody out.
        $files = storage_path('framework/sessions');
        $before = glob($files.'/*') ?: [];
        config(['session.driver' => 'file']);
        $this->app['session']->forgetDrivers();
        try {
            $owner = $this->staff('owner');
            foreach (['hq', 'console'] as $via) {
                [$support, $secret] = $this->enrolled('support', 'support.'.$via.'@platform.example');
                $this->signIn($support);
                $this->keepSession($this->post('/saas/two-factor/challenge', ['two_factor_code' => $this->codeNow($secret)]))->assertRedirect(route('saas.index'));
                $this->get('/saas')->assertOk();
                $this->assertSame(0, DB::table('sessions')->where('user_id', $support->id)->count());

                // Someone else holds this session when the reset happens.
                if ($via === 'hq') {
                    app(PlatformTwoFactor::class)->reset($owner, $support->id, 'Phone stolen with the browser open');
                } else {
                    app(PlatformTwoFactor::class)->breakGlass($support, 'Phone stolen with the browser open');
                }
                $this->assertSame(1, (int) PlatformStaffIdentity::query()->where('user_id', $support->id)->value('two_factor_generation'));

                // The old session reaches neither setup nor any HQ page: it is signed out.
                $this->keepSession($this->get('/saas/two-factor'))->assertRedirect(route('login'))->assertSessionHas('error');
                $this->assertGuest();
                auth()->forgetGuards();
                $this->get('/saas/two-factor')->assertRedirect('/login');
                $this->post('/saas/two-factor', ['current_password' => self::PASSWORD, 'two_factor_code' => '123456'])->assertRedirect('/login');
                $this->assertFalse(PlatformStaffTwoFactor::query()->where('user_id', $support->id)->exists());

                // Other stale sessions: refused on the own password page, and on an HQ page as JSON.
                $this->signIn($support);
                $this->get('/saas/two-factor')->assertOk();
                PlatformStaffIdentity::query()->where('user_id', $support->id)->increment('two_factor_generation');
                $this->keepSession($this->get('/saas/password'))->assertRedirect(route('login'));
                $this->assertGuest();
                $this->signIn($support);
                PlatformStaffIdentity::query()->where('user_id', $support->id)->increment('two_factor_generation');
                $this->keepSession($this->getJson('/saas'))->assertUnauthorized();
                $this->assertGuest();

                // A fresh sign-in with the password sets it up again.
                $this->signIn($support);
                $this->get('/saas')->assertRedirect(route('saas.two-factor.enrol'));
                $fresh = (string) $this->get('/saas/two-factor')->assertOk()->viewData('secret');
                $this->keepSession($this->post('/saas/two-factor', ['current_password' => self::PASSWORD, 'two_factor_code' => $this->codeNow($fresh)]))->assertOk();
                $this->get('/saas')->assertOk();
                $this->signOut();
            }
        } finally {
            foreach (array_diff(glob($files.'/*') ?: [], $before) as $file) {
                @unlink($file);
            }
        }
    }

    public function test_school_accounts_are_unaffected_and_reach_none_of_the_new_pages(): void
    {
        $teacher = User::query()->findOrFail($this->schoolA->ids['tara_user']);
        $this->post('/login', ['email' => $teacher->email, 'password' => 'secret123'])->assertRedirect('/home');
        $this->get('/home')->assertOk();

        foreach ([$this->superAdmin(), $teacher, User::query()->findOrFail($this->schoolB->ids['tara_user'])] as $user) {
            auth()->forgetGuards();
            $this->actingAs($user)->get('/home')->assertOk();
            foreach (self::ROUTES as [$method, $uri]) {
                $this->{$method}($uri, ['two_factor_code' => '123456'])->assertForbidden();
            }
            $this->post('/saas/staff/'.$user->id.'/two-factor/reset', ['reason' => 'A school account trying HQ'])->assertForbidden();
        }
        $this->assertSame(0, PlatformStaffTwoFactor::query()->count());
        $this->assertSame(0, AuditLog::query()->where('action', 'like', 'platform_two_factor%')->count());
    }

    public function test_the_secret_and_recovery_codes_never_appear_in_any_later_response_or_log(): void
    {
        $logged = [];
        Event::listen(MessageLogged::class, function (MessageLogged $event) use (&$logged): void {
            $logged[] = $event->message.' '.json_encode($event->context);
        });
        $owner = $this->staff('owner');
        $this->signIn($owner);
        $setup = $this->get('/saas/two-factor')->assertOk();
        $secret = (string) $setup->viewData('secret');
        $grouped = trim(chunk_split($secret, 4, ' '));
        // The setup page is the one place it shows, to the person setting it up.
        $setup->assertSee($grouped);
        $this->post('/saas/two-factor', ['current_password' => self::PASSWORD, 'two_factor_code' => $this->wrongCode($secret)])->assertSessionHasErrors('two_factor_code');
        $done = $this->keepSession($this->post('/saas/two-factor', ['current_password' => self::PASSWORD, 'two_factor_code' => $this->codeNow($secret)]))->assertOk();
        $codes = $done->viewData('codes');

        $responses = [$done, $this->get('/saas'), $this->get('/saas/staff'), $this->get('/saas/audit'), $this->get('/saas/audit/export'), $this->get('/saas/two-factor'), $this->get('/saas/two-factor/challenge')];
        $this->signOut();
        $this->signIn($owner);
        $responses[] = $this->get('/saas/two-factor/challenge');
        $responses[] = $this->post('/saas/two-factor/challenge', ['two_factor_code' => $this->wrongCode($secret)]);
        $responses[] = $this->keepSession($this->post('/saas/two-factor/challenge', ['two_factor_code' => $codes[0]]));
        $responses[] = $this->get('/saas/audit');
        foreach ($responses as $index => $response) {
            $body = ($response->baseResponse instanceof StreamedResponse ? $response->streamedContent() : $response->baseResponse->getContent()).' '.json_encode($response->headers->all());
            $this->assertStringNotContainsString($secret, (string) $body);
            $this->assertStringNotContainsString($grouped, (string) $body);
            // The codes show on the page that confirms the app ($done), and nowhere after.
            $index > 0 && $this->assertStringNotContainsString($codes[1], (string) $body);
        }
        $this->assertStringNotContainsString($codes[0], (string) $responses[count($responses) - 1]->getContent());

        $stored = (string) DB::table('platform_staff_two_factor')->where('user_id', $owner->id)->value('secret');
        $this->assertStringNotContainsString($secret, $stored);
        $this->assertSame($secret, decrypt($stored, false));
        $model = PlatformStaffTwoFactor::query()->where('user_id', $owner->id)->firstOrFail();
        $this->assertArrayNotHasKey('secret', $model->toArray());
        $this->assertArrayNotHasKey('recovery_codes', $model->toArray());
        $audit = (string) json_encode(AuditLog::query()->get()->toArray());
        $this->assertStringNotContainsString($secret, $audit);
        foreach ($codes as $code) {
            $this->assertStringNotContainsString($code, $audit);
        }
        $this->assertGreaterThan(0, AuditLog::query()->where('action', 'like', 'platform_two_factor%')->count());
        foreach ($logged as $line) {
            $this->assertStringNotContainsString($secret, $line);
            $this->assertStringNotContainsString($codes[1], $line);
        }
        $this->assertNull(session('_old_input.two_factor_code'));
    }
}
