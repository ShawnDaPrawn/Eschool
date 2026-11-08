<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    public function handle($request, Closure $next, ...$guards)
    {
        $this->authenticate($request, $guards);

        return app(EnsureAccountActive::class)->handle($request, function (Request $request) use ($next) {
            // Authentication is common to every v1/v2 role group and private
            // profile download. A new route cannot omit these account checks.
            if ($request->is('api/*', 'protected/profile-files/*')) {
                return app(EnforcePasswordChange::class)->handle($request, $next);
            }

            return $next($request);
        });
    }

    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        // API clients get a 401, never the login page.
        if (! $request->expectsJson() && ! $request->is('api/*')) {
            return route('login');
        }

        return null;
    }
}
