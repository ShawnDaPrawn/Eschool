<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panel\Money;

use App\Http\Controllers\Controller;
use App\Http\Requests\Money\FeePlanPanelRequest;
use App\Models\Academics\GradeLevel;
use App\Models\ClassSchool;
use App\Models\Money\FeePlan;
use App\Models\SessionYear;
use App\Services\Money\FeeCalculator;
use App\Services\Money\FeePlanRules;
use App\Services\Money\FeePlans;
use App\Services\Platform\Modules\ModuleGate;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpFoundation\Response;

class FeePlanPanelController extends Controller
{
    public function index(FeePlanPanelRequest $request): Response
    {
        $years = SessionYear::orderByDesc('start_date')->get(['id', 'name', 'start_date', 'end_date']);
        $year = $request->filled('year_id') ? SessionYear::findOrFail((int) $request->input('year_id')) : $years->first();
        $plans = FeePlan::where('session_year_id', $year->id ?? 0)->orderBy('id')->paginate(30)->withQueryString();
        $gradeNames = GradeLevel::pluck('name', 'id');
        $classNames = ClassSchool::pluck('name', 'id');
        $canWrite = $this->canWrite();

        return response()->view('money.fee-plans.index', compact('years', 'year', 'plans', 'canWrite', 'gradeNames', 'classNames'));
    }

    public function form(FeePlanPanelRequest $request): Response
    {
        $plan = $request->route('plan') === null ? null : FeePlan::findOrFail((int) $request->route('plan'));
        $years = SessionYear::orderByDesc('start_date')->get(['id', 'name']);
        $grades = GradeLevel::orderBy('sort_order')->get(['id', 'name']);
        $classes = ClassSchool::orderBy('name')->get(['id', 'name']);
        $canWrite = $this->canWrite();
        $ruleFields = app(FeePlanRules::class)->fields()->orderBy('label')->get();

        return response()->view('money.fee-plans.form', compact('plan', 'years', 'grades', 'classes', 'canWrite', 'ruleFields'));
    }

    public function save(FeePlanPanelRequest $request): Response
    {
        $plan = app(FeePlans::class)->save($request->user(), $request->validated(), $request->route('plan') === null ? null : (int) $request->route('plan'));

        return redirect()->route('fee-plans.edit', ['plan' => $plan->id], 303)->with('success', __('Fee plan saved.'));
    }

    public function copy(FeePlanPanelRequest $request): Response
    {
        $plan = app(FeePlans::class)->copy($request->user(), (int) $request->route('plan'), $request->validated());

        return redirect()->route('fee-plans.edit', ['plan' => $plan->id], 303)->with('success', __('Draft fee plan copied.'));
    }

    public function preview(FeePlanPanelRequest $request): Response
    {
        $preview = app(FeeCalculator::class)->preview($request->user(), (int) $request->route('year'), (string) $request->input('date'));
        $page = (int) $request->input('page', 1);
        $rows = new LengthAwarePaginator(array_slice($preview['rows'], ($page - 1) * 50, 50), count($preview['rows']), 50, $page, ['path' => $request->url(), 'query' => $request->query()]);

        return response()->view('money.fee-plans.preview', compact('preview', 'rows'));
    }

    private function canWrite(): bool
    {
        // This is a display hint only. Writes reauthorize under the school lock.
        return app(ModuleGate::class)->writable('fees');
    }
}
