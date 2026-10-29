<?php

declare(strict_types=1);

namespace App\Services\Commerce;

use App\Models\Commerce\Ticket;
use App\Models\Commerce\TicketAction;
use App\Models\Commerce\TicketEvent;
use App\Models\Commerce\TicketType;
use App\Models\Commerce\Vendor;
use App\Models\Commerce\VendorOperation;
use App\Models\Commerce\VendorOrder;
use App\Models\Commerce\VendorOrderLine;
use App\Models\Platform\School;
use App\Models\Students;
use App\Models\User;
use App\Policies\Commerce\VendorPolicy;
use App\Policies\Money\WalletPolicy;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

final class TicketCounters
{
    public static function lookupRules(): array
    {
        return ['qr_token' => ['nullable', 'string', 'regex:/\\A[a-f0-9]{64}\\z/D', 'required_without:ticket_number'], 'ticket_number' => ['nullable', 'string', 'regex:/\\ATK-[A-Z0-9]{20}\\z/D', 'required_without:qr_token']];
    }

    public static function redeemRules(): array
    {
        return self::lookupRules() + ['operation_id' => ['required', 'uuid'], 'revision' => ['required', 'integer', 'min:0'], 'confirmed' => ['required', 'accepted']];
    }

    public function authority(User $actor, int $vendor, bool $write = false, bool $operator = true): void
    {
        if ($operator) {
            app(VendorOperatorAccess::class)->counter($actor, $vendor, $write);
        } else {
            app(VendorPolicy::class)->finance($actor, $write);
            Vendor::findOrFail($vendor);
        }
        app(TicketCatalogues::class)->module($write);
    }

    private function find(int $vendor, array $data): Ticket
    {
        abort_if(! empty($data['qr_token']) && ! empty($data['ticket_number']), 422, 'Scan the admission QR or type its current ticket number.');
        $field = ! empty($data['qr_token']) ? 'qr_hash' : 'number_hash';
        $hash = hash('sha256', $data[$field === 'qr_hash' ? 'qr_token' : 'ticket_number']);
        $root = Ticket::where(fn ($q) => $q->where($field, $hash)->orWhereIn('id', TicketAction::where($field, $hash)->select('ticket_id')))->whereIn('order_line_id', VendorOrderLine::whereIn('order_id', VendorOrder::where('vendor_id', $vendor)->select('id'))->select('id'))->lockForUpdate()->firstOrFail();
        $current = app(TicketHolders::class)->state($root)['credential'];
        abort_unless(hash_equals($current->getAttribute($field), $hash), 404);

        return $root;
    }

    private function usable(Ticket $ticket): bool
    {
        $type = TicketType::findOrFail($ticket->type_id);
        $event = TicketEvent::findOrFail($type->event_id);
        $state = app(TicketHolders::class)->state($ticket);
        if ($state['status'] !== 'valid' || $type->status !== 'active' || $event->status !== 'active' || ! $event->starts_at->lessThanOrEqualTo(now('UTC')) || ! $event->ends_at->greaterThan(now('UTC'))) {
            return false;
        }
        try {
            app(WalletPolicy::class)->activePerson(User::findOrFail($state['holder_id']));
            if ($ticket->named_learner_id !== null) {
                app(WalletPolicy::class)->activePerson(User::findOrFail(Students::findOrFail($ticket->named_learner_id)->user_id));
            }
        } catch (HttpExceptionInterface) {
            return false;
        }

        return true;
    }

    private function project(User $actor, int $vendor, Ticket $ticket): array
    {
        $state = app(TicketHolders::class)->state($ticket);
        $type = TicketType::findOrFail($ticket->type_id);
        $event = TicketEvent::findOrFail($type->event_id);
        $student = $ticket->named_learner_id === null ? null : User::findOrFail(Students::findOrFail($ticket->named_learner_id)->user_id);

        return ['school_id' => app(CurrentSchool::class)->id(), 'actor_user_id' => (int) $actor->id, 'vendor_id' => $vendor, 'id' => (int) $ticket->id, 'revision' => $state['revision'], 'status' => $state['status'], 'event_name' => $event->name, 'product_name' => $ticket->snapshot['product_name'], 'kind' => $type->kind, 'named_learner_name' => $student === null ? null : trim($student->first_name.' '.$student->last_name), 'starts_at' => $event->starts_at->utc()->format('Y-m-d\TH:i:s.v\Z'), 'ends_at' => $event->ends_at->utc()->format('Y-m-d\TH:i:s.v\Z'), 'can_redeem' => $this->usable($ticket) && app(ModuleGate::class)->writable('ticketing') && app(ModuleGate::class)->writable('vendors'), 'redeemed_at' => $state['status'] === 'redeemed' ? $state['last']->created_at->utc()->format('Y-m-d\TH:i:s.v\Z') : null, 'redeemed_by' => $state['status'] === 'redeemed' ? $state['last']->actor_id : null];
    }

