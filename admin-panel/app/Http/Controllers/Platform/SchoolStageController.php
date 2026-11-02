<?php

declare(strict_types=1);

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\SchoolStagesRequest;
use App\Services\Platform\Stages\SchoolStages;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

/** /school-stages: the age stage of each grade at this school (Action Plan 4.1). */
class SchoolStageController extends Controller
{
    public function __construct(private readonly SchoolStages $stages) {}

    public function index(SchoolStagesRequest $request): View
    {
        return view('platform.stages', [
            'grades' => $this->stages->overview($request->user()),
            'stages' => SchoolStages::STAGES,
        ]);
    }

    public function update(SchoolStagesRequest $request): RedirectResponse
    {
        $changed = $this->stages->update($request->user(), $request->validated('stages'));

        return redirect()->route('school-stages.index')->with('success', $changed === 0
            ? __('No grade changed stage.')
            : trans_choice(':count grade moved to a new stage.|:count grades moved to a new stage.', $changed, ['count' => $changed]));
    }
}
