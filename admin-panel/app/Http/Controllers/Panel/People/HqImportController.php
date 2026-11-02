<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panel\People;

use App\Http\Controllers\Controller;
use App\Http\Requests\People\HqImportDecisionRequest;
use App\Services\Platform\Saas\PlatformSchoolImports;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

/**
 * /hq-imports: the imports Navigate Ed prepared for this school, every step
 * of each, and the approving leader's decision (Action Plan SA.11).
 * engineering/specs/platform/hq-imports.md.
 */
class HqImportController extends Controller
{
    public function __construct(private readonly PlatformSchoolImports $imports, private readonly CurrentSchool $currentSchool) {}

    public function index(HqImportDecisionRequest $request): View
    {
        return view('people.hq-imports', ['imports' => $this->imports->list($this->currentSchool->id())]);
    }

    public function show(HqImportDecisionRequest $request, int $import): View
    {
        $details = $this->imports->show($import, $this->currentSchool->id());

        return view('people.hq-import', ['import' => $details, 'decides' => $this->imports->awaits($request->user(), $details)]);
    }

    public function approve(HqImportDecisionRequest $request, int $import): RedirectResponse
    {
        $this->imports->approve($request->user(), $import, $request->validated());

        return redirect()->route('hq-imports.show', $import)->with('success', __('Approved. Navigate Ed can now import these rows.'));
    }

    public function decline(HqImportDecisionRequest $request, int $import): RedirectResponse
    {
        $this->imports->decline($request->user(), $import, $request->validated());

        return redirect()->route('hq-imports.show', $import)->with('success', __('Declined. The file was deleted and nothing was imported.'));
    }
}
