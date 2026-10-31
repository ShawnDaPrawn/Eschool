<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Services\Platform\Saas\PlatformTwoFactor;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;

/**
 * Signing in or out forgets that this browser session passed the platform
 * two-factor challenge (SA.2): every new sign-in is challenged again.
 */
final class ForgetPlatformTwoFactor
{
    public function __construct(private readonly PlatformTwoFactor $twoFactor) {}

    public function handle(Login|Logout $event): void
    {
        $request = request();
        if ($event->guard === 'web' && $request->hasSession()) {
            $this->twoFactor->forget($request->session());
        }
    }
}
