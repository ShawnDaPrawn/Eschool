<?php

declare(strict_types=1);

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Saas\AnnouncementRequest;
use App\Services\Platform\Saas\PlatformAnnouncements;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

/** HQ: announcements to schools (Action Plan SA.13). */
class PlatformAnnouncementController extends Controller
{
    public function __construct(private readonly PlatformAnnouncements $announcements) {}

    public function index(): View
    {
        return view('saas.announcements', $this->announcements->list() + ['choices' => $this->announcements->choices()]);
    }

    public function store(AnnouncementRequest $request): RedirectResponse
    {
        $id = $this->announcements->draft($request->validated(), $request->user());

        return redirect()->route('saas.announcements.show', $id)->with('success', __('Draft saved. Check the preview and the schools it reaches, then publish it.'));
    }

    public function show(int $announcement): View
    {
        return view('saas.announcement', $this->announcements->show($announcement) + ['choices' => $this->announcements->choices()]);
    }

    public function update(AnnouncementRequest $request, int $announcement): RedirectResponse
    {
        $this->announcements->update($announcement, $request->validated(), $request->user());

        return redirect()->route('saas.announcements.show', $announcement)->with('success', __('Draft saved.'));
    }

    public function publish(AnnouncementRequest $request, int $announcement): RedirectResponse
    {
        $reach = $this->announcements->publish($announcement, $request->validated(), $request->user());

        return redirect()->route('saas.announcements.show', $announcement)->with('success', __('Published to :schools school(s); :admins Super Admin(s) notified.', ['schools' => $reach['schools'], 'admins' => $reach['recipients']]));
    }

    public function withdraw(AnnouncementRequest $request, int $announcement): RedirectResponse
    {
        $this->announcements->withdraw($announcement, $request->validated(), $request->user());

        return redirect()->route('saas.announcements.show', $announcement)->with('success', __('Withdrawn. Schools no longer see it.'));
    }
}
