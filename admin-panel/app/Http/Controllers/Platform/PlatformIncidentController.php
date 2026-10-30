<?php

declare(strict_types=1);

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Saas\IncidentRequest;
use App\Services\Platform\Saas\PlatformIncidents;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** HQ: the breach and incident register (Action Plan SA.12). */
class PlatformIncidentController extends Controller
{
    public function __construct(private readonly PlatformIncidents $incidents) {}

    public function index(): View
    {
        return view('saas.incidents', $this->incidents->list() + ['schoolChoices' => $this->incidents->schoolChoices()]);
    }

    public function store(IncidentRequest $request): RedirectResponse
    {
        $id = $this->incidents->open($request->validated(), $request->user());

        return redirect()->route('saas.incidents.show', $id)->with('success', __('Incident :reference recorded.', ['reference' => PlatformIncidents::reference($id)]));
    }

    public function show(Request $request, int $incident): View
    {
        return view('saas.incident', $this->incidents->show($incident, (int) $request->query('timeline', 1)) + ['schoolChoices' => $this->incidents->schoolChoices()]);
    }

    public function update(IncidentRequest $request, int $incident): RedirectResponse
    {
        $this->incidents->update($incident, $request->validated(), $request->user());

        return redirect()->route('saas.incidents.show', $incident)->with('success', __('Incident saved.'));
    }

    public function school(IncidentRequest $request, int $incident): RedirectResponse
    {
        $this->incidents->addSchool($incident, $request->validated(), $request->user());

        return redirect()->route('saas.incidents.show', $incident)->with('success', __('School added.'));
    }

    public function notified(IncidentRequest $request, int $incident, int $school): RedirectResponse
    {
        $this->incidents->recordNotified($incident, $school, $request->validated(), $request->user());

        return redirect()->route('saas.incidents.show', $incident)->with('success', __('Notification recorded.'));
    }

    public function entry(IncidentRequest $request, int $incident): RedirectResponse
    {
        $this->incidents->addEntry($incident, $request->validated(), $request->user());

        return redirect()->route('saas.incidents.show', $incident)->with('success', __('Added to the timeline.'));
    }
}
