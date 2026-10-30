<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panel\Academics;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academics\ExamPeriodRequest;
use App\Models\Academics\ExamPeriod;
use App\Models\Academics\GradeLevel;
use App\Models\SessionYear;
use App\Services\Academics\ExamMode;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

/**
 * /exam-periods: the days each grade writes exams (exam mode, Action Plan
 * 1.8). engineering/specs/academics/exam-mode.md.
 */
class ExamPeriodController extends Controller
{
    public function __construct(private readonly ExamMode $examMode) {}

    public function index(ExamPeriodRequest $request): View
    {
        $years = SessionYear::query()->orderByDesc('start_date')->get();
        $chosen = $request->validated('session_year_id');
        $year = $chosen === null ? ($years->firstWhere('default', 1) ?? $years->first()) : $years->firstWhere('id', (int) $chosen);
        abort_if($year === null, 404);

        return view('academics.exam-periods', [
            'year' => $year,
            'years' => $years->pluck('name', 'id')->all(),
            'grades' => GradeLevel::query()->orderBy('sort_order')->pluck('name', 'id')->all(),
            'periods' => $this->examMode->periods((int) $year->id),
        ]);
    }

    public function store(ExamPeriodRequest $request): RedirectResponse
    {
        $this->examMode->save($request->user(), (int) $request->validated('session_year_id'), $request->validated());

        return redirect()->route('exam-periods.index', ['session_year_id' => $request->validated('session_year_id')])->with('success', __('Exam period saved'));
    }

    public function update(ExamPeriodRequest $request, ExamPeriod $period): RedirectResponse
    {
        abort_unless($period->session_year_id === (int) $request->validated('session_year_id'), 404);
        $this->examMode->save($request->user(), $period->session_year_id, $request->validated(), $period);

        return redirect()->route('exam-periods.index', ['session_year_id' => $period->session_year_id])->with('success', __('Exam period saved'));
    }

    public function destroy(ExamPeriodRequest $request, ExamPeriod $period): RedirectResponse
    {
        $year = $period->session_year_id;
        $this->examMode->remove($period);

        return redirect()->route('exam-periods.index', ['session_year_id' => $year])->with('success', __('Exam period removed'));
    }
}
