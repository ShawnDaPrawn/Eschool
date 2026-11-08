<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/** Account deactivation applies to every authenticated role and API version. */
final class EnsureAccountActive
{
    public static function isActive(User $user): bool
    {
        // Newly created models may not yet have loaded the database default.
        return (int) ($user->status ?? User::acrossSchools()->whereKey($user->getKey())->value('status')) === 1;
    }

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user instanceof User || self::isActive($user)) {
            return $next($request);
        }

        if ($request->hasSession()) {
            Auth::guard('web')->logoutCurrentDevice();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        if ($request->is('api/*', 'protected/profile-files/*') || $request->expectsJson()) {
            return response()->json([
                'error' => true,
                'message' => $request->is('api/teacher/*')
                    ? EnsureTeacherActive::message()
                    : trans('Your account is deactivated. Please contact admin for further help.'),
                'data' => null,
                'code' => Response::HTTP_UNAUTHORIZED,
            ], Response::HTTP_UNAUTHORIZED)->header('Cache-Control', 'no-store, private');
        }

        return redirect()->route('login')->withErrors(['email' => trans('auth.failed')]);
    }
}
