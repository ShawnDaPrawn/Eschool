<?php

declare(strict_types=1);

namespace App\Services\Platform\Saas;

use App\Models\Platform\School;
use App\Models\Platform\SchoolAccountEvent;
use App\Models\Platform\SupportTicket;
use App\Models\Platform\SupportTicketMessage;
use App\Models\User;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Notifications\Notifier;
use App\Services\Platform\Tenancy\CurrentSchool;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * HQ's side of the support desk (Action Plan SA.17): the queue of open
 * tickets across schools (urgent first, then oldest), each ticket's thread,
 * replies the school reads, internal notes it never reads, the assignee and
 * the status. A reply tells the person who opened the ticket with a
 * school.support_reply notice that carries the subject only. Every write
 * runs inside the ticket's school, so its rows, timeline line and notice
 * belong to that school. The audit log records ids and states, never the
 * free text. engineering/specs/platform/support-desk.md.
 */
class PlatformSupportDesk
{
    public const ABILITY = 'support.manage';

    public const TIMEZONE = 'Africa/Johannesburg';

    public const NOTICE = 'school.support_reply';

    public const QUEUE_LIMIT = 200;

    public const RECENT_LIMIT = 50;

    public function __construct(private readonly CurrentSchool $currentSchool, private readonly PlatformStaffAccess $access) {}

    public static function messageRules(): array
    {
        return ['kind' => ['required', Rule::in(['reply', 'note'])], 'body' => ['required', 'string', 'min:2', 'max:5000']];
    }

    public static function statusRules(): array
    {
        return ['status' => ['required', Rule::in(SupportTicket::STATUSES)]];
    }

    public static function assigneeRules(): array
    {
        return ['assigned_to' => ['nullable', 'integer', 'min:1']];
    }

    /**
     * The queue (open and waiting on the school: urgent first, then oldest)
     * and the latest resolved or closed tickets. "New" marks a ticket that
     * changed since this staff member last opened the queue; opening it now
     * records the visit.
     *
     * @return array{queue: list<array<string, mixed>>, recent: list<array<string, mixed>>, lastSeen: ?string}
     */
    public function queue(User $viewer): array
    {
        $this->authorise($viewer);

        return $this->currentSchool->acrossSchools(function () use ($viewer): array {
            $seen = DB::table('platform_support_views')->where('user_id', $viewer->id)->value('seen_at');
            $seenAt = $seen === null ? null : CarbonImmutable::parse($seen);
            $base = fn () => SupportTicket::acrossSchools()->join('schools', 'schools.id', '=', 'school_support_tickets.school_id')
                ->select(['school_support_tickets.*', 'schools.name as school_name']);
            $queue = $base()->whereIn('school_support_tickets.status', SupportTicket::ACTIVE)
                ->orderByRaw("case when school_support_tickets.priority = 'urgent' then 0 else 1 end")->orderBy('school_support_tickets.created_at')->orderBy('school_support_tickets.id')
                ->limit(self::QUEUE_LIMIT)->get();
            $recent = $base()->whereNotIn('school_support_tickets.status', SupportTicket::ACTIVE)->orderByDesc('school_support_tickets.updated_at')->orderByDesc('school_support_tickets.id')
                ->limit(self::RECENT_LIMIT)->get();
            $names = $this->names($queue->pluck('assigned_to')->merge($recent->pluck('assigned_to')));
            $row = fn (SupportTicket $ticket): array => $this->row($ticket, $names) + [
                'new' => $seenAt === null || $ticket->updated_at->greaterThan($seenAt),
            ];
            DB::table('platform_support_views')->upsert([['user_id' => $viewer->id, 'seen_at' => now()]], ['user_id'], ['seen_at']);

            return [
                'queue' => $queue->map($row)->values()->all(),
                'recent' => $recent->map($row)->values()->all(),
                'lastSeen' => $seenAt?->setTimezone(self::TIMEZONE)->format('Y-m-d H:i'),
            ];
        });
    }

