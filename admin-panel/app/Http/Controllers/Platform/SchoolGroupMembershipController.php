<?php

declare(strict_types=1);

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\SchoolGroupDecisionRequest;
use App\Services\Platform\Groups\SchoolGroups;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/** The school's side of school groups (5.7): its Super Admin agrees, declines or leaves. */
class SchoolGroupMembershipController extends Controller
{
    public function __construct(private readonly SchoolGroups $groups) {}

    public function index(Request $request): Response
    {
        return response()->view('platform.school-groups', $this->groups->schoolPage($request->user()))->header('Cache-Control', 'private, no-store');
    }

    public function agree(SchoolGroupDecisionRequest $request, int $member): RedirectResponse
    {
        $this->groups->agree($request->user(), $member);

        return redirect()->route('school-groups.index')->with('success', __('Your school joined the group. Its administrators now see your school\'s figures as described.'));
    }

    public function decline(SchoolGroupDecisionRequest $request, int $member): RedirectResponse
    {
        $this->groups->decline($request->user(), $member);

        return redirect()->route('school-groups.index')->with('success', __('Invitation declined. Nothing about your school was shared.'));
    }

    public function leave(SchoolGroupDecisionRequest $request, int $member): RedirectResponse
    {
        $this->groups->leave($request->user(), $member, $request->validated());

        return redirect()->route('school-groups.index')->with('success', __('Your school left the group. The group no longer sees your school.'));
    }
}
