<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class CheckRole
{
    /**
     * The admin panel is for staff: a signed-in user needs at least one role other
     * than the app-only Parent and Student roles. Anyone else is signed out, and
     * so is a teacher who was deactivated while signed in.
     *
     * @param  Closure(Request): (Response|RedirectResponse)  $next
     * @return Response|RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();
        $isStaff = $user && $user->roles()->whereNotIn('name', ['Parent', 'Student'])->exists();
        $deactivatedTeacher = $isStaff && $user->hasRole('Teacher') && ! EnsureTeacherActive::isActive($user);
        if ($isStaff && ! $deactivatedTeacher) {
            return $next($request);
        }

        if ($user) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            $message = $deactivatedTeacher ? 'teacher_account_deactivated' : 'no_permission_message';

            return redirect()->route('login')->withErrors(['message' => trans($message)]);
        }

        return response()->view('auth.login');
    }
}
