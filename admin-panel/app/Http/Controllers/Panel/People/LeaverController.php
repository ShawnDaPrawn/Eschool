<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panel\People;

use App\Http\Controllers\Controller;
use App\Http\Requests\People\EndLeaverAccessRequest;
use App\Http\Requests\People\RestoreLeaverRequest;
use App\Models\ClassSection;
use App\Services\People\Leavers\LeaverState;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * /learners/leavers: learners who left at the year switch, read-only until
 * their leaver_until; the office brings one back or ends their access sooner
 * (Action Plan 1.21). engineering/specs/academics/new-year-structures.md.
 */
class LeaverController extends Controller
{
    public function __construct(private readonly LeaverState $leavers) {}

    public function index(Request $request): View
    {
        $leavers = $this->leavers->leavers($request->user());
        $homerooms = $request->user()->can(LeaverState::EDIT)
            ? ClassSection::query()->with(['class', 'section'])->get()
                ->mapWithKeys(fn (ClassSection $room) => [(int) $room->id => trim(($room->class?->name ?? '').' '.($room->section?->name ?? ''))])
                ->sort()->all()
            : [];

        return view('people.leavers', [
            'leavers' => $leavers,
            'homerooms' => $homerooms,
            'today' => $this->leavers->today(),
            'canEdit' => $request->user()->can(LeaverState::EDIT),
        ]);
    }

    public function restore(RestoreLeaverRequest $request, int $learner): RedirectResponse
    {
        $this->leavers->restore($request->user(), $learner, (int) $request->validated('homeroom_id'));

        return redirect()->route('leavers.index')->with('success', __('The learner is back as a returning learner.'));
    }

    public function end(EndLeaverAccessRequest $request, int $learner): RedirectResponse
    {
        $this->leavers->endAccess($request->user(), $learner);

        return redirect()->route('leavers.index')->with('success', __('Leaver access ended today for the learner and any guardian with no other child here.'));
    }
}
