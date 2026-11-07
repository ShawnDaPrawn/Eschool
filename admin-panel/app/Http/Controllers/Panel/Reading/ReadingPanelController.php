<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panel\Reading;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reading\ReadingPanelRequest;
use App\Services\Reading\ReadingCatalogue;
use App\Services\Reading\ReadingSchool;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

/** /reading-library: a school leader hides shelves and books and sets the reading badges (Action Plan 4.4). */
class ReadingPanelController extends Controller
{
    public function index(ReadingPanelRequest $request, ReadingSchool $school): View
    {
        return view('reading.index', $school->overview($request->user()) + ['levels' => ReadingCatalogue::LEVELS]);
    }

    public function hidden(ReadingPanelRequest $request, ReadingSchool $school): RedirectResponse
    {
        $school->hide($request->user(), $request->validated());

        return redirect()->route('reading-library.index')->with('success', $request->boolean('hidden') ? __('Hidden from your learners.') : __('Shown to your learners again.'));
    }

    public function settings(ReadingPanelRequest $request, ReadingSchool $school): RedirectResponse
    {
        $school->configure($request->user(), $request->validated());

        return redirect()->route('reading-library.index')->with('success', __('Reading badges saved.'));
    }
}
