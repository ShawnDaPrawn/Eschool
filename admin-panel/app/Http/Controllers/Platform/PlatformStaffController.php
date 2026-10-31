<?php

declare(strict_types=1);

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Saas\ManagePlatformStaffRequest;
use App\Services\Platform\Saas\PlatformStaff;
use App\Services\Platform\Saas\PlatformStaffAccess;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * /saas/staff: Owners invite platform staff and set their role and expiry
 * (Action Plan SA.1, the staff.manage ability).
 * engineering/specs/platform/platform-roles.md.
 */
class PlatformStaffController extends Controller
{
    public function __construct(private readonly PlatformStaff $staff, private readonly PlatformStaffAccess $access) {}

    public function index(Request $request): View
    {
        return view('saas.staff', ['staff' => $this->staff->list(), 'roles' => PlatformStaff::roles(), 'me' => (int) $request->user()->id, 'maxDays' => PlatformStaff::MAX_DAYS]);
    }

    public function store(ManagePlatformStaffRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $invited = $this->staff->invite($this->access->actor($request->user()), $data);

        return redirect()->route('saas.staff.index')->with('success', __('Invitation sent to :email.', ['email' => $invited['user']->email]));
    }

    public function update(ManagePlatformStaffRequest $request, int $user): RedirectResponse
    {
        $this->staff->change($this->access->actor($request->user()), $user, $request->validated());

        return redirect()->route('saas.staff.index')->with('success', __('Access updated.'));
    }

    public function revoke(ManagePlatformStaffRequest $request, int $user): RedirectResponse
    {
        $data = $request->validated();
        $this->staff->revoke($this->access->actor($request->user()), $user, $data['reason']);

        return redirect()->route('saas.staff.index')->with('success', __('Access revoked.'));
    }

    public function resend(ManagePlatformStaffRequest $request, int $user): RedirectResponse
    {
        $this->staff->resend($this->access->actor($request->user()), $user);

        return redirect()->route('saas.staff.index')->with('success', __('A new invitation was sent.'));
    }
}
