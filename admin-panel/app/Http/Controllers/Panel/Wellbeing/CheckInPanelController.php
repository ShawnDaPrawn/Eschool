<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panel\Wellbeing;

use App\Http\Controllers\Controller;
use App\Http\Requests\Wellbeing\CheckInPanelRequest;
use App\Services\Academics\AcademicAuthority;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use App\Services\Wellbeing\CheckIns;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * /wellbeing-check-ins: short private check-ins scheduled and recorded by a
 * learner's authorised pastoral staff (Action Plan 4.11).
 * engineering/specs/growth/check-ins-and-intervention-library.md.
 */
class CheckInPanelController extends Controller
{
    public function index(CheckInPanelRequest $request, CheckIns $checkIns): Response
    {
        return response()->view('wellbeing.check-ins', $checkIns->list($request->user(), (int) $request->validated('page', 1)) + [
            'prompts' => CheckIns::PROMPTS, 'scale' => CheckIns::SCALE, 'perGroup' => CheckIns::PER_GROUP,
            'today' => app(AcademicAuthority::class)->today(),
            'canWrite' => app(ModuleGate::class)->writable('wellbeing') && app(ModuleGate::class)->writable('early_warning'),
            'actorId' => (int) $request->user()->id, 'schoolId' => app(CurrentSchool::class)->id(),
        ])->header('Cache-Control', 'private, no-store');
    }

    public function schedule(CheckInPanelRequest $request, CheckIns $checkIns): RedirectResponse
    {
        return $this->saved(function () use ($request, $checkIns) {
            $done = $checkIns->schedule($request->user(), $request->validated());

            return trans_choice(':count check-in scheduled|:count check-ins scheduled', count($done['ids']), ['count' => count($done['ids'])]);
        });
    }

    public function record(CheckInPanelRequest $request, CheckIns $checkIns, int $checkIn): RedirectResponse
    {
        return $this->saved(function () use ($request, $checkIns, $checkIn) {
            $checkIns->record($request->user(), $checkIn, $request->validated());

            return __('Check-in answer saved');
        });
    }

    public function cancel(CheckInPanelRequest $request, CheckIns $checkIns, int $checkIn): RedirectResponse
    {
        return $this->saved(function () use ($request, $checkIns, $checkIn) {
            $checkIns->cancel($request->user(), $checkIn, (int) $request->validated('expected_revision'));

            return __('Check-in cancelled');
        });
    }

    private function saved(callable $write): RedirectResponse
    {
        try {
            $message = $write();
        } catch (ValidationException $e) {
            // Errors only: the private note is not copied into the session's old input.
            return redirect()->route('wellbeing-check-ins.index')->withErrors($e->errors());
        } catch (HttpExceptionInterface $e) {
            if (! in_array($e->getStatusCode(), [409, 422], true)) {
                throw $e;
            }

            return redirect()->route('wellbeing-check-ins.index')->withErrors(['check_in' => $e->getMessage()]);
        }

        return redirect()->route('wellbeing-check-ins.index')->with('success', $message);
    }
}
