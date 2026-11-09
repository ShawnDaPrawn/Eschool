<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Platform\Saas\PlatformSignIn;
use App\Services\ResponseService;
use Illuminate\Foundation\Auth\SendsPasswordResetEmails;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Throwable;

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

    protected function credentials(Request $request)
    {
        return PlatformSignIn::credentials($request->only('email'), false);
    }

    public function sendResetLinkEmail(Request $request): RedirectResponse|JsonResponse
    {
        $this->validateEmail($request);

        try {
            $this->broker()->sendResetLink($this->credentials($request));
        } catch (Throwable $e) {
            // Delivery errors occur only for real accounts. Keep them out of
            // the public response just as the API reset endpoint does.
            ResponseService::logErrorResponse($e);
        }

        return $this->sendResetLinkResponse($request, null);
    }

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
