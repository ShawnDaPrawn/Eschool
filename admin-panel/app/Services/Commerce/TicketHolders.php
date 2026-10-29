<?php

declare(strict_types=1);

namespace App\Services\Commerce;

use App\Models\Commerce\Ticket;
use App\Models\Commerce\TicketAction;
use App\Models\Commerce\TicketEvent;
use App\Models\Commerce\TicketType;
use App\Models\Platform\School;
use App\Models\Students;
use App\Models\User;
use App\Policies\Commerce\VendorPolicy;
use App\Policies\Money\WalletPolicy;
use App\Services\Money\WalletOperations;
use App\Services\Money\Wallets;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

final class TicketHolders
{
    public function actor(User $actor, bool $write = false): void
    {
        $fresh = app(VendorPolicy::class)->actor($actor, $write);
        app(WalletPolicy::class)->activePerson($fresh);
        app(TicketCatalogues::class)->module($write);
    }

    /** Internal snapshot; never expose credentials before fresh holder/counter authority. */
    public function state(Ticket $ticket): array
    {
        $last = TicketAction::where('ticket_id', $ticket->id)->orderByDesc('revision')->first();
        $credential = TicketAction::where('ticket_id', $ticket->id)->where('action', 'transferred')->orderByDesc('revision')->first() ?? $ticket;

        return ['holder_id' => $last?->getAttribute('to_holder_id') ?? $ticket->original_holder_id, 'revision' => $last?->getAttribute('revision') ?? 0, 'status' => $last?->action === 'redeemed' ? 'redeemed' : 'valid', 'last' => $last, 'credential' => $credential];
    }

    public function own(User $actor, int $id, bool $write = false): Ticket
    {
        $this->actor($actor, $write);
        $ticket = Ticket::lockForUpdate()->findOrFail($id);
        $holder = $this->state($ticket)['holder_id'];
        app(WalletPolicy::class)->owner($actor, $holder, $write);
        if ($ticket->named_learner_id !== null) {
            app(WalletPolicy::class)->owner($actor, (int) Students::findOrFail($ticket->named_learner_id)->user_id, $write);
        }

        return $ticket;
    }

    public function listing(User $actor, int $page = 1): array
    {
        return DB::transaction(function () use ($actor, $page): array {
            $this->actor($actor);
            $people = app(Wallets::class)->accessible($actor);
            $owners = array_map(fn ($owner) => (int) $owner['owner_user_id'], $people['owners']);
            // Current holder is the last immutable action's target, or the original holder.
            $ids = Ticket::where(fn ($q) => $q->whereNull('named_learner_id')->orWhereIn('named_learner_id', Students::whereIn('user_id', $owners)->select('id')))->whereRaw('COALESCE((SELECT a.to_holder_id FROM ticket_actions a WHERE a.school_id=tickets.school_id AND a.ticket_id=tickets.id ORDER BY a.revision DESC LIMIT 1),tickets.original_holder_id) IN ('.implode(',', array_fill(0, count($owners), '?')).')', $owners)->orderByDesc('id')->paginate(30, ['id'], 'page', $page);

            return ['school_id' => app(CurrentSchool::class)->id(), 'actor_user_id' => (int) $actor->id, 'items' => $ids->map(fn ($t) => $this->receipt($actor, (int) $t->id))->all(), 'page' => $ids->currentPage(), 'last_page' => $ids->lastPage()];
        });
    }

