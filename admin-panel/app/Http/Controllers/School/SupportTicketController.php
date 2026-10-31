<?php

declare(strict_types=1);

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Http\Requests\School\SupportTicketRequest;
use App\Services\Platform\Support\SchoolSupportTickets;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

/** /support-tickets: the school asks Navigate Ed for help (Action Plan SA.17). */
class SupportTicketController extends Controller
{
    public function __construct(private readonly SchoolSupportTickets $tickets) {}

    public function index(SupportTicketRequest $request): View
    {
        return view('school.support-tickets', ['tickets' => $this->tickets->list($request->user())]);
    }

    public function store(SupportTicketRequest $request): RedirectResponse
    {
        $ticket = $this->tickets->open($request->user(), $request->validated());

        return redirect()->route('support-tickets.show', $ticket->id)
            ->with('success', __('Ticket :reference sent to Navigate Ed. You will be told here when they reply.', ['reference' => $ticket->reference()]));
    }

    public function show(SupportTicketRequest $request, int $ticket): View
    {
        return view('school.support-ticket', $this->tickets->show($request->user(), $ticket));
    }

    public function message(SupportTicketRequest $request, int $ticket): RedirectResponse
    {
        $this->tickets->addMessage($request->user(), $ticket, $request->validated());

        return redirect()->route('support-tickets.show', $ticket)->with('success', __('Message sent to Navigate Ed.'));
    }

    public function close(SupportTicketRequest $request, int $ticket): RedirectResponse
    {
        $this->tickets->close($request->user(), $ticket);

        return redirect()->route('support-tickets.show', $ticket)->with('success', __('Ticket closed.'));
    }
}
