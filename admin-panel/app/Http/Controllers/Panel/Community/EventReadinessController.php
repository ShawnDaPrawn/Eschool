<?php

declare(strict_types=1);

namespace App\Http\Controllers\Panel\Community;

use App\Http\Controllers\Controller;
use App\Http\Requests\Community\EventReadinessRequest;
use App\Services\Community\EventReadiness;
use App\Services\Panel\SessionYearContext;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Http\Request;

class EventReadinessController extends Controller
{
    public function show(Request $request, int $event, EventReadiness $service)
    {
        $request->validate(['page' => 'sometimes|integer|between:1,100000', 'choice_page' => 'sometimes|integer|between:1,100000', 'activity_group_id' => 'sometimes|nullable|integer|min:1',
            'ticket_page' => 'sometimes|integer|between:1,100000', 'ticket_search' => 'sometimes|nullable|string|max:191']);
        $model = $service->read($request->user(), $event, $request->integer('page', 1));
        $view = $request->query('view') === 'links' ? 'links' : 'overview';
        $canWrite = $request->user()->can('event-edit') && ! app(SessionYearContext::class)->isReadOnly() && (int) $model['event']->session_year_id === (int) schoolSetting('session_year');
        $group = $request->integer('activity_group_id') ?: ($model['consent']['activity_group_id'] ?? null);
        $choices = $view === 'links' && $canWrite ? $service->choices($request->user(), $group, $request->integer('choice_page', 1), $request->integer('ticket_page', 1), (string) $request->input('ticket_search', '')) : null;

        return response()->view('events.readiness', $model + compact('view', 'canWrite', 'choices', 'group') + ['account' => $request->user()->id, 'school' => app(CurrentSchool::class)->id()]);
    }

    public function save(EventReadinessRequest $request, int $event, EventReadiness $service)
    {
        $service->save($request->user(), $event, $request->validated());

        return response()->json(['error' => false, 'message' => __('Event links saved.'), 'redirect' => route('event-readiness.show', $event)]);
    }
}
