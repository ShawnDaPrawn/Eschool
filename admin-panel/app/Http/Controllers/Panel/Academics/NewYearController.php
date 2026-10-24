<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panel\Academics;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academics\CreateNextYearRequest;
use App\Http\Requests\Academics\SwitchToNewYearRequest;
use App\Models\SessionYear;
use App\Models\TermTemplate;
use App\Services\Academics\NewYearService;
use App\Services\Panel\SessionYearContext;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * The new-year wizard: create next year, choose what to carry forward, check the
 * year end, preview, make it current. NewYearService holds the rules.
 */
class NewYearController extends Controller
{
    public function __construct(
        private readonly NewYearService $newYear,
        private readonly SessionYearContext $years,
    ) {}

    public function index(Request $request): View|RedirectResponse
    {
        if (! $request->user()->can('session-year-create')) {
            return redirect()->route('home')->withErrors(['message' => trans('no_permission_message')]);
        }

        $current = $this->years->current();
        if (! $current) {
            return redirect()->route('session-years.index')->withErrors(['message' => trans('please_set_session_year_first')]);
        }
        $next = $this->newYear->upcomingYear($current);
        $chosen = array_keys(NewYearService::CARRY);

        return view('academics.new-year', [
            'current' => $current,
            'next' => $next,
            'nextTerms' => $next?->terms()->orderBy('start_date')->get() ?? collect(),
            'termTemplates' => TermTemplate::query()->orderBy('name')->pluck('name', 'id')->all(),
            'suggestion' => $this->newYear->suggestion($current),
            'carry' => $this->newYear->carryOptions($current, $next, $chosen),
            'checklist' => $this->newYear->yearEndChecklist($current, $next, $request->user()),
            'preview' => $next ? $this->newYear->preview($current, $next, $chosen) : null,
            'canSwitch' => $request->user()->can('session-year-edit'),
            'step' => $next ? 2 : 1,
        ]);
    }

    public function store(CreateNextYearRequest $request): JsonResponse
    {
        try {
            $year = $this->newYear->create($request->validated());
        } catch (\DomainException $e) {
            return response()->json(['error' => true, 'message' => $e->getMessage()], 422);
        }

        return response()->json([
            'error' => false,
            'message' => trans('new_year_created', ['year' => $year->name]),
            'data' => ['id' => $year->id, 'name' => $year->name],
            'redirect' => route('new-year.index'),
        ]);
    }

    public function preview(SwitchToNewYearRequest $request): JsonResponse
    {
        $current = $this->years->current();
        $target = SessionYear::findOrFail($request->validated('year_id'));

        return response()->json([
            'error' => false,
            'data' => $this->newYear->preview($current, $target, $request->carry()),
        ]);
    }

    public function makeCurrent(SwitchToNewYearRequest $request): JsonResponse
    {
        $current = $this->years->current();
        $target = SessionYear::findOrFail($request->validated('year_id'));

        try {
            $result = $this->newYear->switchTo($current, $target, $request->carry());
        } catch (\DomainException $e) {
            return response()->json(['error' => true, 'message' => $e->getMessage()], 422);
        }

        return response()->json([
            'error' => false,
            'message' => trans('new_year_switched', ['year' => $target->name]),
            'data' => $result,
            'redirect' => route('home'),
        ]);
    }
}
