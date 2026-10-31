<?php

declare(strict_types=1);

namespace Tests\Concerns;

use App\Models\Platform\PlatformStaffTwoFactor;
use App\Models\User;
use App\Services\Platform\Saas\PlatformTwoFactor;
use App\Services\Platform\Saas\Totp;
use Illuminate\Testing\TestResponse;

/**
 * Every /saas page needs two-factor sign-in (SA.2), passed once per browser
 * session and bound to the session id. Tests that are about something else
 * sign in with actingAsHq(): the staff member has an authenticator app set up
 * and this test's session has passed the challenge, exactly as the real
 * challenge leaves it. The middleware is not weakened; the session cookie is
 * kept so every request of the test is the same browser session.
 *
 * Tests of the challenge itself (PlatformTwoFactorTest) go through the pages
 * and keep the session cookie from each response with keepSession().
 */
trait PassesPlatformTwoFactor
{
    protected function actingAsHq(User $user): static
    {
        $this->actingAs($user);
        $this->passPlatformTwoFactor($user);

        return $this;
    }

    /** Sets up an app for the staff member if they have none, and marks this session as passed. */
    protected function passPlatformTwoFactor(User $user): void
    {
        PlatformStaffTwoFactor::query()->where('user_id', $user->id)->whereNull('confirmed_at')->delete();
        PlatformStaffTwoFactor::query()->firstOrCreate(['user_id' => $user->id], ['secret' => Totp::generateSecret(), 'confirmed_at' => now(), 'recovery_codes' => []]);
        $this->startSession();
        $session = $this->app['session']->driver();
        // As after a fresh sign-in: AuthenticateSession records this account's password again.
        $session->forget('password_hash_'.config('auth.defaults.guard'));
        app(PlatformTwoFactor::class)->remember($session, $user);
        // The session as a browser would send it back, on JSON requests too: same id, same contents.
        $session->save();
        $this->withCookie((string) config('session.cookie'), $session->getId());
        $this->withCredentials();
    }

    /**
     * Someone else (a school account) in a fresh browser: the HQ session and
     * its cookie are dropped, as when a different person signs in elsewhere.
     */
    protected function newBrowserSession(): static
    {
        unset($this->defaultCookies[(string) config('session.cookie')]);
        $this->withCredentials = false;
        $this->app['session']->driver()->flush();

        return $this;
    }

    /** Keeps the session cookie a response sets (a new id after sign-in or the challenge). */
    protected function keepSession(TestResponse $response): TestResponse
    {
        $cookie = $response->getCookie((string) config('session.cookie'));
        if ($cookie !== null && is_string($cookie->getValue())) {
            $this->withCookie((string) config('session.cookie'), $cookie->getValue());
            $this->withCredentials();
        }

        return $response;
    }
}
