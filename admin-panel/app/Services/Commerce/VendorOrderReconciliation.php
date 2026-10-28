<?php

declare(strict_types=1);

namespace App\Services\Commerce;

use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class VendorOrderReconciliation
{
    public function inspect(): array
    {
        $report = ['vendor_order_mismatches' => 0, 'vendor_order_stock_mismatches' => 0, 'unattributed_vendor_order_entries' => 0, 'vendor_payable_differences' => []] + app(WalletCounterReconciliation::class)->inspect();
        if (! Schema::hasTable('vendor_orders')) {
            return $report + app(VendorSettlementReconciliation::class)->inspect() + app(TicketReconciliation::class)->inspect();
        }
        $school = app(CurrentSchool::class)->id();
        foreach (DB::table('vendor_orders')->where('school_id', $school)->orderBy('id')->get() as $order) {
            $vendor = DB::table('vendors')->where('school_id', $school)->where('id', $order->vendor_id)->first();
            $wallet = DB::table('wallet_accounts')->where('school_id', $school)->where('id', $order->wallet_account_id)->first();
            $lines = DB::table('vendor_order_lines')->where('school_id', $school)->where('order_id', $order->id)->get();
            $action = DB::table('vendor_order_actions')->where('school_id', $school)->where('order_id', $order->id)->first();
            $sound = $order->sealed_at !== null && $vendor !== null && $wallet !== null && (int) $wallet->user_id === (int) $order->wallet_owner_id && $wallet->currency === $order->currency && $vendor->currency === $order->currency && $lines->count() >= 1 && $lines->count() <= 20 && (int) $lines->sum('gross_cents') === (int) $order->amount_cents && (int) $lines->sum('vat_cents') === (int) $order->vat_cents && (int) $lines->sum('net_cents') === (int) $order->net_cents;
            if ($vendor !== null && $wallet !== null) {
                $sound = $sound && $this->entry($order->ledger_entry_id, (int) $wallet->ledger_account_id, (int) $vendor->ledger_account_id, (int) $order->amount_cents, $order->currency, 'vendor:order:'.$order->operation_id);
                if ($action?->action === 'cancelled') {
                    $sound = $sound && $this->entry($action->ledger_entry_id, (int) $vendor->ledger_account_id, (int) $wallet->ledger_account_id, (int) $order->amount_cents, $order->currency, 'vendor:cancel:'.$order->id);
                }
            }
            if ($action !== null && ! in_array($action->action, ['collected', 'cancelled'], true)) {
                $sound = false;
            }
            if (! $sound) {
                $report['vendor_order_mismatches']++;
            }
            foreach ($lines as $line) {
                $events = DB::table('vendor_stock_events')->where('school_id', $school)->where('order_line_id', $line->id)->get();
                $purchases = $events->where('kind', 'purchase');
                $cancellations = $events->where('kind', 'cancellation');
                $stockSound = ! $line->tracks_stock ? $events->isEmpty() : $purchases->count() === 1 && $purchases->every(fn ($e) => (int) $e->product_id === (int) $line->product_id && (int) $e->units_delta === -(int) $line->quantity);
                if ($line->tracks_stock) {
                    $stockSound = $stockSound && ($action?->action === 'cancelled' ? $cancellations->count() === 1 && $cancellations->every(fn ($e) => (int) $e->order_action_id === (int) $action->id && (int) $e->product_id === (int) $line->product_id && (int) $e->units_delta === (int) $line->quantity) : $cancellations->isEmpty());
                }
                if (! $stockSound) {
                    $report['vendor_order_stock_mismatches']++;
                }
            }
        }
        $report['unattributed_vendor_order_entries'] = DB::table('ledger_entries as e')->where('e.school_id', $school)->where(function ($q) {
            $q->where(fn ($q) => $q->where('e.source', 'like', 'vendor:order:%')->whereNotExists(fn ($s) => $s->selectRaw('1')->from('vendor_orders as o')->whereColumn('o.school_id', 'e.school_id')->whereColumn('o.ledger_entry_id', 'e.id')))
                ->orWhere(fn ($q) => $q->where('e.source', 'like', 'vendor:cancel:%')->whereNotExists(fn ($s) => $s->selectRaw('1')->from('vendor_order_actions as a')->whereColumn('a.school_id', 'e.school_id')->whereColumn('a.ledger_entry_id', 'e.id')->where('a.action', 'cancelled')));
        })->count();
        foreach (DB::table('vendors')->where('school_id', $school)->get() as $vendor) {
            $expected = (int) DB::table('vendor_orders as o')->where('o.school_id', $school)->where('o.vendor_id', $vendor->id)->whereNotNull('o.sealed_at')->whereNotExists(fn ($q) => $q->selectRaw('1')->from('vendor_order_actions as a')->whereColumn('a.school_id', 'o.school_id')->whereColumn('a.order_id', 'o.id')->where('a.action', 'cancelled'))->sum('o.amount_cents');
            if (Schema::hasTable('vendor_settlements')) {
                $expected -= (int) DB::table('vendor_settlements as s')->where('s.school_id', $school)->where('s.vendor_id', $vendor->id)->whereExists(fn ($q) => $q->selectRaw('1')->from('vendor_settlement_outcomes as o')->whereColumn('o.school_id', 's.school_id')->whereColumn('o.settlement_id', 's.id'))->sum('s.gross_cents');
            }
            $actual = (int) DB::table('ledger_postings as p')->join('ledger_entries as e', fn ($j) => $j->on('e.school_id', '=', 'p.school_id')->on('e.id', '=', 'p.entry_id'))->where('p.school_id', $school)->where('p.account_id', $vendor->ledger_account_id)->whereNotNull('e.posted_at')->selectRaw("COALESCE(SUM(CASE p.direction WHEN 'credit' THEN p.cents ELSE -p.cents END),0) AS total")->value('total');
            if ($expected !== $actual) {
                $report['vendor_payable_differences'][(int) $vendor->id] = ['expected_cents' => $expected, 'actual_cents' => $actual];
            }
        }

        return $report + app(VendorSettlementReconciliation::class)->inspect() + app(TicketReconciliation::class)->inspect();
    }

    private function entry(?int $id, int $debit, int $credit, int $cents, string $currency, string $source): bool
    {
        if ($cents === 0) {
            return $id === null;
        }
        $school = app(CurrentSchool::class)->id();
        $entry = DB::table('ledger_entries')->where('school_id', $school)->where('id', $id)->where('source', $source)->where('currency', $currency)->whereNotNull('posted_at')->first();
        $postings = DB::table('ledger_postings')->where('school_id', $school)->where('entry_id', $id)->get();

        return $entry !== null && $postings->count() === 2
            && $postings->contains(fn ($p) => (int) $p->account_id === $debit && $p->direction === 'debit' && (int) $p->cents === $cents)
            && $postings->contains(fn ($p) => (int) $p->account_id === $credit && $p->direction === 'credit' && (int) $p->cents === $cents);
    }

    public static function healthy(array $report): bool
    {
        return WalletCounterReconciliation::healthy($report) && TicketReconciliation::healthy($report) && VendorSettlementReconciliation::healthy($report) && $report['vendor_order_mismatches'] === 0 && $report['vendor_order_stock_mismatches'] === 0 && $report['unattributed_vendor_order_entries'] === 0 && $report['vendor_payable_differences'] === [];
    }
}
