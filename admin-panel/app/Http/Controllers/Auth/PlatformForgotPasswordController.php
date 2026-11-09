<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Services\Platform\Saas\PlatformSignIn;
use Illuminate\Http\Request;

final class PlatformForgotPasswordController extends ForgotPasswordController
{
    public function showLinkRequestForm()
    {
        return view('saas.auth.forgot-password');
    }

    protected function credentials(Request $request)
    {
        return PlatformSignIn::credentials($request->only('email'), true);
    }
}
