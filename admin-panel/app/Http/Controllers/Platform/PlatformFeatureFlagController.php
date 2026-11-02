<?php

declare(strict_types=1);

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Saas\FeatureFlagRequest;
use App\Services\Platform\Saas\PlatformFeatureFlags;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

/** HQ: feature flags and staged rollout (Action Plan SA.14). */
class PlatformFeatureFlagController extends Controller
{
    public function __construct(private readonly PlatformFeatureFlags $flags) {}

    public function index(): View
    {
        return view('saas.flags', ['flags' => $this->flags->list(), 'schoolChoices' => $this->flags->schoolChoices()]);
    }

    public function store(FeatureFlagRequest $request): RedirectResponse
    {
        $this->flags->create($request->validated(), $request->user());

        return redirect()->route('saas.flags.index')->with('success', __('Flag created. It is off until you roll it out.'));
    }

    public function update(FeatureFlagRequest $request, int $flag): RedirectResponse
    {
        $this->flags->update($flag, $request->validated(), $request->user());

        return redirect()->route('saas.flags.index')->with('success', __('Flag saved.'));
    }

    public function school(FeatureFlagRequest $request, int $flag): RedirectResponse
    {
        $this->flags->switchSchool($flag, $request->validated(), $request->user());

        return redirect()->route('saas.flags.index')->with('success', __('School switch saved.'));
    }
}
