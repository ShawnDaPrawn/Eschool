<?php

declare(strict_types=1);

namespace App\Services\Commerce;

use App\Models\Commerce\Ticket;
use App\Models\Commerce\TicketEvent;
use App\Models\Commerce\TicketType;
use App\Models\Commerce\VendorOrder;
use App\Models\Commerce\VendorOrderAction;
use App\Models\Commerce\VendorOrderLine;
use App\Models\Commerce\VendorProduct;
use App\Models\Students;
use App\Models\User;
use App\Policies\Money\WalletPolicy;
use App\Services\Platform\Modules\ModuleGate;
use Illuminate\Support\Str;

final class TicketSales
{
    /** Called inside the original school-locked quoted checkout. */
    public function pricedLine(VendorProduct $product, int $quantity, ?int $learner): ?array
    {
        $type = TicketType::where('product_id', $product->id)->lockForUpdate()->first();
        if ($type === null) {
            return null;
        }
        app(TicketCatalogues::class)->module(true);
        $event = TicketEvent::lockForUpdate()->findOrFail($type->event_id);
        abort_unless($type->status === 'active' && $event->status === 'active', 409, 'These event admissions are not available for sale.');
        $now = now('UTC');
        abort_unless($event->sales_from->lessThanOrEqualTo($now) && $event->sales_until->greaterThan($now), 409, 'This event’s ticket sale window is closed.');
        abort_unless($quantity <= 100 && ! $product->tracks_stock, 422, 'Choose at most one hundred admissions per ticket type with explicit admission capacity.');
        abort_unless($type->kind !== 'named' || $learner !== null, 422, 'Choose the original authorised learner for this named admission.');
        if ($type->kind === 'named') {
            $subject = User::findOrFail(Students::findOrFail($learner)->user_id);
            abort_unless(in_array('Student', app(WalletPolicy::class)->roles($subject), true), 403);
            app(WalletPolicy::class)->activePerson($subject);
        }

        abort_unless(Ticket::where('type_id', $type->id)->count() + $quantity <= $type->capacity, 409, 'The requested event admission capacity is no longer available.');

        return ['type_id' => (int) $type->id, 'type_revision' => $type->revision, 'event_id' => $event->id, 'event_revision' => $event->revision, 'event_name' => $event->name, 'description' => $event->description, 'kind' => $type->kind, 'capacity' => $type->capacity, 'transfer_rule' => $type->transfer_rule, 'starts_at' => $event->starts_at->utc()->format('Y-m-d\TH:i:s.v\Z'), 'ends_at' => $event->ends_at->utc()->format('Y-m-d\TH:i:s.v\Z'), 'named_learner_id' => $type->kind === 'named' ? $learner : null];
    }

    public function catalogue(VendorProduct $product): ?array
    {
        $type = TicketType::where('product_id', $product->id)->first();
        if ($type === null) {
            return null;
        }
        $event = TicketEvent::findOrFail($type->event_id);
        $remaining = max(0, $type->capacity - Ticket::where('type_id', $type->id)->count());
        $canPurchase = $remaining > 0 && $type->status === 'active' && $event->status === 'active' && $event->sales_from->lessThanOrEqualTo(now('UTC')) && $event->sales_until->greaterThan(now('UTC')) && app(ModuleGate::class)->writable('ticketing');

        return ['type_id' => (int) $type->id, 'event_id' => (int) $event->id, 'event_name' => $event->name, 'kind' => $type->kind, 'transfer_rule' => $type->transfer_rule, 'remaining_capacity' => $remaining, 'quantity_limit' => min(100, $remaining), 'can_purchase' => $canPurchase, 'starts_at' => $event->starts_at->utc()->format('Y-m-d\TH:i:s.v\Z'), 'ends_at' => $event->ends_at->utc()->format('Y-m-d\TH:i:s.v\Z')];
    }

    public static function credentials(): array
    {
        $token = bin2hex(random_bytes(32));
        $number = 'TK-'.strtoupper(Str::random(20));

        return ['private_qr_token' => $token, 'qr_hash' => hash('sha256', $token), 'private_number' => $number, 'number_hash' => hash('sha256', $number)];
    }

    /** Digital delivery is explicit; original physical orders keep their existing flow. */
    public function issue(User $actor, VendorOrder $order, array $lines): void
    {
        $ticketLines = array_values(array_filter($lines, fn ($line) => isset($line['ticket'])));
        if ($ticketLines === []) {
            return;
        }
        abort_unless(count($ticketLines) === count($lines), 422, 'Buy digital admissions and physical goods in separate checkouts.');
        VendorOrderAction::create(['order_id' => $order->id, 'operation_id' => (string) Str::uuid(), 'actor_id' => $actor->id, 'action' => 'collected', 'created_at' => now('UTC')]);
        foreach ($ticketLines as $line) {
            $root = VendorOrderLine::where('order_id', $order->id)->where('product_id', $line['product_id'])->sole();
            for ($ordinal = 1; $ordinal <= $root->quantity; $ordinal++) {
                Ticket::create(self::credentials() + ['type_id' => $line['ticket']['type_id'], 'order_line_id' => $root->id, 'ordinal' => $ordinal, 'original_holder_id' => $order->wallet_owner_id, 'named_learner_id' => $line['ticket']['named_learner_id'], 'snapshot' => $line['ticket'] + ['product_name' => $root->product_name, 'currency' => $order->currency, 'unit_price_cents' => $root->unit_price_cents, 'original_order_number' => $order->order_number, 'vendor_id' => $order->vendor_id], 'created_at' => now('UTC')]);
            }
        }
    }
}
