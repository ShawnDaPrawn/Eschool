<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Students;
use App\Models\Teacher;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckRole
{
    /**
    * Handle an incoming request.
    *
    * @param  \Illuminate\Http\Request  $request
    * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
    * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
    */
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();
        if (! $user) {
            return response()->view('auth.login');
        }

        // The panel is for staff. Students and parents use the apps, and an
        // account without any role must not get in either.
        if ($user->getRoleNames()->diff(['Student', 'Parent'])->isNotEmpty()) {
            return $next($request);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('error', trans('no_permission_message'));
    }
}
