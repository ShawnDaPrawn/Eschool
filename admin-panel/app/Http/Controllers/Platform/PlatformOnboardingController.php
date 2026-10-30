<?php

declare(strict_types=1);

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Services\Platform\Saas\PlatformOnboarding;
use App\Services\Platform\Saas\PlatformSchools;
use App\Services\Platform\Saas\PlatformStaffAccess;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** HQ: a school's onboarding checklist and its training sessions (Action Plan SA.6). */
class PlatformOnboardingController extends Controller
{
    public function __construct(private readonly PlatformOnboarding $onboarding) {}

    public function show(Request $request, int $school): View
    {
        $row = app(PlatformSchools::class)->find($school);
        abort_if($row === null, 404);

        return view('saas.onboarding', [
            'school' => $row,
            'checklist' => $this->onboarding->checklist($school),
            'canRecord' => app(PlatformStaffAccess::class)->can($request->user(), 'accounts.manage'),
        ]);
    }

    public function training(Request $request, int $school): RedirectResponse
    {
        $this->onboarding->recordTraining($school, $request->only(['held_on', 'topic', 'attendees']), $request->user());

        return redirect()->route('saas.schools.onboarding', $school)->with('success', __('Training session recorded.'));
    }
}
