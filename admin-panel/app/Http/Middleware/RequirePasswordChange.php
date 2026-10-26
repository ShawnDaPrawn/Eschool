<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\Platform\Identity\DateOfBirthPasswords;
use App\Services\Platform\Saas\PlatformStaffAccess;
use Closure;
use Illuminate\Http\Request;

/**
 * Staff who sign in to the panel with a default password must change it before
 * doing anything else. The defaults are public (superadmin for the installer's
 * admin) or easy to find out (a date of birth for teachers and staff). So must
 * an account flagged with users.password_change_required.
 */
class RequirePasswordChange
{
    public const SESSION_KEY = 'must_change_password';

    /** What someone who must change their password can still open. */
    private const ALLOWED = ['resetpassword', 'checkPassword', 'changePassword', 'logout', 'web-set-language/*', 'saas/password'];

    public static function isDefaultPassword(User $user, string $password): bool
    {
        // TeacherController and StaffController set the date of birth as ddmmyyyy;
        // older code used other formats (DateOfBirthPasswords::FORMATS).
        return $password === 'superadmin' || DateOfBirthPasswords::isDateOfBirth($user, $password);
    }

    /** Whether someone signing in with this password must change it first. */
    public static function mustChange(User $user, string $password): bool
    {
        return (bool) $user->password_change_required || self::isDefaultPassword($user, $password);
    }

    public function handle(Request $request, Closure $next)
    {
        if (! $request->hasSession() || ! $request->session()->get(self::SESSION_KEY) || ! $request->user() || $request->is(self::ALLOWED)) {
            return $next($request);
        }

        $message = trans('Please change your password before you continue. It is still the default one.');
        if ($request->expectsJson() || $request->ajax()) {
            $response = response()->json(['error' => true, 'message' => $message], 403);
        } else {
            $destination = app(PlatformStaffAccess::class)->operatesPlatform($request->user())
                ? route('saas.password') : url('resetpassword');
            $response = redirect($destination)->withErrors(['password' => $message]);
        }
        if ($request->is('saas', 'saas/*')) {
            $response->headers->set('Cache-Control', 'no-store, private');
            $response->headers->set('Referrer-Policy', 'no-referrer');
            $response->headers->set('X-Content-Type-Options', 'nosniff');
        }

        return $response;
    }
}
