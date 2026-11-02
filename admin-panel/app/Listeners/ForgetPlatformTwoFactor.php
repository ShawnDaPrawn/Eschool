<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Models\User;
use App\Services\Platform\Saas\PlatformTwoFactor;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;

/**
 * Signing in or out forgets that this browser session passed the platform
 * two-factor challenge (SA.2): every new sign-in is challenged again. A
 * sign-in also stamps the session with the account's two-factor generation,
 * so a later reset signs it out whatever the session driver.
 */
final class ForgetPlatformTwoFactor
{
    public function __construct(private readonly PlatformTwoFactor $twoFactor) {}

    public function handle(Login|Logout $event): void
    {
        $request = request();
        if ($event->guard !== 'web' || ! $request->hasSession()) {
            return;
        }
        $this->twoFactor->forget($request->session());
        if ($event instanceof Login && $event->user instanceof User) {
            $this->twoFactor->stamp($request->session(), $event->user);
        }
    }
}
