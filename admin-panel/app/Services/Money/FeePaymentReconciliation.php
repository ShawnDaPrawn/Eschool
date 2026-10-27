<?php

declare(strict_types=1);

namespace App\Services\Money;

use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;

/** Read-only domain checks, independent of cached account projections. */
final class FeePaymentReconciliation
{
    public function inspect(?array $accountIds = null): array
    {
        $school = app(CurrentSchool::class)->id();
        $paymentIds = DB::table('payments')->where('school_id', $school)->when($accountIds !== null, fn ($q) => $q->whereIn('fee_account_id', $accountIds))->select('id');
        $drafts = DB::table('payment_allocation_runs')->where('school_id', $school)->whereIn('payment_id', clone $paymentIds)->whereNull('sealed_at')->count();
        $badRuns = DB::table('payment_allocation_runs as r')->leftJoin('payment_allocations as a', function ($j) {
            $j->on('a.school_id', '=', 'r.school_id')->on('a.allocation_run_id', '=', 'r.id');
        })->where('r.school_id', $school)->whereIn('r.payment_id', clone $paymentIds)->whereNotNull('r.sealed_at')->groupBy('r.id', 'r.amount_cents')
            ->havingRaw('COALESCE(SUM(a.amount_cents),0) <> r.amount_cents')->pluck('r.id')->count();
        $credit = [];
        $totals = DB::table('payments')->where('school_id', $school)->when($accountIds !== null, fn ($q) => $q->whereIn('fee_account_id', $accountIds))->selectRaw('credit_account_id, SUM(amount_cents) AS cents')->groupBy('credit_account_id')->get();
        $used = DB::table('payment_allocations as a')->join('payments as p', function ($j) {
            $j->on('p.school_id', '=', 'a.school_id')->on('p.id', '=', 'a.payment_id');
        })->where('a.school_id', $school)->when($accountIds !== null, fn ($q) => $q->whereIn('p.fee_account_id', $accountIds))->selectRaw('p.credit_account_id, SUM(a.amount_cents) AS cents')->groupBy('p.credit_account_id')->pluck('cents', 'credit_account_id');
        $restored = FeeCreditHistory::releases()->when($accountIds !== null, fn ($q) => $q->whereIn('p.fee_account_id', $accountIds))->selectRaw('p.credit_account_id, SUM(c.amount_cents) AS cents')->groupBy('p.credit_account_id')->pluck('cents', 'credit_account_id');
        $accounts = DB::table('fee_accounts')->where('school_id', $school)->whereNotNull('ledger_account_id')->when($accountIds !== null, fn ($q) => $q->whereIn('id', $accountIds))->get(['id', 'ledger_account_id']);
        $ledgerIds = array_values(array_unique([...$totals->pluck('credit_account_id')->all(), ...$accounts->pluck('ledger_account_id')->all()]));
        $posted = DB::table('ledger_postings as p')->join('ledger_entries as e', function ($j) {
            $j->on('e.school_id', '=', 'p.school_id')->on('e.id', '=', 'p.entry_id');
        })->where('p.school_id', $school)->whereIn('p.account_id', $ledgerIds)->whereNotNull('e.posted_at')
            ->selectRaw("p.account_id, SUM(CASE WHEN p.direction = 'credit' THEN p.cents ELSE -p.cents END) AS cents")->groupBy('p.account_id')->pluck('cents', 'account_id');
        foreach ($totals as $total) {
            $id = (int) $total->credit_account_id;
            $expected = (int) $total->cents - (int) ($used[$id] ?? 0) + (int) ($restored[$id] ?? 0);
            $actual = (int) ($posted[$id] ?? 0);
            if ($actual !== $expected || $expected < 0) {
                $credit[] = ['account_id' => $id, 'expected_cents' => $expected, 'posting_cents' => $actual];
            }
        }
        $receivable = [];
        $invoices = DB::table('invoices')->where('school_id', $school)->when($accountIds !== null, fn ($q) => $q->whereIn('fee_account_id', $accountIds))->whereNotNull('sealed_at')->selectRaw('fee_account_id, SUM(total_cents) AS cents')->groupBy('fee_account_id')->pluck('cents', 'fee_account_id');
        $allocated = DB::table('payment_allocations as a')->join('payments as p', function ($j) {
            $j->on('p.school_id', '=', 'a.school_id')->on('p.id', '=', 'a.payment_id');
        })->where('a.school_id', $school)->when($accountIds !== null, fn ($q) => $q->whereIn('p.fee_account_id', $accountIds))->selectRaw('p.fee_account_id, SUM(a.amount_cents) AS cents')->groupBy('p.fee_account_id')->pluck('cents', 'fee_account_id');
        $credits = FeeCreditHistory::notes()->when($accountIds !== null, fn ($q) => $q->whereIn('n.fee_account_id', $accountIds))->selectRaw('n.fee_account_id, SUM(n.amount_cents) AS cents')->groupBy('n.fee_account_id')->pluck('cents', 'fee_account_id');
        $released = FeeCreditHistory::releases()->when($accountIds !== null, fn ($q) => $q->whereIn('p.fee_account_id', $accountIds))->selectRaw('p.fee_account_id, SUM(c.amount_cents) AS cents')->groupBy('p.fee_account_id')->pluck('cents', 'fee_account_id');
        foreach ($accounts as $account) {
            $expected = (int) ($invoices[$account->id] ?? 0) - (int) ($credits[$account->id] ?? 0) - (int) ($allocated[$account->id] ?? 0) + (int) ($released[$account->id] ?? 0);
            $actual = -(int) ($posted[$account->ledger_account_id] ?? 0);
            if ($actual !== $expected || $expected < 0) {
                $receivable[] = ['fee_account_id' => (int) $account->id, 'expected_cents' => $expected, 'posting_cents' => $actual];
            }
        }

        return ['incomplete_allocations' => $drafts, 'allocation_mismatches' => $badRuns, 'family_credit_differences' => $credit, 'fee_receivable_differences' => $receivable, ...app(FeeCreditReconciliation::class)->inspect($accountIds), ...app(FeeItemReconciliation::class)->inspect($accountIds)];
    }

    public static function healthy(array $report): bool
    {
        foreach ($report as $value) {
            if (is_array($value) ? $value !== [] : $value !== 0) {
                return false;
            }
        }

        return true;
    }
}
