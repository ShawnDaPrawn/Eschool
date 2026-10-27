<?php

declare(strict_types=1);

namespace App\Services\Money;

use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;

final class FeeItemReconciliation
{
    public function inspect(?array $accountIds = null): array
    {
        $school = app(CurrentSchool::class)->id();
        $charges = DB::table('fee_charges as c')->where('c.school_id', $school)->when($accountIds !== null, fn ($q) => $q->whereIn('c.fee_account_id', $accountIds));
        $drafts = (clone $charges)->whereNull('c.sealed_at')->count();
        $badInvoices = (clone $charges)->leftJoin('invoices as i', fn ($j) => $j->on('i.school_id', '=', 'c.school_id')->on('i.bill_run_id', '=', 'c.bill_run_id'))->leftJoin('bill_runs as b', fn ($j) => $j->on('b.school_id', '=', 'c.school_id')->on('b.id', '=', 'c.bill_run_id'))->whereNotNull('c.sealed_at')
            ->where(fn ($q) => $q->whereNull('i.id')->orWhereNull('i.sealed_at')->orWhere('b.kind', '<>', 'fee_item')->orWhere('b.state', '<>', 'posted')->orWhereColumn('i.fee_account_id', '<>', 'c.fee_account_id')->orWhereColumn('i.session_year_id', '<>', 'c.session_year_id')->orWhereColumn('i.currency', '<>', 'c.currency')->orWhereColumn('i.total_cents', '<>', 'c.amount_cents'))->count();
        $badTotals = (clone $charges)->leftJoin('invoice_lines as l', fn ($j) => $j->on('l.school_id', '=', 'c.school_id')->on('l.fee_charge_id', '=', 'c.id'))->whereNotNull('c.sealed_at')->groupBy('c.id', 'c.amount_cents')->havingRaw('COALESCE(SUM(l.amount_cents),0) <> c.amount_cents')->pluck('c.id')->count();
        $badLinks = DB::table('invoice_lines as l')->join('invoices as i', fn ($j) => $j->on('i.school_id', '=', 'l.school_id')->on('i.id', '=', 'l.invoice_id'))->join('fee_charges as c', fn ($j) => $j->on('c.school_id', '=', 'l.school_id')->on('c.id', '=', 'l.fee_charge_id'))->where('l.school_id', $school)->when($accountIds !== null, fn ($q) => $q->whereIn('c.fee_account_id', $accountIds))->whereNotNull('c.sealed_at')->where(fn ($q) => $q->whereColumn('i.bill_run_id', '<>', 'c.bill_run_id')->orWhereColumn('i.fee_account_id', '<>', 'c.fee_account_id')->orWhereColumn('l.session_year_id', '<>', 'c.session_year_id'))->count();

        return ['incomplete_fee_charges' => $drafts, 'fee_item_charge_mismatches' => $badInvoices + $badTotals + $badLinks];
    }
}
