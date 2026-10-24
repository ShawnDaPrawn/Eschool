<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panel\Academics;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academics\PanelCurriculumRequest;
use App\Models\Academics\GradeLevel;
use App\Models\Academics\SubjectStandard;
use App\Models\SessionYear;
use App\Models\Subject;
use App\Models\Term;
use App\Policies\Academics\CurriculumPolicy;
use App\Services\Academics\CurriculumPlans;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CurriculumController extends Controller
{
    public function index(PanelCurriculumRequest $request, CurriculumPlans $plans): View
    {
        return view('academics.curriculum-index', ['plans' => $plans->list($request->user(), $request->validated())->withQueryString()]);
    }

    public function edit(PanelCurriculumRequest $request, CurriculumPlans $plans, ?int $plan = null): View
    {
        $value = $plan === null ? null : $plans->get($request->user(), $plan);
        if ($value === null) {
            app(CurriculumPolicy::class)->authorise($request->user(), 'manage');
        }

        return view('academics.curriculum-edit', ['plan' => $value, 'definition' => $value === null ? null : $plans->data($value),
            'subjects' => Subject::query()->orderBy('name')->get(['id', 'name', 'code']),
            'grades' => GradeLevel::query()->orderBy('sort_order')->get(['id', 'name', 'sort_order']),
            'standards' => SubjectStandard::query()->whereNotNull('published_at')->orderBy('name')->get(['id', 'name', 'code', 'version', 'grade_from', 'grade_to']),
            'years' => SessionYear::query()->orderByDesc('start_date')->get(['id', 'name']),
            'terms' => Term::query()->orderBy('start_date')->get()->map(fn ($t) => ['id' => $t->id, 'name' => $t->name, 'session_year_id' => $t->session_year_id])]);
    }

    public function store(PanelCurriculumRequest $request, CurriculumPlans $plans): RedirectResponse
    {
        $plan = $plans->save($request->user(), $request->validated());

        return redirect()->route('curriculum.edit', $plan->id)->with('success', __('Curriculum draft saved'));
    }

    public function update(PanelCurriculumRequest $request, CurriculumPlans $plans, int $plan): RedirectResponse
    {
        $plans->save($request->user(), $request->validated(), $plan);

        return redirect()->route('curriculum.edit', $plan)->with('success', __('Curriculum draft saved'));
    }

    public function publish(PanelCurriculumRequest $request, CurriculumPlans $plans, int $plan, int $assessment): RedirectResponse
    {
        $plans->publish($request->user(), $plan, $assessment, (int) $request->input('revision'));

        return redirect()->route('curriculum.edit', $plan)->with('success', __('Term assessment published'));
    }

    public function copy(PanelCurriculumRequest $request, CurriculumPlans $plans, int $plan): RedirectResponse
    {
        $copy = $plans->copy($request->user(), $plan, (int) $request->input('session_year_id'));

        return redirect()->route('curriculum.edit', $copy->id)->with('success', __('Definitions copied to a draft. Review dates and weights before publication.'));
    }
}
