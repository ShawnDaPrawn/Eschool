<?php

declare(strict_types=1);

namespace App\Services\Commerce;

use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class TicketReconciliation
{
    public function inspect(): array
    {
        $report = ['ticket_catalogue_history_mismatches' => 0, 'ticket_sale_mismatches' => 0, 'ticket_capacity_mismatches' => 0, 'ticket_history_mismatches' => 0];
        if (! Schema::hasTable('tickets')) {
            return $report;
        }
        $school = app(CurrentSchool::class)->id();
        foreach (['event' => 'ticket_events', 'type' => 'ticket_types'] as $kind => $table) {
            foreach (DB::table($table)->where('school_id', $school)->get(['id', 'revision']) as $record) {
                $history = DB::table('ticket_catalogue_revisions')->where('school_id', $school)->where('resource_type', $kind)->where('record_id', $record->id);
                if ($history->count() !== (int) $record->revision || (int) $history->max('revision') !== (int) $record->revision) {
                    $report['ticket_catalogue_history_mismatches']++;
                }
            }
        }
        foreach (DB::table('ticket_types')->where('school_id', $school)->get() as $type) {
            $product = DB::table('vendor_products')->where('school_id', $school)->where('id', $type->product_id)->first();
            $issued = DB::table('tickets')->where('school_id', $school)->where('type_id', $type->id)->count();
            if ($product === null || $product->tracks_stock || $issued > $type->capacity) {
                $report['ticket_capacity_mismatches']++;
            }
            foreach (DB::table('vendor_order_lines as l')->join('vendor_orders as o', fn ($j) => $j->on('o.school_id', '=', 'l.school_id')->on('o.id', '=', 'l.order_id'))->where('l.school_id', $school)->where('l.product_id', $type->product_id)->get(['l.*', 'o.wallet_owner_id', 'o.learner_id', 'o.order_number', 'o.vendor_id', 'o.sealed_at', 'o.currency']) as $line) {
                $rows = DB::table('tickets')->where('school_id', $school)->where('order_line_id', $line->id)->get();
                $collected = DB::table('vendor_order_actions')->where('school_id', $school)->where('order_id', $line->order_id)->where('action', 'collected')->exists();
                if ($line->sealed_at === null || ! $collected || $rows->count() !== (int) $line->quantity) {
                    $report['ticket_sale_mismatches']++;
                }
                foreach ($rows as $ticket) {
                    $snapshot = json_decode($ticket->snapshot, true, 512, JSON_THROW_ON_ERROR);
                    $named = $type->kind === 'named' ? (int) $line->learner_id : null;
                    $ticketNamed = $ticket->named_learner_id === null ? null : (int) $ticket->named_learner_id;
                    if ((int) $ticket->type_id !== (int) $type->id || (int) $ticket->original_holder_id !== (int) $line->wallet_owner_id || $ticketNamed !== $named || $snapshot['type_id'] !== (int) $type->id || $snapshot['event_id'] !== (int) $type->event_id || $snapshot['unit_price_cents'] !== (int) $line->unit_price_cents || $snapshot['currency'] !== $line->currency || $snapshot['original_order_number'] !== $line->order_number || $snapshot['vendor_id'] !== (int) $line->vendor_id) {
                        $report['ticket_sale_mismatches']++;
                    }
                    $holder = (int) $ticket->original_holder_id;
                    $revision = 0;
                    $redeemed = false;
                    foreach (DB::table('ticket_actions')->where('school_id', $school)->where('ticket_id', $ticket->id)->orderBy('revision')->get() as $action) {
                        $revision++;
                        if ($redeemed || (int) $action->revision !== $revision || (int) $action->from_holder_id !== $holder || ! in_array($action->action, ['transferred', 'redeemed'], true) || ($action->action === 'redeemed' && (int) $action->to_holder_id !== $holder)) {
                            $report['ticket_history_mismatches']++;
                        }
                        $holder = (int) $action->to_holder_id;
                        $redeemed = $action->action === 'redeemed';
                    }
                }
            }
        }

        return $report;
    }

    public static function healthy(array $report): bool
    {
        return $report['ticket_catalogue_history_mismatches'] === 0 && $report['ticket_sale_mismatches'] === 0 && $report['ticket_capacity_mismatches'] === 0 && $report['ticket_history_mismatches'] === 0;
    }
}