    public function receipt(User $actor, int $id): array
    {
        return DB::transaction(function () use ($actor, $id): array {
            $ticket = $this->own($actor, $id);
            $state = $this->state($ticket);
            $type = TicketType::findOrFail($ticket->type_id);
            $event = TicketEvent::findOrFail($type->event_id);
            $usable = $state['status'] === 'valid' && $event->status === 'active' && $type->status === 'active';
            $canTransfer = $usable && $type->transfer_rule !== 'none' && app(ModuleGate::class)->writable('ticketing') && app(ModuleGate::class)->writable('vendors') && app(ModuleGate::class)->writable('wallet') && $event->ends_at->isFuture();
            $learner = $ticket->named_learner_id === null ? null : User::findOrFail(Students::findOrFail($ticket->named_learner_id)->user_id);

            return ['id' => (int) $ticket->id, 'school_id' => app(CurrentSchool::class)->id(), 'actor_user_id' => (int) $actor->id, 'holder_user_id' => $state['holder_id'], 'revision' => $state['revision'], 'status' => $state['status'], 'can_transfer' => $canTransfer, 'event' => ['id' => (int) $event->id, 'name' => $event->name, 'description' => $event->description, 'status' => $event->status, 'starts_at' => $event->starts_at->utc()->format('Y-m-d\TH:i:s.v\Z'), 'ends_at' => $event->ends_at->utc()->format('Y-m-d\TH:i:s.v\Z')], 'type_id' => $type->id, 'kind' => $type->kind, 'transfer_rule' => $type->transfer_rule, 'product_name' => $ticket->snapshot['product_name'], 'currency' => $ticket->snapshot['currency'], 'unit_price_cents' => $ticket->snapshot['unit_price_cents'], 'order_number' => $ticket->snapshot['original_order_number'], 'named_learner_id' => $ticket->named_learner_id, 'named_learner_name' => $learner === null ? null : trim($learner->first_name.' '.$learner->last_name), 'qr' => $usable ? 'navigateed:ticket:'.$state['credential']->private_qr_token : null, 'ticket_number' => $usable ? $state['credential']->private_number : null, 'issued_at' => $ticket->created_at->utc()->format('Y-m-d\TH:i:s.v\Z'), 'redeemed_at' => $state['status'] === 'redeemed' ? $state['last']->created_at->utc()->format('Y-m-d\TH:i:s.v\Z') : null, 'redeemed_by' => $state['status'] === 'redeemed' ? $state['last']->actor_id : null];
        });
    }

    public function receiveCode(User $actor): array
    {
        return DB::transaction(function () use ($actor): array {
            $this->actor($actor, true);
            app(WalletPolicy::class)->owner($actor, (int) $actor->id, true);
            $expires = now('UTC')->addMinutes(5);
            $code = Crypt::encryptString(json_encode(['purpose' => 'ticket-recipient', 'school_id' => app(CurrentSchool::class)->id(), 'recipient_id' => (int) $actor->id, 'expires_at' => $expires->getTimestamp()], JSON_THROW_ON_ERROR));

            return ['school_id' => app(CurrentSchool::class)->id(), 'actor_user_id' => (int) $actor->id, 'receive_code' => $code, 'expires_at' => $expires->utc()->format('Y-m-d\TH:i:s.v\Z')];
        });
    }

    private function recipient(Ticket $ticket, TicketType $type, string $code): User
    {
        try {
            $value = json_decode(Crypt::decryptString($code), true, 32, JSON_THROW_ON_ERROR);
        } catch (DecryptException|\JsonException) {
            abort(422, 'Use a current receiving code from the intended person’s ticket wallet.');
        }
        abort_unless(is_array($value) && ($value['purpose'] ?? null) === 'ticket-recipient' && ($value['school_id'] ?? null) === app(CurrentSchool::class)->id() && is_int($value['recipient_id'] ?? null) && is_int($value['expires_at'] ?? null), 403);
        abort_unless($value['expires_at'] >= now('UTC')->getTimestamp(), 409, 'This receiving code expired. Ask the intended recipient for a current code.');
        $recipient = User::lockForUpdate()->findOrFail($value['recipient_id']);
        app(WalletPolicy::class)->owner($recipient, (int) $recipient->id, true);
        abort_unless($type->transfer_rule !== 'none', 422, 'The school has not enabled transfers for this ticket type.');
        $roles = app(WalletPolicy::class)->roles($recipient);
        abort_unless($type->transfer_rule === 'wallet_profiles' || array_intersect($roles, ['Parent', 'Student']) !== [], 422, 'This ticket type permits transfers only to current school families.');
        if ($ticket->named_learner_id !== null) {
            $student = Students::findOrFail($ticket->named_learner_id);
            app(WalletPolicy::class)->owner($recipient, (int) $student->user_id, true);
        }

        return $recipient;
    }

