<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\SendsPasswordResetEmails;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ForgotPasswordController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling password reset emails and
    | includes a trait which assists in sending these notifications from
    | your application to your users. Feel free to explore this trait.
    |
    */

    use SendsPasswordResetEmails;

    /**
     * The same answer whether or not the address has an account (or was just
     * sent a link), so the form can't be used to find out who has one.
     */
    protected function sendResetLinkResponse(Request $request, $response): RedirectResponse|JsonResponse
    {
        $message = trans('If an account exists for this email, a password reset link has been sent.');

        return $request->wantsJson()
            ? new JsonResponse(['message' => $message], 200)
            : back()->with('status', $message);
    }

    protected function sendResetLinkFailedResponse(Request $request, $response): RedirectResponse|JsonResponse
    {
        return $this->sendResetLinkResponse($request, $response);
    }
}