    public function lookup(User $actor, int $vendor, array $input, bool $operator = true): array
    {
        $data = Validator::make($input, self::lookupRules())->validate();

        return DB::transaction(function () use ($actor, $vendor, $data, $operator): array {
            School::whereKey(app(CurrentSchool::class)->id())->lockForUpdate()->firstOrFail();
            $this->authority($actor, $vendor, false, $operator);

            return $this->project($actor, $vendor, $this->find($vendor, $data));
        });
    }

    public function resolve(User $actor, int $vendor, string $operation): array
    {
        Validator::make(['operation_id' => $operation], ['operation_id' => ['required', 'uuid']])->validate();

        return DB::transaction(function () use ($actor, $vendor, $operation): array {
            School::whereKey(app(CurrentSchool::class)->id())->lockForUpdate()->firstOrFail();
            $this->authority($actor, $vendor);
            $row = VendorOperation::where('operation_id', $operation)->lockForUpdate()->first();
            $receipt = null;
            if ($row === null) {
                VendorOperation::create(['actor_id' => $actor->id, 'operation_id' => $operation, 'request_hash' => hash_hmac('sha256', 'abandoned_ticket_admission:'.$vendor.':'.$operation, (string) config('app.key')), 'receipt' => ['abandoned' => true, 'vendor_id' => $vendor, 'purpose' => 'ticket_admission'], 'created_at' => now('UTC')]);
                $status = 'abandoned';
            } else {
                abort_unless($row->actor_id === (int) $actor->id, 404);
                if (($row->receipt['abandoned'] ?? false) === true) {
                    abort_unless(($row->receipt['vendor_id'] ?? null) === $vendor && ($row->receipt['purpose'] ?? null) === 'ticket_admission', 404);
                    $status = 'abandoned';
                } else {
                    $action = TicketAction::where('operation_id', $operation)->where('actor_id', $actor->id)->where('action', 'redeemed')->firstOrFail();
                    $ticket = Ticket::whereIn('order_line_id', VendorOrderLine::whereIn('order_id', VendorOrder::where('vendor_id', $vendor)->select('id'))->select('id'))->findOrFail($action->ticket_id);
                    $receipt = $this->project($actor, $vendor, $ticket);
                    $status = 'committed';
                }
            }

            return ['school_id' => app(CurrentSchool::class)->id(), 'actor_user_id' => (int) $actor->id, 'vendor_id' => $vendor, 'operation_id' => $operation, 'status' => $status, 'receipt' => $receipt];
        }, 3);
    }

    public function redeem(User $actor, int $vendor, array $input, bool $operator = true): array
    {
        $data = Validator::make($input, self::redeemRules())->validate();
        $data['revision'] = (int) $data['revision'];
        $data['confirmed'] = true;
        $data['vendor_id'] = $vendor;

        return DB::transaction(function () use ($actor, $vendor, $data, $operator): array {
            School::whereKey(app(CurrentSchool::class)->id())->lockForUpdate()->firstOrFail();
            $authorize = function () use ($actor, $vendor, $data, $operator): void {
                $this->authority($actor, $vendor, true, $operator);
                $this->find($vendor, $data);
            };
            $authorize();
            $replayed = VendorOperation::where('operation_id', $data['operation_id'])->exists();
            $receipt = app(VendorOperations::class)->once($actor, 'ticket_redeem', $data, function () use ($actor, $vendor, $data): array {
                $ticket = $this->find($vendor, $data);
                $state = app(TicketHolders::class)->state($ticket);
                abort_unless($state['status'] === 'valid' && $state['revision'] === $data['revision'], 409, 'This ticket changed or was already admitted. Refresh its original outcome; do not admit it twice.');
                abort_unless($this->usable($ticket), 409, 'Admission is unavailable under the current event window, holder or named learner authority.');
                TicketAction::create(['ticket_id' => $ticket->id, 'revision' => $state['revision'] + 1, 'action' => 'redeemed', 'actor_id' => $actor->id, 'from_holder_id' => $state['holder_id'], 'to_holder_id' => $state['holder_id'], 'operation_id' => $data['operation_id'], 'redemption_marker' => 1, 'created_at' => now('UTC')]);

                return ['id' => (int) $ticket->id];
            }, $authorize);

            return ['school_id' => app(CurrentSchool::class)->id(), 'actor_user_id' => (int) $actor->id, 'vendor_id' => $vendor, 'receipt' => $this->project($actor, $vendor, Ticket::findOrFail($receipt['id'])), 'replayed' => $replayed];
        }, 3);
    }
}
