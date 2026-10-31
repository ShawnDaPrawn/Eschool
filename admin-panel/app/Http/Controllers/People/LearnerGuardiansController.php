<?php

declare(strict_types=1);

namespace App\Http\Controllers\People;

use App\Http\Controllers\Controller;
use App\Http\Requests\People\LearnerGuardianRequest;
use App\Models\Platform\GuardianLink;
use App\Models\Students;
use App\Services\Platform\Identity\GuardianLinks;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;

/** A learner's guardians, managed by the school office (Action Plan 1.2). */
class LearnerGuardiansController extends Controller
{
    public function __construct(private readonly GuardianLinks $links) {}

    public function index(LearnerGuardianRequest $request, int $learner): Response
    {
        $guardians = $this->links->list($request->user(), $learner);
        $record = Students::query()->with('user')->findOrFail($learner);

        return response()->view('people.learner-guardians', ['learner' => $record, 'guardians' => $guardians, 'relationships' => GuardianLinks::RELATIONSHIPS])
            ->header('Cache-Control', 'private, no-store');
    }

    public function store(LearnerGuardianRequest $request, int $learner): RedirectResponse
    {
        $this->links->add($request->user(), $learner, $request->validated());

        return redirect()->route('learner-guardians.index', $learner)->with('success', __('Guardian added'));
    }

    public function update(LearnerGuardianRequest $request, int $learner, int $link): RedirectResponse
    {
        $this->owned($learner, $link);
        $this->links->update($request->user(), $link, $request->validated());

        return redirect()->route('learner-guardians.index', $learner)->with('success', __('Guardian updated'));
    }

    public function end(LearnerGuardianRequest $request, int $learner, int $link): RedirectResponse
    {
        $this->owned($learner, $link);
        $this->links->end($request->user(), $link, $request->validated());

        return redirect()->route('learner-guardians.index', $learner)->with('success', __('Guardian removed'));
    }

    /** A link id from another learner's page is not found. */
    private function owned(int $learner, int $link): void
    {
        abort_unless(GuardianLink::query()->whereKey($link)->where('learner_id', $learner)->exists(), 404);
    }
}
