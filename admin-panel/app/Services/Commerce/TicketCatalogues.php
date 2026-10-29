<?php

declare(strict_types=1);

namespace App\Services\Commerce;

use App\Models\Commerce\Ticket;
use App\Models\Commerce\TicketCatalogueRevision;
use App\Models\Commerce\TicketEvent;
use App\Models\Commerce\TicketType;
use App\Models\Commerce\VendorOrderLine;
use App\Models\Commerce\VendorProduct;
use App\Models\User;
use App\Policies\Commerce\VendorPolicy;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Validator;

final class TicketCatalogues
{
    public function finance(User $actor, bool $write = false): void
    {
        app(VendorPolicy::class)->finance($actor, $write);
        $this->module($write);
    }

    public function module(bool $write = false): void
    {
        app(ModuleGate::class)->forget();
        abort_unless($write ? app(ModuleGate::class)->writable('ticketing', locked: true) : app(ModuleGate::class)->state('ticketing', locked: true)['enabled'], 403);
    }

    public static function eventRules(): array
    {
        $rules = ['operation_id' => ['required', 'uuid'], 'id' => ['sometimes', 'integer', 'min:1'], 'revision' => ['required', 'integer', 'min:0'], 'name' => ['required', 'string', 'max:191'], 'description' => ['nullable', 'string', 'max:4000'], 'status' => ['required', 'in:draft,active,paused,cancelled'], 'private_reason' => ['required', 'string', 'min:10', 'max:2000']];
        foreach (['sales_from', 'sales_until', 'starts_at', 'ends_at'] as $field) {
            $rules[$field] = ['required', 'date_format:Y-m-d\\TH:i'];
        }

        return $rules;
    }

    public static function typeRules(): array
    {
        return ['operation_id' => ['required', 'uuid'], 'id' => ['sometimes', 'integer', 'min:1'], 'revision' => ['required', 'integer', 'min:0'], 'event_id' => ['required', 'integer', 'min:1'], 'product_id' => ['required', 'integer', 'min:1'], 'kind' => ['required', 'in:general,named'], 'status' => ['required', 'in:draft,active,paused'], 'transfer_rule' => ['required', 'in:none,families,wallet_profiles'], 'capacity' => ['required', 'integer', 'between:1,1000000'], 'private_reason' => ['required', 'string', 'min:10', 'max:2000']];
    }

    public function event(User $actor, array $input): TicketEvent
    {
        $data = Validator::make($input, self::eventRules())->validate();
        $data['revision'] = (int) $data['revision'];
        if (isset($data['id'])) {
            $data['id'] = (int) $data['id'];
        }
        $receipt = app(VendorOperations::class)->once($actor, 'ticket_event', $data, function () use ($actor, $data): array {
            $row = isset($data['id']) ? TicketEvent::lockForUpdate()->findOrFail($data['id']) : new TicketEvent;
            abort_unless(($row->revision ?? 0) === $data['revision'], 409, 'This event changed. Review its current revision.');
            abort_if($row->exists && $row->status === 'cancelled', 409, 'The original cancelled event is retained. Configure a new event for a new admission.');
            $values = [];
            $tz = app(CurrentSchool::class)->school()->timezone ?? 'UTC';
            foreach (['sales_from', 'sales_until', 'starts_at', 'ends_at'] as $field) {
                $values[$field] = CarbonImmutable::createFromFormat('!Y-m-d\\TH:i', $data[$field], $tz)->utc();
            }
            abort_unless($values['sales_from']->lessThan($values['sales_until']) && $values['sales_until']->lessThanOrEqualTo($values['ends_at']) && $values['starts_at']->lessThan($values['ends_at']), 422, 'Review explicit sale and admission windows: sales and admission must end after they start, and sales end no later than admission.');
            $row->fill($values + ['name' => $data['name'], 'description' => $data['description'] ?? null, 'status' => $data['status'], 'revision' => $data['revision'] + 1, 'changed_by' => $actor->id])->save();
            TicketCatalogueRevision::create(['resource_type' => 'event', 'record_id' => $row->id, 'revision' => $row->revision, 'actor_id' => $actor->id, 'snapshot' => $row->attributesToArray(), 'private_reason' => $data['private_reason'], 'created_at' => now('UTC')]);
            Audit::record('tickets.event_saved', $row, [], ['revision' => $row->revision, 'status' => $row->status]);

            return ['id' => (int) $row->id];
        }, fn () => $this->finance($actor, true));

        return TicketEvent::findOrFail($receipt['id']);
    }

    public function type(User $actor, array $input): TicketType
    {
        $data = Validator::make($input, self::typeRules())->validate();
        foreach (['revision', 'event_id', 'product_id', 'capacity'] as $field) {
            $data[$field] = (int) $data[$field];
        }
        if (isset($data['id'])) {
            $data['id'] = (int) $data['id'];
        }
        $receipt = app(VendorOperations::class)->once($actor, 'ticket_type', $data, function () use ($actor, $data): array {
            $row = isset($data['id']) ? TicketType::lockForUpdate()->findOrFail($data['id']) : new TicketType;
            abort_unless(($row->revision ?? 0) === $data['revision'], 409, 'This ticket type changed. Review its current revision.');
            $event = TicketEvent::lockForUpdate()->findOrFail($data['event_id']);
            abort_if($event->status === 'cancelled', 422, 'A cancelled event cannot issue new admissions.');
            $product = VendorProduct::lockForUpdate()->findOrFail($data['product_id']);
            abort_if($product->tracks_stock, 422, 'Tickets use explicit admission capacity, not a physical stock adjustment.');
            abort_if(TicketType::where('product_id', $product->id)->when($row->exists, fn ($q) => $q->where('id', '<>', $row->id))->exists(), 409, 'This product already belongs to another ticket type.');
            if ($row->exists) {
                abort_unless($row->event_id === $data['event_id'] && $row->product_id === $data['product_id'] && $row->kind === $data['kind'], 422, 'Retain this ticket type’s original event, product and kind.');
                abort_unless($data['capacity'] >= Ticket::where('type_id', $row->id)->count(), 422, 'Capacity cannot fall below original issued tickets.');
            } else {
                abort_if(VendorOrderLine::where('product_id', $product->id)->exists(), 422, 'Choose a new product that has never been sold as physical goods.');
            }
            $row->fill(array_intersect_key($data, array_flip(['event_id', 'product_id', 'kind', 'status', 'transfer_rule', 'capacity'])) + ['revision' => $data['revision'] + 1, 'changed_by' => $actor->id])->save();
            TicketCatalogueRevision::create(['resource_type' => 'type', 'record_id' => $row->id, 'revision' => $row->revision, 'actor_id' => $actor->id, 'snapshot' => $row->attributesToArray(), 'private_reason' => $data['private_reason'], 'created_at' => now('UTC')]);
            Audit::record('tickets.type_saved', $row, [], ['revision' => $row->revision, 'capacity' => $row->capacity, 'transfer_rule' => $row->transfer_rule]);

            return ['id' => (int) $row->id];
        }, fn () => $this->finance($actor, true));

        return TicketType::findOrFail($receipt['id']);
    }
}
