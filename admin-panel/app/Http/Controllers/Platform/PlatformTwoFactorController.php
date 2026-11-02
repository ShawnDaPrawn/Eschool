<?php

declare(strict_types=1);

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Saas\TwoFactorCodeRequest;
use App\Services\Platform\Saas\PlatformTwoFactor;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * /saas/two-factor: a platform staff member sets up their authenticator app
 * and, in each new session, passes the challenge before any HQ page opens
 * (Action Plan SA.2, the platform.staff:two-factor routes).
 * engineering/specs/platform/hq-two-factor.md.
 */
class PlatformTwoFactorController extends Controller
{
    public function __construct(private readonly PlatformTwoFactor $twoFactor) {}

    public function enrol(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        if ($user->password_change_required) {
            return redirect()->route('saas.password');
        }
        if ($this->twoFactor->enrolled($user)) {
            return redirect()->route('saas.two-factor.challenge');
        }

        return view('saas.two-factor.enrol', $this->twoFactor->begin($user));
    }

    public function confirm(TwoFactorCodeRequest $request): View
    {
        $user = $request->user();
        $codes = $this->twoFactor->confirm($user, (string) $request->validated('current_password'), (string) $request->validated('two_factor_code'), (string) $request->ip());
        // A new session id for the raised trust, then the passed state bound to it.
        $request->session()->migrate(true);
        $this->twoFactor->remember($request->session(), $user);

        // Shown in this response only: the codes are not kept anywhere in plain text.
        return view('saas.two-factor.recovery-codes', ['codes' => $codes, 'next' => $this->twoFactor->intended($request->session())]);
    }

    public function challenge(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        if (! $this->twoFactor->enrolled($user)) {
            return redirect()->route('saas.two-factor.enrol');
        }
        if ($this->twoFactor->passed($request->session(), $user)) {
            return redirect()->to($this->twoFactor->intended($request->session()));
        }

        return view('saas.two-factor.challenge');
    }

    public function verify(TwoFactorCodeRequest $request): RedirectResponse
    {
        $user = $request->user();
        $result = $this->twoFactor->challenge($user, (string) $request->validated('two_factor_code'), (string) $request->ip());
        $request->session()->migrate(true);
        $this->twoFactor->remember($request->session(), $user);
        $redirect = redirect()->to($this->twoFactor->intended($request->session()));

        return $result['method'] === 'recovery_code'
            ? $redirect->with('success', trans_choice('You used a recovery code. :count is left.|You used a recovery code. :count are left.', $result['remaining'], ['count' => $result['remaining']]))
            : $redirect;
    }
}
