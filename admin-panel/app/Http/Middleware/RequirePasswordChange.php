<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;

/**
 * Staff who sign in to the panel with a default password must change it before
 * doing anything else. The defaults are public (superadmin for the installer's
 * admin) or easy to find out (a date of birth for teachers and staff).
 */
class RequirePasswordChange
{
    public const SESSION_KEY = 'must_change_password';

    /** What someone who must change their password can still open. */
    private const ALLOWED = ['resetpassword', 'checkPassword', 'changePassword', 'logout', 'web-set-language/*'];

    public static function isDefaultPassword(User $user, string $password): bool
    {
        // TeacherController and StaffController set the date of birth as ddmmyyyy.
        return $password === 'superadmin'
            || ($user->dob && $password === date('dmY', strtotime((string) $user->dob)));
    }

    public function handle(Request $request, Closure $next)
    {
        if (! $request->hasSession() || ! $request->session()->get(self::SESSION_KEY) || ! $request->user() || $request->is(self::ALLOWED)) {
            return $next($request);
        }

        $message = trans('Please change your password before you continue. It is still the default one.');
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['error' => true, 'message' => $message], 403);
        }

        return redirect('resetpassword')->withErrors(['password' => $message]);
    }
}
