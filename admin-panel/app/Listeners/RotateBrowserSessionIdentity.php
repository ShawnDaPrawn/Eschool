<?php

declare(strict_types=1);

namespace App\Listeners;

use Illuminate\Auth\Events\CurrentDeviceLogout;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;

/** A non-authorizing marker shared by the same browser's web portals. */
final class RotateBrowserSessionIdentity
{
    public const COOKIE = 'navigateed-session-identity';

    public function handle(Login|Logout|CurrentDeviceLogout $event): void
    {
        if ($event->guard !== 'web') {
            return;
        }

        // Never use this as authentication or CSRF proof. JavaScript compares
        // the opaque value solely to discard responses from a replaced session.
        // Normal reads and expired-session 401s must not rotate it.
        Cookie::queue(Cookie::make(
            name: self::COOKIE,
            value: Str::random(40),
            minutes: config('session.expire_on_close') ? 0 : (int) config('session.lifetime'),
            path: config('session.path', '/'),
            domain: config('session.domain'),
            secure: config('session.secure'),
            httpOnly: false,
            sameSite: config('session.same_site', 'lax'),
        ));
    }
}
