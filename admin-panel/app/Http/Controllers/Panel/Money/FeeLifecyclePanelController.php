<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panel\Money;

use App\Http\Controllers\Controller;
use App\Http\Requests\Money\FeeLifecyclePanelRequest;
use App\Models\Money\FeeLifecyclePolicy;
use App\Models\SessionYear;
use App\Services\Money\FeeLifecyclePolicies;
use App\Services\Platform\Modules\ModuleGate;
use Symfony\Component\HttpFoundation\Response;

class FeeLifecyclePanelController extends Controller
{
    public function index(FeeLifecyclePanelRequest $request): Response
    {
        $year = SessionYear::where('default', 1)->first();
        $policy = $year === null ? null : FeeLifecyclePolicy::where('session_year_id', $year->id)->first();
        $canWrite = $year !== null && app(ModuleGate::class)->writable('fees');

        return response()->view('money.lifecycle.policy', compact('year', 'policy', 'canWrite'));
    }

    public function save(FeeLifecyclePanelRequest $request): Response
    {
        app(FeeLifecyclePolicies::class)->save($request->user(), $request->validated());

        return redirect()->route('fee-lifecycle.index', status: 303)->with('success', __('Fee enrolment policy saved. Prepare fresh bill reviews.'));
    }
}
