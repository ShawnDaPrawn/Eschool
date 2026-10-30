<?php

declare(strict_types=1);

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\DismissAnnouncementRequest;
use App\Services\Platform\Announcements\SchoolAnnouncements;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** School panel: Navigate Ed's announcements to this school, for its Super Admins (SA.13). */
class SchoolAnnouncementController extends Controller
{
    public function __construct(private readonly SchoolAnnouncements $announcements) {}

    public function index(Request $request): View
    {
        return view('platform.announcements', ['announcements' => $this->announcements->page($request->user())]);
    }

    public function read(DismissAnnouncementRequest $request, int $announcement): RedirectResponse
    {
        $this->announcements->markRead($request->user(), $announcement);

        return redirect()->back(fallback: route('platform-announcements.index'))->with('success', __('Announcement dismissed.'));
    }
}
