<?php

declare(strict_types=1);

namespace App\Services\Platform\Support;

use App\Models\Platform\SchoolAccountEvent;
use App\Models\Platform\SupportTicket;
use App\Models\Platform\SupportTicketMessage;
use App\Models\User;
use App\Services\Platform\Audit\Audit;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * The school side of the support desk (Action Plan SA.17): the school's Super
 * Admin, or office staff with the school settings permission, open a ticket
 * to Navigate Ed, read their school's tickets and Navigate Ed's replies (never
 * HQ's internal notes), add a message and close a ticket. Every query runs in
 * the current school (BelongsToSchool), so another school's ticket is a 404.
 * Messages are encrypted at rest; the audit log records ids and states only.
 * engineering/specs/platform/support-desk.md.
 */
class SchoolSupportTickets
{
    public const PER_PAGE = 25;

    /** The same rule as Settings > Email from your domain: Super Admin, or office staff with setting-create. */
    public function authorise(?User $actor): void
    {
        abort_unless($actor !== null && ($actor->hasRole('Super Admin')
            || (! $actor->hasAnyRole(['Parent', 'Student', 'Teacher']) && $actor->can('setting-create'))), 403);
    }

    public static function openRules(): array
    {
        return [
            'subject' => ['required', 'string', 'min:5', 'max:150'],
            'category' => ['required', Rule::in(SupportTicket::CATEGORIES)],
            'priority' => ['required', Rule::in(SupportTicket::PRIORITIES)],
            'body' => ['required', 'string', 'min:10', 'max:5000'],
        ];
    }

    public static function messageRules(): array
    {
        return ['body' => ['required', 'string', 'min:2', 'max:5000']];
    }

    /** The school's tickets: those still being worked on first, then the latest. */
    public function list(User $actor): LengthAwarePaginator
    {
        $this->authorise($actor);

        return SupportTicket::query()
            ->orderByRaw("case when status in ('open', 'waiting_on_school') then 0 when status = 'resolved' then 1 else 2 end")
            ->orderByDesc('updated_at')->orderByDesc('id')
            ->paginate(self::PER_PAGE);
    }

    /**
     * One ticket and the messages the school may read: its own and Navigate
     * Ed's replies, never an internal note.
     *
     * @return array{ticket: SupportTicket, messages: list<array{side: string, author: ?string, body: string, at: mixed}>}
     */
    public function show(User $actor, int $ticketId): array
    {
        $this->authorise($actor);
        $ticket = SupportTicket::query()->findOrFail($ticketId);
        $messages = SupportTicketMessage::query()->where('ticket_id', $ticket->id)->where('internal', false)
            ->orderBy('created_at')->orderBy('id')->get();
        $names = $this->names($messages->where('side', 'school')->pluck('author_id'));

        return [
            'ticket' => $ticket,
            'messages' => $messages->map(fn (SupportTicketMessage $message) => [
                'side' => (string) $message->side,
                // Navigate Ed answers as a team: the school never sees which staff member wrote.
                'author' => $message->side === 'school' ? ($names[$message->author_id] ?? null) : null,
                'body' => (string) $message->body,
                'at' => $message->created_at,
            ])->values()->all(),
        ];
    }

    public function open(User $actor, array $input): SupportTicket
    {
        $this->authorise($actor);
        $data = Validator::make($input, self::openRules())->validate();

        return DB::transaction(function () use ($actor, $data): SupportTicket {
            $ticket = SupportTicket::query()->create([
                'subject' => $data['subject'], 'category' => $data['category'], 'priority' => $data['priority'],
                'status' => 'open', 'opened_by' => $actor->id,
            ]);
            $message = SupportTicketMessage::query()->create(['ticket_id' => $ticket->id, 'side' => 'school', 'internal' => false, 'body' => $data['body'], 'author_id' => $actor->id]);
            // HQ's timeline of the school (SA.4), in English like the rest of HQ: the reference and kind, never the school's words.
            SchoolAccountEvent::query()->create(['kind' => 'support', 'actor_id' => $actor->id,
                'body' => __('Support ticket :reference opened (:category, :priority).', ['reference' => $ticket->reference(),
                    'category' => mb_strtolower(SupportTicket::CATEGORY_LABELS[$ticket->category]), 'priority' => mb_strtolower(SupportTicket::PRIORITY_LABELS[$ticket->priority])], 'en')]);
            Audit::record('support_ticket_opened', null, [], ['ticket_id' => (int) $ticket->id, 'message_id' => (int) $message->id,
                'category' => $ticket->category, 'priority' => $ticket->priority, 'status' => 'open'], (int) $ticket->school_id);

            return $ticket;
        }, 3);
    }

    /** A message from the school. It puts a ticket waiting on the school, or resolved, back in Navigate Ed's queue. */
    public function addMessage(User $actor, int $ticketId, array $input): void
    {
        $this->authorise($actor);
        $data = Validator::make($input, self::messageRules())->validate();

        DB::transaction(function () use ($actor, $ticketId, $data): void {
            $ticket = SupportTicket::query()->lockForUpdate()->findOrFail($ticketId);
            abort_if($ticket->status === 'closed', 409, __('This ticket is closed. Open a new ticket if you still need help.'));
            $message = SupportTicketMessage::query()->create(['ticket_id' => $ticket->id, 'side' => 'school', 'internal' => false, 'body' => $data['body'], 'author_id' => $actor->id]);
            $was = $ticket->status;
            // A new message is activity on the ticket: HQ's queue shows it as new.
            $ticket->forceFill(['status' => 'open', 'updated_at' => now()])->save();
            Audit::record('support_ticket_message_added', null, ['status' => $was], ['ticket_id' => (int) $ticket->id, 'message_id' => (int) $message->id,
                'side' => 'school', 'internal' => false, 'status' => 'open'], (int) $ticket->school_id);
        }, 3);
    }

    public function close(User $actor, int $ticketId): void
    {
        $this->authorise($actor);

        DB::transaction(function () use ($actor, $ticketId): void {
            $ticket = SupportTicket::query()->lockForUpdate()->findOrFail($ticketId);
            abort_if($ticket->status === 'closed', 409, __('This ticket is already closed.'));
            $was = $ticket->status;
            $ticket->fill(['status' => 'closed', 'closed_at' => now(), 'closed_by' => $actor->id])->save();
            SchoolAccountEvent::query()->create(['kind' => 'support', 'actor_id' => $actor->id,
                'body' => __('Support ticket :reference closed by the school.', ['reference' => $ticket->reference()], 'en')]);
            Audit::record('support_ticket_closed', null, ['status' => $was], ['ticket_id' => (int) $ticket->id, 'status' => 'closed', 'side' => 'school'], (int) $ticket->school_id);
        }, 3);
    }

    /** @return array<int, string> */
    private function names(iterable $ids): array
    {
        $ids = collect($ids)->filter()->map(fn ($id) => (int) $id)->unique()->values();

        return User::acrossSchools()->withTrashed()->whereIn('id', $ids)->get(['id', 'first_name', 'last_name'])
            ->mapWithKeys(fn (User $user) => [(int) $user->id => trim($user->first_name.' '.$user->last_name)])->all();
    }
}
