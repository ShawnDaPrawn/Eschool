<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckRole
{
    /**
     * The admin panel is for staff: a signed-in user needs at least one role other
     * than the app-only Parent and Student roles. Anyone else is signed out.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();
        if ($user && $user->roles()->whereNotIn('name', ['Parent', 'Student'])->exists()) {
            return $next($request);
        }

        if ($user) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors(['message' => trans('no_permission_message')]);
        }

        return response()->view('auth.login');
    }
}
