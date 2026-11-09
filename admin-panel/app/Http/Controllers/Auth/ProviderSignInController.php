<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\SignIn\SignInProviderChallenge;
use App\Providers\RouteServiceProvider;
use App\Services\Platform\SignIn\ProviderSignIn;
use App\Services\Platform\SignIn\ProviderSignInRefused;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * The panel's Google and Microsoft sign-in (Action Plan 5.9): the button on
 * the login page starts it, and the provider sends the browser back to the
 * callback. ProviderSignIn does every check.
 */
class ProviderSignInController extends Controller
{
    public function __construct(private readonly ProviderSignIn $signIn) {}

    public function start(Request $request, string $provider): RedirectResponse
    {
        $school = $request->validate(['school' => ['required', 'string', 'max:100']])['school'];
        try {
            return redirect()->away($this->signIn->startWeb($request, $provider, (string) $school));
        } catch (ProviderSignInRefused $refused) {
            return redirect()->route('login', ['school' => $school])->with('error', __($refused->userMessage()));
        }
    }

    public function callback(Request $request, string $provider): RedirectResponse
    {
        try {
            $done = $this->signIn->callback($request, $provider);
        } catch (ProviderSignInRefused $refused) {
            return Auth::guard('web')->check()
                ? redirect()->route('sign-in-links.index')->withErrors(['provider' => __($refused->userMessage())])
                : redirect()->route('login')->with('error', __($refused->userMessage()));
        }

        return $done['purpose'] === SignInProviderChallenge::LINK
            ? redirect()->route('sign-in-links.index')->with('success', __('Your account is linked. You can now sign in with it.'))
            : redirect()->intended(RouteServiceProvider::HOME);
    }
}
