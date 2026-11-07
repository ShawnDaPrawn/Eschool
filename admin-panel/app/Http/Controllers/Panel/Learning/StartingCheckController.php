<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panel\Learning;

use App\Http\Controllers\Controller;
use App\Http\Requests\Learning\StartingCheckRequest;
use App\Services\Learning\DiagnosticService;
use App\Services\Learning\PracticeChoices;
use Illuminate\Contracts\View\View;

/** /starting-checks: who in a teacher's group has done the starting check, and the starting map per topic (Action Plan 3.7). */
class StartingCheckController extends Controller
{
    public function index(StartingCheckRequest $request, PracticeChoices $choices, DiagnosticService $diagnostics): View
    {
        $groups = $choices->access($request->user())['staff_groups'];
        $asked = $request->validated('group');
        $chosen = $asked !== null ? (int) $asked : ($groups[0]['id'] ?? null);
        // Another teacher's or another school's group is refused by the service (403 or 404).
        $view = $chosen === null ? null : $diagnostics->group($request->user(), $chosen);

        return view('learning.starting-checks', ['groups' => $groups, 'chosen' => $chosen, 'view' => $view, 'bands' => DiagnosticService::BANDS]);
    }
}
