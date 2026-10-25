<?php

declare(strict_types=1);

namespace App\Services\Admissions;

use App\Models\Academics\GradeLevel;
use App\Models\Admissions\Application;
use App\Models\Admissions\ApplicationDocument;
use App\Models\Admissions\ApplicationEvent;
use App\Models\Admissions\Intake;
use App\Models\Admissions\IntakeGrade;
use App\Models\Category;
use App\Models\SessionYear;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class IntakePanelPage
{
    public function index(Request $r): Response
    {
        app(IntakePanelScope::class)->authorize($r);
        $intakes = Intake::orderByDesc('id')->get();
        $intake = $r->filled('intake_id') ? Intake::findOrFail((int) $r->input('intake_id')) : $intakes->first();
        $applications = null;
        $gradeNames = [];
        if ($intake !== null) {
            $days = max(1, min((int) $intake->keep_for_days, (int) config('admissions.maximum_keep_days')));
            $query = Application::select(['id', 'public_id', 'intake_grade_id', 'state', 'submitted_at', 'office_fee_cents', 'office_fee_paid', 'revision'])->where('intake_id', $intake->id)->where('state', '!=', 'draft');
            // Filter due bodies before the paginator count, even before daily erasure runs.
            $query->where(function ($q) use ($intake, $days): void {
                $q->where('state', 'accepted')->orWhere(fn ($enrolled) => $enrolled->where('state', 'enrolled')->where('enrolled_at', '>', now()->utc()->subDays($days)));
                if ($intake->closes_at->addDays($days)->greaterThan(now())) {
                    $q->orWhereIn('state', ['submitted', 'waitlisted']);
                }
                $q->orWhere(fn ($offered) => $offered->where('state', 'offered')->whereExists(fn ($offer) => $offer->selectRaw('1')->from('offers')->whereColumn('offers.application_id', 'applications.id')->where('offers.school_id', app(CurrentSchool::class)->id())->where('offers.state', 'offered')->where('offers.expires_at', '>', now()->utc()->subDays($days))));
                $q->orWhere(fn ($terminal) => $terminal->whereIn('state', ['rejected', 'withdrawn', 'offer_expired'])->where('terminal_at', '>', now()->utc()->subDays($days)));
            });
            if ($r->filled('state')) {
                $query->where('state', $r->input('state'));
            }
            $applications = $query->orderBy('submitted_at')->orderBy('id')->paginate(50)->withQueryString();
            $gradeNames = IntakeGrade::where('intake_id', $intake->id)->get()->mapWithKeys(fn ($grade) => [$grade->id => GradeLevel::findOrFail($grade->grade_level_id)->name])->all();
        }
        $categories = Category::where('status', 1)->orderBy('name')->get(['id', 'name']);
        $intakeYear = $intake === null ? null : SessionYear::findOrFail($intake->session_year_id);

        return response()->view('admissions.panel.pipeline', compact('intakes', 'intake', 'intakeYear', 'applications', 'gradeNames', 'categories'));
    }

    public function configuration(Request $r): Response
    {
        app(IntakePanelScope::class)->authorize($r);
        $intake = $r->route('intake') ? Intake::findOrFail((int) $r->route('intake')) : null;
        $years = SessionYear::orderByDesc('start_date')->get(['id', 'name']);
        $fields = app(ApplicationForms::class)->selectable();
        $grades = GradeLevel::orderBy('sort_order')->get(['id', 'name']);
        $capacities = $intake ? IntakeGrade::where('intake_id', $intake->id)->pluck('places', 'grade_level_id')->all() : [];

        return response()->view('admissions.panel.configuration', compact('intake', 'years', 'fields', 'grades', 'capacities'));
    }

    public function detail(Request $r): Response
    {
        $row = app(IntakeAuthority::class)->application($r->user(), (int) $r->route('application'));
        $intake = Intake::findOrFail($row->intake_id);
        $fields = app(ApplicationForms::class)->definitions($intake);
        $documents = ApplicationDocument::where('application_id', $row->id)->orderBy('id')->get(['handle', 'category', 'bytes']);
        $events = ApplicationEvent::where('application_id', $row->id)->orderByDesc('id')->paginate(20)->withQueryString();
        $year = SessionYear::findOrFail($intake->session_year_id);
        $startsOn = (string) $r->input('starts_on', max(now()->toDateString(), substr((string) $year->start_date, 0, 10)));
        $choices = app(AdmissionAllocation::class)->choices($row, $startsOn);
        $categories = Category::where('status', 1)->orderBy('name')->get(['id', 'name']);

        return response()->view('admissions.panel.detail', compact('row', 'intake', 'fields', 'documents', 'events', 'choices', 'startsOn', 'categories'));
    }

    public function failure(Request $r, array $errors, int $status): Response
    {
        app(IntakePanelScope::class)->authorize($r);
        $draft = [];
        foreach (['reason' => 2000, 'reference' => 191, 'name' => 160] as $key => $limit) {
            if (is_string($r->input($key))) {
                $draft[] = ['label' => $key, 'value' => mb_substr($r->input($key), 0, $limit)];
            }
        }
        $message = implode(' ', array_map(fn ($item) => is_array($item) ? implode(' ', $item) : $item, $errors));
        $title = 'Admissions';
        $returnUrl = route('admissions-panel.index');

        return response()->view('errors.private-year-refusal', compact('message', 'title', 'draft', 'returnUrl'), $status)->header('Cache-Control', 'private, no-store');
    }
}
