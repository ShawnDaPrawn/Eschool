<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Academics\TermRequest;
use App\Http\Requests\Academics\TermTemplateRequest;
use App\Models\SessionYear;
use App\Models\Term;
use App\Models\TermTemplate;
use App\Policies\Academics\TermPolicy;
use App\Services\Academics\TermCalendar;
use App\Services\Academics\TermTemplates;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TermController extends Controller
{
    public function index(Request $request, TermCalendar $calendar): View
    {
        app(TermPolicy::class)->authorise($request->user(), 'list');
        $years = SessionYear::query()->orderByDesc('start_date')->get();
        $yearId = (int) $request->input('session_year_id', schoolSetting('session_year') ?: $years->first()?->id);
        $year = $yearId === 0 ? null : SessionYear::query()->findOrFail($yearId);

        return view('term.index', ['years' => $years, 'year' => $year,
            'terms' => $year === null ? [] : $calendar->list($request->user(), $yearId)['terms'],
            'templates' => TermTemplate::query()->orderBy('name')->get()]);
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

    public function saveTemplate(TermTemplateRequest $request, TermTemplates $templates): RedirectResponse
    {
        $templates->save($request->user(), (int) $request->input('session_year_id'), $request->string('name')->toString());

        return redirect()->route('term.index', ['session_year_id' => $request->input('session_year_id')])->with('success', __('Calendar template saved'));
    }

    public function applyTemplate(TermTemplateRequest $request, TermTemplates $templates): RedirectResponse
    {
        $request->validate(['template_id' => ['required', 'integer', 'min:1']]);
        $result = $templates->apply($request->user(), (int) $request->input('template_id'), (int) $request->input('session_year_id'), $request->boolean('apply'));

        return redirect()->route('term.index', ['session_year_id' => $request->input('session_year_id')])
            ->with('term_preview', ['school_id' => app(CurrentSchool::class)->id()] + $result)->with('success', $request->boolean('apply') ? __('Calendar template applied') : __('Calendar preview ready'));
    }
}
