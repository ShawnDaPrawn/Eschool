<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Middleware\EnsureAccountActive;
use App\Http\Middleware\EnsureTeacherActive;
use App\Http\Middleware\RequirePasswordChange;
use App\Providers\RouteServiceProvider;
use App\Services\Platform\Groups\SchoolGroupAccess;
use App\Services\Platform\Saas\PlatformSignIn;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    protected function credentials(Request $request)
    {
        return PlatformSignIn::credentials($request->only($this->username(), 'password'), false);
    }

    /**
     * The user has been authenticated. Parents/Guardians and Students are
     * restricted to the mobile app only, so block them from the Laravel web
     * panel by logging them straight back out and returning to the login
     * screen with an error. Accounts without any role are refused too: every
     * account the school creates has one, so a role-less account was not
     * created by the school.
     */
    protected function authenticated(Request $request, $user): ?RedirectResponse
    {
        if (! EnsureAccountActive::isActive($user)) {
            return $this->refuseLogin($request, $user->hasRole('Teacher') ? 'teacher_account_deactivated' : 'auth.failed');
        }

        if ($user->hasAnyRole(['Parent', 'Student'])) {
            return $this->refuseLogin($request, 'app_only_login_not_allowed_on_web');
        }

        // A school group's administrator may hold no school role at all: they
        // see only the group's dashboard, never a school (Action Plan 5.7).
        if ($user->roles->isEmpty() && app(SchoolGroupAccess::class)->groups($user) !== []
            && ! RequirePasswordChange::mustChange($user, (string) $request->input('password'))) {
            return redirect()->route('group.index');
        }
        if ($user->roles->isEmpty()) {
            return $this->refuseLogin($request, 'no_permission_message');
        }

        if ($user->hasRole('Teacher') && ! EnsureTeacherActive::isActive($user)) {
            return $this->refuseLogin($request, 'teacher_account_deactivated');
        }

        if (RequirePasswordChange::mustChange($user, (string) $request->input('password'))) {
            $request->session()->put(RequirePasswordChange::SESSION_KEY, true);
            // The same cookie also authenticates API calls. Keep the account
            // flag authoritative across the panel and both API versions.
            $user->forceFill(['password_change_required' => true])->save();
        }

        return null;
    }

    private function refuseLogin(Request $request, string $message): RedirectResponse
    {
        $this->guard()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('error', trans($message));
    }
}
