<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panel\People;

use App\Http\Controllers\Controller;
use App\Http\Requests\People\WithdrawJoinInvitationRequest;
use App\Services\Platform\Identity\SchoolJoinInvitations;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** /join-invitations: the school's waiting invitations to join it, and withdrawing one (Action Plan 1.3). */
class JoinInvitationOfficeController extends Controller
{
    public function __construct(private readonly SchoolJoinInvitations $invitations) {}

    public function index(Request $request): View
    {
        return view('people.join-invitations', ['invitations' => $this->invitations->waiting($request->user())]);
    }

    public function withdraw(WithdrawJoinInvitationRequest $request, int $invitation): RedirectResponse
    {
        $this->invitations->withdraw($request->user(), $invitation);

        return redirect()->route('join-invitations.index')->with('success', __('The invitation is withdrawn. Its link no longer works.'));
    }
}
