<?php

declare(strict_types=1);

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Services\Platform\Identity\PasswordPolicy;
use App\Services\Platform\Identity\StaffInvitations;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/** The page behind an emailed staff invitation: choose a password, then sign in. */
class StaffInvitationController extends Controller
{
    public function show(string $token): View
    {
        return view('invitations.staff', ['invitation' => app(StaffInvitations::class)->preview($token), 'token' => $token, 'minimum' => PasswordPolicy::MIN_LENGTH]);
    }

    public function accept(Request $request, string $token): RedirectResponse
    {
        $staff = app(StaffInvitations::class)->accept($token, $request->only(['password', 'password_confirmation']));
        // Whoever was signed in on this browser is not the person who just chose a password.
        if (Auth::check()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return redirect()->route('login')->withInput(['email' => $staff->email])->with('success', __('Your password is set. Sign in to continue.'));
    }
}
