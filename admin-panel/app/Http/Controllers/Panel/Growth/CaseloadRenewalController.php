<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panel\Growth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Growth\CaseloadRenewalRequest;
use App\Models\SessionYear;
use App\Services\Academics\AcademicAuthority;
use App\Services\Academics\NewYearService;
use App\Services\Growth\CaseloadRenewals;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;

/**
 * /risk-interventions/renewals: last year's open risk caseload grants offered
 * for renewal into the new year (Action Plan 1.20).
 * engineering/specs/growth/caseload-renewals.md.
 */
class CaseloadRenewalController extends Controller
{
    public function index(CaseloadRenewalRequest $request, CaseloadRenewals $renewals): Response|RedirectResponse
    {
        $id = $request->validated('year');
        $year = $id === null ? $this->defaultYear() : SessionYear::query()->findOrFail((int) $id);
        if ($year === null) {
            return redirect()->route('risk-interventions.index')->withErrors(['year' => __('There is no school year to renew caseloads into.')]);
        }

        return response()->view('growth.caseload-renewals', [
            'plan' => $renewals->plan($request->user(), $year),
            'years' => SessionYear::query()->whereDate('end_date', '>=', app(AcademicAuthority::class)->today())->orderByDesc('start_date')->pluck('name', 'id')->all(),
            'actorId' => (int) $request->user()->id, 'schoolId' => app(CurrentSchool::class)->id(),
        ]);
    }

    public function store(CaseloadRenewalRequest $request, CaseloadRenewals $renewals): RedirectResponse
    {
        $data = $request->validated();
        $year = SessionYear::query()->findOrFail((int) $data['year']);
        $done = $renewals->apply($request->user(), $year, $data['cases'] ?? [], $data['fingerprint']);

        return redirect()->route('risk-interventions.renewals', ['year' => $year->id])
            ->with('success', __(':renewed caseloads renewed and :reassigned reassigned for :year.', $done + ['year' => $year->name]));
    }

    private function defaultYear(): ?SessionYear
    {
        $current = SessionYear::query()->find((int) schoolSetting('session_year'));

        return $current === null ? null : (app(NewYearService::class)->upcomingYear($current) ?? $current);
    }
}
