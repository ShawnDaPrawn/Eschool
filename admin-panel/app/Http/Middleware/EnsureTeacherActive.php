<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * A deactivated teacher (users.status other than 1) can't use the teacher API,
 * even with a token issued before they were deactivated.
 *
 * The answer is HTTP 401, which both apps already treat as "signed out": the
 * shared ApiClient clears the session and shows the sign-in screen. The body
 * keeps the usual {error, message, data, code} envelope with code 401.
 */
class EnsureTeacherActive
{
    public const ACTIVE = 1;

    public static function isActive(User $user): bool
    {
        // A model built in code (not read back) has no status yet: ask the table.
        $status = $user->status ?? User::whereKey($user->getKey())->value('status');

        return (int) $status === self::ACTIVE;
    }

    public static function message(): string
    {
        return trans('teacher_account_deactivated');
    }

    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user instanceof User && $user->hasRole('Teacher') && ! self::isActive($user)) {
            return response()->json([
                'error' => true,
                'message' => self::message(),
                'data' => null,
                'code' => Response::HTTP_UNAUTHORIZED,
            ], Response::HTTP_UNAUTHORIZED);
        }

        return $next($request);
    }
}
