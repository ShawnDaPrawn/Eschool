<?php

declare(strict_types=1);

namespace App\Services\Activities\Chat;

use App\Services\Panel\SessionYearContext;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;

/** Panel conversation rights come from dated allocations, never group management. */
class GroupChatPanelScope
{
    public function authorize(Request $request): bool
    {
        $access = app(GroupChatAccess::class);
        $actor = $access->actor($request->user(), 'staff');
        if (! $request->isMethodSafe() || $request->routeIs('group-chat-panel.lease')) {
            abort_unless((int) $request->input('expected_account_id') === (int) $actor->id
                && (int) $request->input('expected_school_id') === app(CurrentSchool::class)->id(), 403);
        }
        if (! $request->isMethodSafe()) {
            abort_if($request->query->count() > 0, 422);
        }
        if ($request->route('group') !== null) {
            $access->group($actor, (int) $request->route('group'), 'staff');
            if ($request->route('thread') !== null) {
                $access->thread($actor, (int) $request->route('group'), (string) $request->route('thread'), 'staff');
            }
        }
        // Refuse all panel writes in a selected historical year, including
        // moderation, without invoking Laravel's plain old-input flash.
        if (! $request->isMethodSafe()) {
            $years = app(SessionYearContext::class);
            if ($years->isReadOnly()) {
                // A generic debug exception page includes request input. Return
                // the private refusal document directly, without message text.
                throw new HttpResponseException(response()->view('errors.private-year-refusal', [
                    'message' => trans('read_only_year_refused', ['year' => $years->selected()?->name, 'current' => $years->current()?->name]),
                    'title' => __('Group conversations'), 'draft' => [], 'returnUrl' => route('group-chat-panel.index'),
                ], 423)->header('Cache-Control', 'no-store, private'));
            }
        }

        return true;
    }
}
