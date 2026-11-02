<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panel\Growth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Growth\InterventionLibraryRequest;
use App\Services\Growth\InterventionLibrary;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * /risk-interventions/library: the school's intervention library (Action Plan
 * 4.11). engineering/specs/growth/check-ins-and-intervention-library.md.
 */
class InterventionLibraryController extends Controller
{
    public function index(InterventionLibraryRequest $request, InterventionLibrary $library): Response
    {
        return response()->view('growth.intervention-library', $library->list($request->user()) + [
            'areas' => InterventionLibrary::AREAS, 'suggested' => InterventionLibrary::DEFAULTS, 'maxSteps' => InterventionLibrary::MAX_STEPS,
            'canWrite' => app(ModuleGate::class)->writable('early_warning'),
            'actorId' => (int) $request->user()->id, 'schoolId' => app(CurrentSchool::class)->id(),
        ])->header('Cache-Control', 'private, no-store');
    }

    public function store(InterventionLibraryRequest $request, InterventionLibrary $library): RedirectResponse
    {
        return $this->saved(fn () => $library->save($request->user(), null, $request->validated()), __('Library action added'));
    }

    public function update(InterventionLibraryRequest $request, InterventionLibrary $library, int $action): RedirectResponse
    {
        return $this->saved(fn () => $library->save($request->user(), $action, $request->validated()), __('Library action saved'));
    }

    public function adopt(InterventionLibraryRequest $request, InterventionLibrary $library): RedirectResponse
    {
        $added = $library->adopt($request->user());

        return redirect()->route('risk-interventions.library')->with('success', trans_choice(':count suggested action added|:count suggested actions added', $added, ['count' => $added]));
    }

    public function archive(InterventionLibraryRequest $request, InterventionLibrary $library, int $action): RedirectResponse
    {
        $archived = $request->boolean('archived');

        return $this->saved(fn () => $library->archive($request->user(), $action, (int) $request->validated('expected_revision'), $archived),
            $archived ? __('Library action archived') : __('Library action restored'));
    }

    private function saved(callable $write, string $message): RedirectResponse
    {
        try {
            $write();
        } catch (HttpExceptionInterface $e) {
            if ($e->getStatusCode() !== 409) {
                throw $e;
            }

            return redirect()->route('risk-interventions.library')->withErrors(['revision' => $e->getMessage()]);
        }

        return redirect()->route('risk-interventions.library')->with('success', $message);
    }
}
