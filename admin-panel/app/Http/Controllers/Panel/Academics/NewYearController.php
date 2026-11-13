<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panel\Academics;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academics\CreateNextYearRequest;
use App\Http\Requests\Academics\SwitchToNewYearRequest;
use App\Models\SessionYear;
use App\Models\TermTemplate;
use App\Policies\Academics\CurriculumPolicy;
use App\Services\Academics\NewYearService;
use App\Services\Panel\SessionYearContext;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

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
        if (! $request->user()->canAny(['session-year-create', 'session-year-edit'])) {
            return redirect()->route('home')->withErrors(['message' => trans('no_permission_message')]);
        }

        $current = $this->years->current();
        if (! $current) {
            return redirect()->route('session-years.index')->withErrors(['message' => trans('please_set_session_year_first')]);
        }
        $request->validate(['year' => ['nullable', 'integer', 'min:1']]);
        $next = $request->filled('year') ? SessionYear::findOrFail($request->integer('year')) : $this->newYear->upcomingYear($current);
        abort_if($next && ((int) $next->id === (int) $current->id || $next->start_date <= $current->start_date), 422, __('Choose a prepared year after the current school year.'));
        $chosen = array_values(array_diff(array_keys(NewYearService::CARRY), ['curriculum']));
        $canCarryCurriculum = false;
        try {
            app(CurriculumPolicy::class)->authorise($request->user(), 'publish');
            $canCarryCurriculum = true;
        } catch (HttpExceptionInterface) {
            // Keep the optional choice visible with its permission prerequisite.
        }

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
            'canCreate' => $request->user()->can('session-year-create'),
            'canCarryCurriculum' => $canCarryCurriculum,
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
            $result = $this->newYear->switchTo($current, $target, $request->carry(), $request->boolean('welcome'));
        } catch (\DomainException $e) {
            return response()->json(['error' => true, 'message' => $e->getMessage()], 422);
        }

        $request->session()->put('school_year_transition', [
            'school_id' => app(CurrentSchool::class)->id(),
            'actor_id' => (int) $request->user()->id, 'previous' => $current->name,
            'current' => $target->name, 'year_id' => (int) $target->id,
            'completed_at' => now()->toIso8601String(), 'result' => $result,
        ]);

        return response()->json([
            'error' => false,
            'message' => trans('new_year_switched', ['year' => $target->name]),
            'data' => $result,
            'redirect' => route('new-year.result'),
        ]);
    }

    public function result(Request $request): Response
    {
        abort_unless($request->user()->can('session-year-edit'), 403);
        $receipt = $request->session()->get('school_year_transition');
        abort_unless(is_array($receipt)
            && ($receipt['school_id'] ?? null) === app(CurrentSchool::class)->id()
            && ($receipt['actor_id'] ?? null) === (int) $request->user()->id, 404);

        return response()->view('academics.new-year-result', compact('receipt'))->header('Cache-Control', 'no-store, private');
    }
}
