<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Middleware\RequirePasswordChange;
use App\Providers\RouteServiceProvider;
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
        if ($user->hasAnyRole(['Parent', 'Student'])) {
            return $this->refuseLogin($request, 'app_only_login_not_allowed_on_web');
        }

        if ($user->roles->isEmpty()) {
            return $this->refuseLogin($request, 'no_permission_message');
        }

        if (RequirePasswordChange::isDefaultPassword($user, (string) $request->input('password'))) {
            $request->session()->put(RequirePasswordChange::SESSION_KEY, true);
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