    /** One ticket, its whole thread with internal notes, and the staff it can be assigned to. */
    public function show(User $viewer, int $ticketId): array
    {
        $this->authorise($viewer);
        $ticket = $this->find($ticketId);

        return $this->currentSchool->run((int) $ticket->school_id, function () use ($ticket): array {
            $messages = SupportTicketMessage::query()->where('ticket_id', $ticket->id)->orderBy('created_at')->orderBy('id')->get();
            $names = $this->names($messages->pluck('author_id')->push($ticket->opened_by, $ticket->assigned_to, $ticket->closed_by));
            $ticket->setAttribute('school_name', (string) School::query()->whereKey($ticket->school_id)->value('name'));

            return [
                'ticket' => $this->row($ticket, $names) + [
                    'opened_by' => $names[$ticket->opened_by] ?? null,
                    'closed_by' => $names[$ticket->closed_by] ?? null,
                    'closed_at' => $this->local($ticket->closed_at),
                ],
                'messages' => $messages->map(fn (SupportTicketMessage $message) => [
                    'side' => (string) $message->side, 'internal' => (bool) $message->internal, 'body' => (string) $message->body,
                    'author' => $names[$message->author_id] ?? null, 'at' => $this->local($message->created_at),
                ])->values()->all(),
                'staff' => $this->assignable(),
            ];
        });
    }

    /** A reply the school reads (and its opener is told of), or an internal note it never sees. */
    public function addMessage(User $actor, int $ticketId, array $input): void
    {
        $this->authorise($actor);
        $data = Validator::make($input, self::messageRules())->validate();
        $ticket = $this->find($ticketId);
        $internal = $data['kind'] === 'note';

        $this->currentSchool->run((int) $ticket->school_id, fn () => DB::transaction(function () use ($actor, $ticket, $data, $internal): void {
            $ticket = SupportTicket::query()->lockForUpdate()->findOrFail($ticket->id);
            abort_if(! $internal && $ticket->status === 'closed', 409, __('Reopen the ticket before replying.'));
            $message = SupportTicketMessage::query()->create(['ticket_id' => $ticket->id, 'side' => 'hq', 'internal' => $internal, 'body' => $data['body'], 'author_id' => $actor->id]);
            $ticket->forceFill(['updated_at' => now()])->save();
            Audit::record('support_ticket_message_added', null, [], ['ticket_id' => (int) $ticket->id, 'message_id' => (int) $message->id,
                'side' => 'hq', 'internal' => $internal, 'status' => $ticket->status], (int) $ticket->school_id);
            if (! $internal && $ticket->opened_by !== null) {
                $this->notifyOpener($ticket);
            }
        }, 3));
    }

    public function setStatus(User $actor, int $ticketId, array $input): void
    {
        $this->authorise($actor);
        $data = Validator::make($input, self::statusRules())->validate();
        $ticket = $this->find($ticketId);

        $this->currentSchool->run((int) $ticket->school_id, fn () => DB::transaction(function () use ($actor, $ticket, $data): void {
            $ticket = SupportTicket::query()->lockForUpdate()->findOrFail($ticket->id);
            $was = $ticket->status;
            if ($was === $data['status']) {
                return;
            }
            $closing = $data['status'] === 'closed';
            $ticket->fill(['status' => $data['status'], 'closed_at' => $closing ? now() : null, 'closed_by' => $closing ? $actor->id : null])->save();
            if ($closing || $was === 'closed') {
                SchoolAccountEvent::query()->create(['kind' => 'support', 'actor_id' => $actor->id, 'body' => $closing
                    ? __('Support ticket :reference closed by Navigate Ed.', ['reference' => $ticket->reference()], 'en')
                    : __('Support ticket :reference reopened by Navigate Ed.', ['reference' => $ticket->reference()], 'en')]);
            }
            Audit::record('support_ticket_status_changed', null, ['status' => $was], ['ticket_id' => (int) $ticket->id, 'status' => $ticket->status, 'side' => 'hq'], (int) $ticket->school_id);
        }, 3));
    }

    /** Assigns the ticket to a platform staff member who may work the desk, or to nobody. */
    public function assign(User $actor, int $ticketId, array $input): void
    {
        $this->authorise($actor);
        $data = Validator::make($input, self::assigneeRules())->validate();
        $assignee = isset($data['assigned_to']) ? (int) $data['assigned_to'] : null;
        if ($assignee !== null && ! array_key_exists($assignee, $this->assignable())) {
            throw ValidationException::withMessages(['assigned_to' => __('Choose a member of platform staff who works the support desk.')]);
        }
        $ticket = $this->find($ticketId);

        $this->currentSchool->run((int) $ticket->school_id, fn () => DB::transaction(function () use ($ticket, $assignee): void {
            $ticket = SupportTicket::query()->lockForUpdate()->findOrFail($ticket->id);
            $was = $ticket->assigned_to;
            if ($was === $assignee) {
                return;
            }
            $ticket->fill(['assigned_to' => $assignee])->save();
            Audit::record('support_ticket_assigned', null, ['assigned_to' => $was], ['ticket_id' => (int) $ticket->id, 'assigned_to' => $assignee], (int) $ticket->school_id);
        }, 3));
    }

