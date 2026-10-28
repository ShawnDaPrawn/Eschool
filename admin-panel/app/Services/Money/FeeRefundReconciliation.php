<?php

declare(strict_types=1);

namespace App\Services\Money;

use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;

final class FeeRefundReconciliation
{
    public function inspect(?array $accountIds = null): array
    {
        $school = app(CurrentSchool::class)->id();
        $requests = DB::table('fee_refunds')->where('school_id', $school)->when($accountIds !== null, fn ($q) => $q->whereIn('fee_account_id', $accountIds))->get();
        $actions = DB::table('fee_refund_actions')->where('school_id', $school)->whereIn('fee_refund_id', $requests->pluck('id'))->orderBy('id')->get();
        $groups = $actions->groupBy('fee_refund_id');
        $invalid = [];
        $links = [];
        $pending = [];
        $postings = DB::table('ledger_postings as p')->join('ledger_entries as e', fn ($j) => $j->on('e.school_id', '=', 'p.school_id')->on('e.id', '=', 'p.entry_id'))->where('p.school_id', $school)->whereIn('p.entry_id', $actions->pluck('ledger_entry_id'))->whereNotNull('e.posted_at')->get(['p.entry_id', 'p.account_id', 'p.direction', 'p.cents', 'e.currency'])->groupBy('entry_id');
        foreach ($requests as $request) {
            $rows = $groups->get($request->id, collect());
            $reserved = $rows->where('action', 'reserved');
            $terminal = $rows->whereIn('action', ['paid', 'cancelled']);
            if (($rows->count() > 0 && $reserved->count() !== 1) || $terminal->count() > 1 || $rows->count() !== $reserved->count() + $terminal->count()) {
                $invalid[] = (int) $request->id;
            }
            $reserve = $reserved->first();
            if ($reserve !== null) {
                $pending[(int) $reserve->pending_account_id] = $terminal->isEmpty() ? (int) $request->amount_cents : 0;
            }
            foreach ($rows as $action) {
                $parts = $postings->get($action->ledger_entry_id, collect());
                $debit = $action->action === 'reserved' ? (int) $action->counter_account_id : (int) $action->pending_account_id;
                $credit = $action->action === 'reserved' ? (int) $action->pending_account_id : (int) $action->counter_account_id;
                if ($parts->count() !== 2 || (int) $action->amount_cents !== (int) $request->amount_cents || $action->currency !== $request->currency || (int) $parts->where('account_id', $debit)->where('direction', 'debit')->sum('cents') !== (int) $request->amount_cents || (int) $parts->where('account_id', $credit)->where('direction', 'credit')->sum('cents') !== (int) $request->amount_cents || $parts->contains(fn ($p) => $p->currency !== $request->currency)) {
                    $links[] = (int) $action->id;
                }
            }
        }
        $accounts = DB::table('ledger_accounts')->where('school_id', $school)->where('type', 'fee_refund')->when($accountIds !== null, fn ($q) => $q->whereIn('id', array_keys($pending)))->get(['id']);
        $balances = DB::table('ledger_postings as p')->join('ledger_entries as e', fn ($j) => $j->on('e.school_id', '=', 'p.school_id')->on('e.id', '=', 'p.entry_id'))->where('p.school_id', $school)->whereIn('p.account_id', $accounts->pluck('id'))->whereNotNull('e.posted_at')->selectRaw("p.account_id,SUM(CASE WHEN p.direction='credit' THEN p.cents ELSE -p.cents END) AS cents")->groupBy('p.account_id')->pluck('cents', 'account_id');
        $differences = [];
        foreach ($accounts as $account) {
            $expected = $pending[(int) $account->id] ?? 0;
            $actual = (int) ($balances[$account->id] ?? 0);
            if ($expected !== $actual || $actual < 0) {
                $differences[] = ['account_id' => (int) $account->id, 'expected_cents' => $expected, 'posting_cents' => $actual];
            }
        }

        return ['refund_state_mismatches' => array_values(array_unique($invalid)), 'refund_posting_mismatches' => array_values(array_unique($links)), 'refund_liability_differences' => $differences];
    }
}
