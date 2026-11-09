<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Services\Platform\Saas\PlatformSignIn;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

final class PlatformResetPasswordController extends ResetPasswordController
{
    public function showResetForm(Request $request)
    {
        return view('saas.auth.reset-password', [
            'token' => $request->route('token'),
            'email' => $request->query('email'),
        ]);
    }

    protected function credentials(Request $request)
    {
        return PlatformSignIn::credentials($request->only('email', 'password', 'password_confirmation', 'token'), true);
    }

    protected function resetPassword($user, $password)
    {
        $this->setUserPassword($user, $password);
        $user->setRememberToken(Str::random(60));
        $user->save();
        event(new PasswordReset($user));
        // Resetting a password never supplies business sign-in or MFA proof.
    }

    protected function sendResetResponse(Request $request, $response)
    {
        $this->guard()->logoutCurrentDevice();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('saas.login')->with('status', trans($response));
    }
}
