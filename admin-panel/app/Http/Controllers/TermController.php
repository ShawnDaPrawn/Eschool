<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Academics\ClassTermSettingsRequest;
use App\Http\Requests\Academics\EnableClassTermsRequest;
use App\Http\Requests\Academics\TermRequest;
use App\Http\Requests\Academics\TermTemplateRequest;
use App\Models\ClassSchool;
use App\Models\SessionYear;
use App\Models\Term;
use App\Models\TermTemplate;
use App\Policies\Academics\TermPolicy;
use App\Services\Academics\TermCalendar;
use App\Services\Academics\TermTemplates;
use App\Services\Platform\Tenancy\CurrentSchool;
use App\Services\ResponseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TermController extends Controller
{
    public function index(Request $request, TermCalendar $calendar): View
    {
        app(TermPolicy::class)->authorise($request->user(), 'list');
        $request->validate(['view' => ['nullable', Rule::in(['list', 'edit', 'create', 'configuration', 'templates'])], 'term' => ['nullable', 'integer', 'min:1']]);
        $view = (string) $request->query('view', session()->has('term_preview') ? 'templates' : 'list');
        if (in_array($view, ['edit', 'create', 'configuration'], true)) {
            app(TermPolicy::class)->authorise($request->user(), $view === 'create' ? 'create' : 'edit');
        }
        $years = SessionYear::query()->orderByDesc('start_date')->get();
        $yearId = (int) $request->input('session_year_id', schoolSetting('session_year') ?: $years->first()?->id);
        $year = $yearId === 0 ? null : SessionYear::query()->findOrFail($yearId);

        $termRows = $year === null ? [] : $calendar->list($request->user(), $yearId)['terms'];
        abort_if($view === 'edit' && ! collect($termRows)->contains('id', $request->integer('term')), 404);

        return view('term.index', ['view' => $view, 'years' => $years, 'year' => $year,
            'terms' => $termRows,
            'templates' => TermTemplate::query()->orderBy('name')->get(),
            'classes' => ClassSchool::query()->with('medium')->withSessionConfigFor($yearId)->orderBy('name')->get()]);
    }

    public function store(TermRequest $request, TermCalendar $calendar): RedirectResponse
    {
        $term = $calendar->save($request->user(), $request->validated());

        return redirect()->route('term.index', ['session_year_id' => $term->session_year_id])->with('success', __('Term saved'));
    }

    public function update(TermRequest $request, TermCalendar $calendar, int $term): RedirectResponse
    {
        $saved = $calendar->save($request->user(), $request->validated(), $term);

        return redirect()->route('term.index', ['session_year_id' => $saved->session_year_id])->with('success', __('Term saved'));
    }

    public function destroy(TermRequest $request, TermCalendar $calendar, int $term): RedirectResponse
    {
        $yearId = Term::query()->findOrFail($term)->session_year_id;
        $calendar->remove($request->user(), $term);

        return redirect()->route('term.index', ['session_year_id' => $yearId])->with('success', __('Term removed'));
    }

    public function classSettings(ClassTermSettingsRequest $request, TermCalendar $calendar): JsonResponse
    {
        return ResponseService::successResponse('data_fetch_successfully', $calendar->classSettings($request->user(), (int) $request->input('session_year_id')));
    }

    public function enableClass(EnableClassTermsRequest $request, TermCalendar $calendar): RedirectResponse
    {
        $calendar->enableClass($request->user(), (int) $request->input('session_year_id'), (int) $request->input('class_id'));

        return redirect()->route('class-subject-edit.index', ['id' => $request->input('class_id'), 'session_year_id' => $request->input('session_year_id')])
            ->with('success', __('Terms enabled. Existing records are retained; review the term for each subject allocation.'));
    }

    public function saveTemplate(TermTemplateRequest $request, TermTemplates $templates): RedirectResponse
    {
        $templates->save($request->user(), (int) $request->input('session_year_id'), $request->string('name')->toString());

        return redirect()->route('term.index', ['session_year_id' => $request->input('session_year_id'), 'view' => 'templates'])->with('success', __('Calendar template saved'));
    }

    public function applyTemplate(TermTemplateRequest $request, TermTemplates $templates): RedirectResponse
    {
        $request->validate(['template_id' => ['required', 'integer', 'min:1']]);
        $result = $templates->apply($request->user(), (int) $request->input('template_id'), (int) $request->input('session_year_id'), $request->boolean('apply'));

        return redirect()->route('term.index', ['session_year_id' => $request->input('session_year_id'), 'view' => 'templates'])
            ->with('term_preview', ['school_id' => app(CurrentSchool::class)->id()] + $result)->with('success', $request->boolean('apply') ? __('Calendar template applied') : __('Calendar preview ready'));
    }
}
