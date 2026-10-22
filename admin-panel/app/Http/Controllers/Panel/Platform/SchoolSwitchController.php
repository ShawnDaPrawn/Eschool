<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panel\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\SwitchSchoolRequest;
use App\Services\Platform\Identity\MembershipService;
use Illuminate\Http\RedirectResponse;

/**
 * The panel's school switcher (header, beside the year switcher), for people
 * with memberships at several schools. The choice lives in the session and
 * school.context reads it on every request; the selected school year goes
 * back to the new school's current one.
 */
class SchoolSwitchController extends Controller
{
    public function __invoke(SwitchSchoolRequest $request, MembershipService $memberships): RedirectResponse
    {
        $schoolId = $request->schoolId();
        $school = $memberships->active($request->user())->firstWhere('school_id', $schoolId)?->school;

        $request->session()->put((string) config('tenancy.session_key'), $schoolId);
        $request->session()->forget('session_year');

        return redirect()->route('home')->with('success', trans('school_switched', ['school' => $school?->name ?? '']));
    }
}
