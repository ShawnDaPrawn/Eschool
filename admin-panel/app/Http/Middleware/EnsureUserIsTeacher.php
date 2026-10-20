<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The teacher API only checks that a token is valid, and some endpoints (such
 * as delete-file) don't check anything else, so student and parent app tokens
 * could use them. Only Teacher accounts can log in to the teacher app.
 */
class EnsureUserIsTeacher
{
    public function handle(Request $request, Closure $next)
    {
        if (! $request->user()?->hasRole('Teacher')) {
            return response()->json([
                'error' => true,
                'message' => trans('no_permission_message'),
                'code' => Response::HTTP_FORBIDDEN,
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