    public static function transferRules(): array
    {
        return ['operation_id' => ['required', 'uuid'], 'revision' => ['required', 'integer', 'min:0'], 'receive_code' => ['required', 'string', 'max:8192'], 'recipient_id' => ['required', 'integer', 'min:1'], 'confirmed' => ['required', 'accepted']];
    }

    public function previewTransfer(User $actor, int $id, string $code): array
    {
        Validator::make(['receive_code' => $code], ['receive_code' => ['required', 'string', 'max:8192']])->validate();

        return DB::transaction(function () use ($actor, $id, $code): array {
            School::whereKey(app(CurrentSchool::class)->id())->lockForUpdate()->firstOrFail();
            $ticket = $this->own($actor, $id, true);
            $state = $this->state($ticket);
            $type = TicketType::lockForUpdate()->findOrFail($ticket->type_id);
            $event = TicketEvent::lockForUpdate()->findOrFail($type->event_id);
            abort_unless($state['status'] === 'valid' && $type->status === 'active' && $event->status === 'active' && $event->ends_at->isFuture(), 409, 'Only a valid available admission can be transferred.');
            $recipient = $this->recipient($ticket, $type, $code);
            abort_if((int) $recipient->id === $state['holder_id'], 422, 'This person already holds the original ticket.');

            return ['school_id' => app(CurrentSchool::class)->id(), 'actor_user_id' => (int) $actor->id, 'ticket_id' => $id, 'revision' => $state['revision'], 'recipient_id' => (int) $recipient->id, 'recipient_name' => trim($recipient->first_name.' '.$recipient->last_name), 'kind' => $type->kind, 'event_name' => $event->name];
        });
    }

    /** Safe acknowledgement only: it never returns a new holder's ticket/QR to an old owner. */
    public function authorizeTransfer(User $actor, int $id, array $input): void
    {
        $this->actor($actor, true);
        $data = Validator::make($input, self::transferRules())->validate();
        $prior = TicketAction::where('operation_id', $data['operation_id'])->lockForUpdate()->first();
        if ($prior !== null) {
            abort_unless($prior->actor_id === (int) $actor->id && $prior->ticket_id === $id && $prior->action === 'transferred', 409);
            app(WalletPolicy::class)->owner($actor, $prior->from_holder_id, true);
            $original = Ticket::findOrFail($id);
            if ($original->named_learner_id !== null) {
                app(WalletPolicy::class)->owner($actor, (int) Students::findOrFail($original->named_learner_id)->user_id, true);
            }

            return;
        }
        $this->own($actor, $id, true);
    }

    public function transfer(User $actor, int $id, array $input): array
    {
        $data = Validator::make($input, self::transferRules())->validate();
        $data['revision'] = (int) $data['revision'];
        $data['recipient_id'] = (int) $data['recipient_id'];
        $data['confirmed'] = true;
        $data['ticket_id'] = $id;

        return app(WalletOperations::class)->once($actor, 'ticket_transfer', $data, fn () => $this->authorizeTransfer($actor, $id, $data), function () use ($actor, $id, $data): array {
            $preview = $this->previewTransfer($actor, $id, $data['receive_code']);
            abort_unless($preview['revision'] === $data['revision'] && $preview['recipient_id'] === $data['recipient_id'], 409, 'Ticket ownership or intended recipient changed. Review this transfer again.');
            $ticket = Ticket::findOrFail($id);
            $state = $this->state($ticket);
            TicketAction::create(TicketSales::credentials() + ['ticket_id' => $id, 'revision' => $state['revision'] + 1, 'action' => 'transferred', 'actor_id' => $actor->id, 'from_holder_id' => $state['holder_id'], 'to_holder_id' => $data['recipient_id'], 'operation_id' => $data['operation_id'], 'created_at' => now('UTC')]);

            return ['school_id' => app(CurrentSchool::class)->id(), 'actor_user_id' => (int) $actor->id, 'ticket_id' => $id, 'operation_id' => $data['operation_id'], 'revision' => $state['revision'] + 1, 'status' => 'transferred'];
        });
    }
}
