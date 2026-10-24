<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panel\Academics;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academics\SubjectStandardRequest;
use App\Models\Mediums;
use App\Models\Subject;
use App\Policies\Academics\SubjectStandardPolicy;
use App\Services\Academics\SubjectStandards;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SubjectStandardController extends Controller
{
    public function index(SubjectStandardRequest $request, SubjectStandards $standards): View
    {
        app(SubjectStandardPolicy::class)->authorise($request->user(), 'list');

        return view('academics.subject-standards', [
            'catalogue' => $standards->catalogue($request->user(), $request->validated())->withQueryString(),
            'subjects' => Subject::query()->with('standard')->orderBy('name')->get(),
            'mediums' => Mediums::query()->orderBy('name')->get(),
        ]);
    }

    public function update(SubjectStandardRequest $request, SubjectStandards $standards, int $subject): RedirectResponse
    {
        $standards->map($request->user(), $subject, $request->input('standard_id') === null ? null : (int) $request->input('standard_id'));

        return redirect()->route('subject-standards.index')->with('success', __('Subject standard saved'));
    }

    public function store(SubjectStandardRequest $request, SubjectStandards $standards): RedirectResponse
    {
        $standards->createSubject($request->user(), (int) $request->input('standard_id'), (int) $request->input('medium_id'),
            $request->string('type')->toString(), $request->string('bg_color')->toString());

        return redirect()->route('subject-standards.index')->with('success', __('School subject created'));
    }
}
