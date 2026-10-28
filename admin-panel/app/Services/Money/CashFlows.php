<?php

declare(strict_types=1);

namespace App\Services\Money;

use App\Services\Platform\Tenancy\CurrentSchool;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/** Internal book/custody projection. Callers authorize Finance before exposing it. */
final class CashFlows
{
    public function inspect(string $currency): array
    {
        $school = app(CurrentSchool::class)->id();
        $tz = app(CurrentSchool::class)->school()->timezone ?? 'UTC';
        $accounts = DB::table('ledger_accounts')->where('school_id', $school)->where('currency', $currency)->where('type', 'cash_desk')->get()->keyBy('id');
        $raw = DB::table('ledger_postings as p')->join('ledger_entries as e', fn ($j) => $j->on('e.school_id', '=', 'p.school_id')->on('e.id', '=', 'p.entry_id'))->join('ledger_accounts as a', fn ($j) => $j->on('a.school_id', '=', 'p.school_id')->on('a.id', '=', 'p.account_id'))->where('p.school_id', $school)->where('a.currency', $currency)->where('a.type', 'cash_desk')->whereNotNull('e.posted_at')->get(['p.id', 'p.entry_id', 'p.account_id', 'p.cents', 'p.direction']);
        $postings = [];
        $balances = [];
        foreach ($raw as $p) {
            $postings[(int) $p->entry_id.':'.(int) $p->account_id][] = $p;
            $balances[(int) $p->account_id] = ($balances[(int) $p->account_id] ?? 0) + ($p->direction === 'debit' ? (int) $p->cents : -(int) $p->cents);
        }
        $remaining = $raw->pluck('id')->mapWithKeys(fn ($id) => [(int) $id => true])->all();
        $events = [];
        $bad = 0;
        $include = function (string $kind, object $row, int $sign) use (&$events, &$remaining, &$bad, $postings, $tz): void {
            $key = (int) $row->ledger_entry_id.':'.(int) $row->asset_account_id;
            $matches = $postings[$key] ?? [];
            if (count($matches) !== 1) {
                $bad++;

                return;
            }$p = $matches[0];
            if (! isset($remaining[(int) $p->id]) || (int) $p->cents !== (int) $row->amount_cents || $p->direction !== ($sign === 1 ? 'debit' : 'credit') || $row->operator_id === null) {
                $bad++;

                return;
            }
            unset($remaining[(int) $p->id]);
            $date = $row->effective_on ?? CarbonImmutable::parse($row->created_at, 'UTC')->setTimezone($tz)->toDateString();
            $events[] = ['kind' => $kind, 'record_id' => (int) $row->id, 'operator_id' => (int) $row->operator_id, 'effective_on' => $date, 'date_basis' => $row->effective_on === null ? 'recorded' : 'actual', 'amount_cents' => $sign * (int) $row->amount_cents, 'ledger_entry_id' => (int) $row->ledger_entry_id, 'asset_account_id' => (int) $row->asset_account_id];
        };
        foreach (DB::table('payments')->where('school_id', $school)->where('currency', $currency)->where('method', 'cash')->where('status', 'settled')->get(['id', 'created_by as operator_id', 'received_on as effective_on', 'amount_cents', 'ledger_entry_id', 'debit_account_id as asset_account_id', 'created_at']) as $r) {
            $include('fee_receipt', $r, 1);
        }
        foreach (DB::table('wallet_fundings')->where('school_id', $school)->where('currency', $currency)->where('method', 'cash')->get(['id', 'created_by as operator_id', 'received_on as effective_on', 'amount_cents', 'ledger_entry_id', 'asset_account_id', 'created_at']) as $r) {
            $include('school_wallet_funding', $r, 1);
        }
        foreach (DB::table('wallet_parent_deposits')->where('school_id', $school)->where('currency', $currency)->where('method', 'cash')->get(['id', 'cash_operator_id as operator_id', 'received_on as effective_on', 'amount_cents', 'ledger_entry_id', 'asset_account_id', 'created_at']) as $r) {
            $include('parent_wallet_receipt', $r, 1);
        }
        foreach (DB::table('fee_refund_actions as a')->join('fee_refunds as r', fn ($j) => $j->on('r.school_id', '=', 'a.school_id')->on('r.id', '=', 'a.fee_refund_id'))->where('a.school_id', $school)->where('a.currency', $currency)->where('r.method', 'cash')->where('a.action', 'paid')->select(['a.id', 'a.actor_id as operator_id', 'a.amount_cents', 'a.ledger_entry_id', 'a.counter_account_id as asset_account_id', 'a.created_at'])->selectRaw('NULL as effective_on')->get() as $r) {
            $include('fee_refund', $r, -1);
        }
        foreach (DB::table('wallet_withdrawal_actions as a')->join('wallet_withdrawals as w', fn ($j) => $j->on('w.school_id', '=', 'a.school_id')->on('w.id', '=', 'a.withdrawal_id'))->where('a.school_id', $school)->where('w.currency', $currency)->where('w.method', 'cash')->where('a.status', 'paid')->get(['a.id', 'a.cash_operator_id as operator_id', 'a.paid_on as effective_on', 'w.amount_cents', 'a.ledger_entry_id', 'a.counter_account_id as asset_account_id', 'a.created_at']) as $r) {
            $include('adult_wallet_payout', $r, -1);
        }
        foreach (DB::table('vendor_settlement_outcomes as a')->join('vendor_settlements as s', fn ($j) => $j->on('s.school_id', '=', 'a.school_id')->on('s.id', '=', 'a.settlement_id'))->where('a.school_id', $school)->where('s.currency', $currency)->where('s.method', 'cash')->where('s.payout_cents', '>', 0)->get(['a.id', 'a.cash_operator_id as operator_id', 'a.paid_on as effective_on', 's.payout_cents as amount_cents', 'a.ledger_entry_id', 'a.counter_account_id as asset_account_id', 'a.created_at']) as $r) {
            $include('vendor_payout', $r, -1);
        }
        $differences = [];
        foreach ($accounts as $a) {
            $actual = $balances[(int) $a->id] ?? 0;
            if ((int) $a->balance_cents !== $actual) {
                $differences[(int) $a->id] = ['cached_cents' => (int) $a->balance_cents, 'actual_cents' => $actual];
            }
        }
        usort($events, fn ($a, $b) => [$a['effective_on'], $a['kind'], $a['record_id']] <=> [$b['effective_on'], $b['kind'], $b['record_id']]);

        return ['events' => $events, 'cash_asset_cents' => array_sum($balances), 'unattributed_cash_postings' => count($remaining), 'cash_record_mismatches' => $bad, 'cash_projection_differences' => $differences];
    }

    public static function healthy(array $r): bool
    {
        return $r['unattributed_cash_postings'] === 0 && $r['cash_record_mismatches'] === 0 && $r['cash_projection_differences'] === [];
    }
}
