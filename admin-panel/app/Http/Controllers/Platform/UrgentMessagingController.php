<?php

declare(strict_types=1);

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\UrgentMessagingRequest;
use App\Services\Platform\Notifications\UrgentMessaging;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

/** Settings > SMS and WhatsApp: the school's opt-in for urgent notices (Action Plan 2.7). */
class UrgentMessagingController extends Controller
{
    public function index(UrgentMessagingRequest $request, UrgentMessaging $messaging): View
    {
        return view('platform.urgent-messaging', $messaging->overview());
    }

    public function update(UrgentMessagingRequest $request, UrgentMessaging $messaging): RedirectResponse
    {
        $messaging->change($request->user(), $request->validated());

        return redirect()->route('urgent-messaging.index')->with('success', __('SMS and WhatsApp settings saved.'));
    }
}