    /**
     * For HQ's school page: how many of the school's tickets are open, and its latest five.
     *
     * @return array{open: int, latest: list<array<string, mixed>>}
     */
    public function forSchool(int $schoolId): array
    {
        return $this->currentSchool->run($schoolId, function (): array {
            $latest = SupportTicket::query()->orderByDesc('updated_at')->orderByDesc('id')->limit(5)->get();

            return [
                'open' => SupportTicket::query()->whereIn('status', SupportTicket::ACTIVE)->count(),
                'latest' => $latest->map(fn (SupportTicket $ticket) => [
                    'id' => (int) $ticket->id, 'reference' => $ticket->reference(), 'subject' => (string) $ticket->subject,
                    'status' => (string) $ticket->status, 'priority' => (string) $ticket->priority, 'updated_at' => $this->local($ticket->updated_at),
                ])->values()->all(),
            ];
        });
    }

    /**
     * Platform staff whose current role may work the desk.
     *
     * @return array<int, string> user id => name
     */
    public function assignable(): array
    {
        return $this->currentSchool->acrossSchools(function (): array {
            $ids = DB::table('platform_staff_identities')->where('active', true)->pluck('user_id');

            return User::query()->whereIn('id', $ids)->where('status', 1)->orderBy('first_name')->orderBy('last_name')->get()
                ->filter(fn (User $user) => $this->access->can($user, self::ABILITY))
                ->mapWithKeys(fn (User $user) => [(int) $user->id => trim($user->first_name.' '.$user->last_name)])->all();
        });
    }

    private function authorise(User $actor): void
    {
        abort_unless($this->access->can($actor, self::ABILITY), 403);
    }

    private function find(int $ticketId): SupportTicket
    {
        // HQ reads every school's tickets: the queue is across schools by design.
        return SupportTicket::acrossSchools()->findOrFail($ticketId);
    }

    /**
     * The person who opened the ticket is told in the admin panel: the
     * subject only, never the message, and a link to the ticket.
     */
    private function notifyOpener(SupportTicket $ticket): void
    {
        $notifier = app(Notifier::class);
        $notice = $notifier->makeNotice(self::NOTICE, __('Navigate Ed replied to your support ticket'), (string) $ticket->subject, '/support-tickets/'.$ticket->id);
        $notifier->assign($notice, [(int) $ticket->opened_by]);
    }

    /** @param  array<int, string>  $names */
    private function row(SupportTicket $ticket, array $names): array
    {
        return [
            'id' => (int) $ticket->id, 'reference' => $ticket->reference(), 'subject' => (string) $ticket->subject,
            'school_id' => (int) $ticket->school_id, 'school' => (string) $ticket->getAttribute('school_name'),
            'category' => (string) $ticket->category, 'priority' => (string) $ticket->priority, 'status' => (string) $ticket->status,
            'assigned_to' => $ticket->assigned_to, 'assignee' => $names[$ticket->assigned_to] ?? null,
            'opened_at' => $this->local($ticket->created_at), 'updated_at' => $this->local($ticket->updated_at),
            'days_open' => (int) $ticket->created_at->diffInDays(now(), true),
        ];
    }

    private function local(mixed $value): ?string
    {
        return $value === null ? null : CarbonImmutable::parse($value)->setTimezone(self::TIMEZONE)->format('Y-m-d H:i');
    }

    /** @return array<int, string> */
    private function names(iterable $ids): array
    {
        $ids = collect($ids)->filter()->map(fn ($id) => (int) $id)->unique()->values();

        return User::acrossSchools()->withTrashed()->whereIn('id', $ids)->get(['id', 'first_name', 'last_name'])
            ->mapWithKeys(fn (User $user) => [(int) $user->id => trim($user->first_name.' '.$user->last_name)])->all();
    }
}
