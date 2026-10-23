<?php

declare(strict_types=1);

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\NotificationRuleRequest;
use App\Services\Platform\Notifications\NotificationRules;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class NotificationRuleController extends Controller
{
    public function index(NotificationRuleRequest $request, NotificationRules $rules): View
    {
        $portal = $request->validated('portal', 'parent');
        $types = [];
        foreach (config('notification_types') as $type => $definition) {
            if (in_array($portal, $definition['portals'], true)) {
                $types[$type] = $definition + ['modes' => array_combine(NotificationRules::CHANNELS, array_map(fn ($channel) => $rules->mode($portal, $type, $channel), NotificationRules::CHANNELS))];
            }
        }

        return view('platform.notification-rules', compact('portal', 'types'));
    }

    public function update(NotificationRuleRequest $request, NotificationRules $rules): RedirectResponse
    {
        $rules->change($request->user(), $request->validated('portal'), $request->validated('type'), $request->validated('channel'), $request->validated('mode'));

        return redirect()->route('notification-rules.index', ['portal' => $request->validated('portal')])->with('success', __('Notification rule updated.'));
    }
}
