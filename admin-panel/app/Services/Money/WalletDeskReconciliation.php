<?php

declare(strict_types=1);

namespace App\Services\Money;

use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;

final class WalletDeskReconciliation
{
    public function inspect(): array
    {
        $school = app(CurrentSchool::class)->id();
        $reserved = DB::table('wallet_desk_topups as t')->leftJoin('ledger_postings as p', function ($j) {
            $j->on('p.school_id', '=', 't.school_id')->on('p.entry_id', '=', 't.reserve_entry_id');
        })->leftJoin('ledger_accounts as a', function ($j) {
            $j->on('a.school_id', '=', 'p.school_id')->on('a.id', '=', 'p.account_id');
        })->leftJoin('ledger_entries as e', function ($j) {
            $j->on('e.school_id', '=', 't.school_id')->on('e.id', '=', 't.reserve_entry_id');
        })->where('t.school_id', $school)->groupBy('t.id')->havingRaw("MAX(e.posted_at) IS NULL OR MAX(e.currency)<>MAX(t.currency) OR COUNT(p.id)<>2 OR COALESCE(SUM(CASE WHEN p.direction='debit' AND a.id=t.master_account_id AND a.type='wallet' AND a.module='wallet' THEN p.cents ELSE 0 END),0)<>MAX(t.amount_cents) OR COALESCE(SUM(CASE WHEN p.direction='credit' AND a.id=t.reserve_account_id AND a.type='wallet' AND a.module='wallet' THEN p.cents ELSE 0 END),0)<>MAX(t.amount_cents)")->pluck('t.id')->count();
        $pending = DB::table('wallet_desk_topups as t')->leftJoin('wallet_desk_actions as d', function ($j) {
            $j->on('d.school_id', '=', 't.school_id')->on('d.topup_id', '=', 't.id');
        })->leftJoin('ledger_postings as p', function ($j) {
            $j->on('p.school_id', '=', 't.school_id')->on('p.account_id', '=', 't.reserve_account_id');
        })->leftJoin('ledger_entries as e', function ($j) {
            $j->on('e.school_id', '=', 'p.school_id')->on('e.id', '=', 'p.entry_id');
        })->where('t.school_id', $school)->groupBy('t.id')->havingRaw("COALESCE(SUM(CASE WHEN e.posted_at IS NOT NULL THEN CASE p.direction WHEN 'credit' THEN p.cents ELSE -p.cents END ELSE 0 END),0)<>IF(MAX(d.id) IS NULL,MAX(t.amount_cents),0)")->pluck('t.id')->count();
        $terminal = DB::table('wallet_desk_actions as d')->join('wallet_desk_topups as t', function ($j) {
            $j->on('t.school_id', '=', 'd.school_id')->on('t.id', '=', 'd.topup_id');
        })->join('wallet_accounts as w', function ($j) {
            $j->on('w.school_id', '=', 't.school_id')->on('w.id', '=', 't.wallet_account_id');
        })->leftJoin('ledger_postings as p', function ($j) {
            $j->on('p.school_id', '=', 'd.school_id')->on('p.entry_id', '=', 'd.ledger_entry_id');
        })->leftJoin('ledger_accounts as a', function ($j) {
            $j->on('a.school_id', '=', 'p.school_id')->on('a.id', '=', 'p.account_id');
        })->leftJoin('ledger_entries as e', function ($j) {
            $j->on('e.school_id', '=', 'd.school_id')->on('e.id', '=', 'd.ledger_entry_id');
        })->where('d.school_id', $school)->groupBy('d.id')->havingRaw("MAX(e.posted_at) IS NULL OR MAX(e.currency)<>MAX(t.currency) OR COUNT(p.id)<>2 OR COALESCE(SUM(CASE WHEN p.direction='debit' AND a.id=t.reserve_account_id AND a.type='wallet' AND a.module='wallet' THEN p.cents ELSE 0 END),0)<>MAX(t.amount_cents) OR COALESCE(SUM(CASE WHEN p.direction='credit' AND a.id=IF(d.action='released',w.ledger_account_id,t.master_account_id) AND a.type='wallet' AND a.module='wallet' THEN p.cents ELSE 0 END),0)<>MAX(t.amount_cents) OR MAX(d.requires_second_approval)<>IF(MAX(d.action)='released' AND (MAX(t.requires_second_approval)=1 OR MAX(t.amount_cents)>MAX(d.threshold_cents)),1,0) OR (MAX(d.requires_second_approval)=1 AND MAX(d.actor_id)=MAX(t.requested_by))")->pluck('d.id')->count();
        $orphan = DB::table('ledger_entries as e')->where('e.school_id', $school)->where('e.source', 'like', 'wallet-desk:%')->whereNotExists(function ($q) {
            $q->selectRaw('1')->from('wallet_desk_topups as t')->whereColumn('t.school_id', 'e.school_id')->whereColumn('t.reserve_entry_id', 'e.id');
        })->whereNotExists(function ($q) {
            $q->selectRaw('1')->from('wallet_desk_actions as d')->whereColumn('d.school_id', 'e.school_id')->whereColumn('d.ledger_entry_id', 'e.id');
        })->count();

        return ['desk_reservation_mismatches' => $reserved, 'desk_pending_liability_mismatches' => $pending, 'desk_terminal_mismatches' => $terminal, 'unattributed_desk_entries' => $orphan];
    }

    public static function healthy(array $report): bool
    {
        return ! array_filter($report, fn ($v) => $v !== 0);
    }
}
