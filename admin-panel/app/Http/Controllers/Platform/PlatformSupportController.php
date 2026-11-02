<?php

declare(strict_types=1);

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Saas\SupportDeskRequest;
use App\Services\Platform\Saas\PlatformSupportDesk;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** HQ: the support desk queue and each ticket (Action Plan SA.17). */
class PlatformSupportController extends Controller
{
    public function __construct(private readonly PlatformSupportDesk $desk) {}

    public function index(Request $request): View
    {
        return view('saas.support', $this->desk->queue($request->user()));
    }

    public function show(Request $request, int $ticket): View
    {
        return view('saas.support-ticket', $this->desk->show($request->user(), $ticket));
    }

    public function message(SupportDeskRequest $request, int $ticket): RedirectResponse
    {
        $this->desk->addMessage($request->user(), $ticket, $request->validated());

        return redirect()->route('saas.support.show', $ticket)
            ->with('success', $request->validated('kind') === 'note' ? __('Internal note added.') : __('Reply sent to the school.'));
    }

    public function status(SupportDeskRequest $request, int $ticket): RedirectResponse
    {
        $this->desk->setStatus($request->user(), $ticket, $request->validated());

        return redirect()->route('saas.support.show', $ticket)->with('success', __('Status saved.'));
    }

    public function assignee(SupportDeskRequest $request, int $ticket): RedirectResponse
    {
        $this->desk->assign($request->user(), $ticket, $request->validated());

        return redirect()->route('saas.support.show', $ticket)->with('success', __('Assignment saved.'));
    }
}
