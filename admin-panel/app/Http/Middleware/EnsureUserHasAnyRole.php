<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Spatie\Permission\Exceptions\UnauthorizedException;
use Symfony\Component\HttpFoundation\Response;

/**
 * The API v2 group for routes every profile calls, such as /me/context: the
 * user needs at least one role. An account whose roles were all taken away
 * keeps its token until it expires, but gets nothing with it.
 */
class EnsureUserHasAnyRole
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user === null) {
            throw UnauthorizedException::notLoggedIn();
        }

        if ($user->getRoleNames()->isEmpty()) {
            throw UnauthorizedException::forRoles(['any']);
        }

        return $next($request);
    }
}
