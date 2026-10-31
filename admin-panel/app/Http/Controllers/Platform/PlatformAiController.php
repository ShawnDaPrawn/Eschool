<?php

declare(strict_types=1);

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Saas\AiFeatureSwitchRequest;
use App\Services\Platform\Saas\PlatformAiControls;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** HQ: the AI control centre (Action Plan SA.15). Aggregates and counts, never AI content. */
class PlatformAiController extends Controller
{
    public function __construct(private readonly PlatformAiControls $controls) {}

    public function index(Request $request): View
    {
        return view('saas.ai', $this->controls->overview($request->user(), (int) $request->query('page', 1)));
    }

    public function switch(AiFeatureSwitchRequest $request): RedirectResponse
    {
        $changed = $this->controls->switch($request->validated(), $request->user());

        return redirect()->route('saas.ai')->with('success', $changed ? 'AI feature switched.' : 'Nothing changed: the feature was already in that state.');
    }
}
