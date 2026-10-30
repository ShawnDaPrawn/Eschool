<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panel\Activities;

use App\Http\Controllers\Controller;
use App\Models\SessionYear;
use App\Services\Activities\ActivitySeasons;
use App\Services\Panel\SessionYearContext;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** /activity-seasons: start the next sports or culture season (Action Plan 1.22). */
class ActivitySeasonController extends Controller
{
    public function __construct(private readonly ActivitySeasons $seasons) {}

    public function index(Request $request): View
    {
        $year = $this->year();

        return view('activities.seasons', ['year' => $year, 'seasons' => $this->seasons->seasons($request->user(), (int) $year->id)]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'from' => ['required', 'string', 'max:64'],
            'to' => ['required', 'string', 'max:64'],
            'starts_on' => ['required', 'date_format:Y-m-d'],
            'ends_on' => ['required', 'date_format:Y-m-d'],
        ]);
        $result = $this->seasons->rollover($request->user(), $data + ['session_year_id' => $this->year()->id]);

        return redirect()->route('activity-seasons.index')->with('success', __(':created groups started for :season; :skipped were already there or did not fit their parent\'s dates.', [
            'created' => $result['created'], 'skipped' => $result['skipped'], 'season' => trim($data['to'])]));
    }

    /** Seasons change in the year in use. */
    private function year(): SessionYear
    {
        $id = app(SessionYearContext::class)->currentId();
        abort_if($id === null, 422, __('Set up a school year first.'));

        return SessionYear::query()->findOrFail($id);
    }
}
