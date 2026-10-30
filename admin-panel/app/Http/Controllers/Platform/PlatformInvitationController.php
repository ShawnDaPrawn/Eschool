<?php

declare(strict_types=1);

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Saas\AcceptPlatformInvitationRequest;
use App\Services\Platform\Saas\PlatformStaff;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

/**
 * /platform/invite/{token}: an invited platform staff member chooses their
 * password, then signs in (Action Plan SA.1). The page is private and never
 * cached; an unknown, used or expired link answers 404.
 */
class PlatformInvitationController extends Controller
{
    public function __construct(private readonly PlatformStaff $staff) {}

    public function show(string $token): View
    {
        return view('saas.invitation', ['invitation' => $this->staff->preview($token), 'token' => $token]);
    }

    public function accept(AcceptPlatformInvitationRequest $request, string $token): RedirectResponse
    {
        $this->staff->accept($token, $request->validated());
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', __('Your password is set. Sign in with your email address and new password.'));
    }
}
