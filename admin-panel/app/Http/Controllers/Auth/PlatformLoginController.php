<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Middleware\RequirePasswordChange;
use App\Services\Platform\Saas\PlatformSignIn;
use App\Services\Platform\Saas\PlatformStaffAccess;
use App\Services\Platform\Saas\PlatformTwoFactor;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

final class PlatformLoginController extends Controller
{
    use AuthenticatesUsers;

    public function showLoginForm(Request $request)
    {
        $user = $this->guard()->user();
        if (PlatformSignIn::passed($request->session(), $user) && app(PlatformStaffAccess::class)->operatesPlatform($user)) {
            return redirect()->route('saas.home');
        }

        return view('saas.auth.login');
    }

    protected function credentials(Request $request)
    {
        return PlatformSignIn::credentials($request->only('email', 'password'), true);
    }

    protected function validateLogin(Request $request)
    {
        $request->validate(['email' => 'required|email|max:255', 'password' => 'required|string|max:4096']);
    }

    protected function sendFailedLoginResponse(Request $request)
    {
        throw ValidationException::withMessages(['email' => [trans('auth.failed')]])
            ->redirectTo(route('saas.login'));
    }

    protected function attemptLogin(Request $request)
    {
        // A business sign-in always starts fresh, including when a school
        // account is already signed in on this browser. No remember-me login.
        $this->guard()->logoutCurrentDevice();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return $this->guard()->attempt($this->credentials($request), false);
    }

    protected function authenticated(Request $request, $user)
    {
        PlatformSignIn::mark($request->session(), $user);
        app(PlatformTwoFactor::class)->stamp($request->session(), $user);
        if (RequirePasswordChange::mustChange($user, (string) $request->input('password'))) {
            $user->forceFill(['password_change_required' => true])->save();
            $request->session()->put(RequirePasswordChange::SESSION_KEY, true);
        }

        // Never use a school or caller-supplied intended URL after HQ sign-in.
        return redirect()->route('saas.home');
    }

    protected function loggedOut(Request $request)
    {
        return redirect()->route('saas.login');
    }
}
