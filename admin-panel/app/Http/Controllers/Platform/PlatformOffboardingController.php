<?php

declare(strict_types=1);

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Saas\OffboardingRequest;
use App\Services\Platform\Saas\PlatformOffboarding;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

/** HQ: a leaving school's offboarding (Action Plan SA.11). States and counts only, never the school's data. */
class PlatformOffboardingController extends Controller
{
    public function __construct(private readonly PlatformOffboarding $offboarding) {}

    public function show(int $school): View
    {
        return view('saas.offboarding', $this->offboarding->show($school));
    }

    public function start(OffboardingRequest $request, int $school): RedirectResponse
    {
        $id = $this->offboarding->start($school, $request->validated(), $request->user());

        return $this->back($school, __('Offboarding :reference started. The export is being prepared.', ['reference' => PlatformOffboarding::reference($id)]));
    }

    public function retry(OffboardingRequest $request, int $school): RedirectResponse
    {
        $this->offboarding->retry($school, $request->user());

        return $this->back($school, __('The export is being prepared again.'));
    }

    public function archive(OffboardingRequest $request, int $school): RedirectResponse
    {
        $this->offboarding->archive($school, $request->user());

        return $this->back($school, __('School archived. Deletion is scheduled.'));
    }

    public function cancel(OffboardingRequest $request, int $school): RedirectResponse
    {
        $this->offboarding->cancel($school, $request->validated(), $request->user());

        return $this->back($school, __('Offboarding cancelled.'));
    }

    public function dryRun(OffboardingRequest $request, int $school): RedirectResponse
    {
        $this->offboarding->dryRun($school, $request->user());

        return $this->back($school, __('Deletion dry run done. Nothing was deleted.'));
    }

    public function requestDeletion(OffboardingRequest $request, int $school): RedirectResponse
    {
        $this->offboarding->requestDeletion($school, $request->user());

        return $this->back($school, __('Deletion asked for. A different Owner must approve it.'));
    }

    public function approve(OffboardingRequest $request, int $school): RedirectResponse
    {
        $this->offboarding->approveDeletion($school, $request->user());

        return $this->back($school, __('Deletion approved and the dry run repeated. Nothing was deleted: deleting the rows is not built yet.'));
    }

    public function hold(OffboardingRequest $request, int $school): RedirectResponse
    {
        $this->offboarding->placeHold($school, $request->validated(), $request->user());

        return $this->back($school, __('Legal hold placed. Deletion is stopped.'));
    }

    public function liftHold(OffboardingRequest $request, int $school): RedirectResponse
    {
        $this->offboarding->liftHold($school, $request->user());

        return $this->back($school, __('Legal hold lifted.'));
    }

    private function back(int $school, string $message): RedirectResponse
    {
        return redirect()->route('saas.schools.offboarding', $school)->with('success', $message);
    }
}
