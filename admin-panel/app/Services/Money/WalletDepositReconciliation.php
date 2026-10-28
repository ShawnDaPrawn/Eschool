<?php

declare(strict_types=1);

namespace App\Services\Money;

use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;

final class WalletDepositReconciliation
{
    public function inspect(): array
    {
        $school = app(CurrentSchool::class)->id();
        $bad = DB::table('wallet_parent_deposits as d')->join('wallet_deposit_claims as c', fn ($j) => $j->on('c.school_id', '=', 'd.school_id')->on('c.id', '=', 'd.claim_id'))->join('wallet_accounts as w', fn ($j) => $j->on('w.school_id', '=', 'c.school_id')->on('w.id', '=', 'c.wallet_account_id'))->leftJoin('ledger_postings as p', fn ($j) => $j->on('p.school_id', '=', 'd.school_id')->on('p.entry_id', '=', 'd.ledger_entry_id'))->leftJoin('ledger_accounts as a', fn ($j) => $j->on('a.school_id', '=', 'p.school_id')->on('a.id', '=', 'p.account_id'))->leftJoin('ledger_entries as e', fn ($j) => $j->on('e.school_id', '=', 'd.school_id')->on('e.id', '=', 'd.ledger_entry_id'))->where('d.school_id', $school)->groupBy('d.id')->havingRaw("MAX(e.posted_at) IS NULL OR MAX(e.currency)<>MAX(d.currency) OR MAX(e.source)<>CONCAT('wallet-parent-deposit:',MAX(d.operation_id)) OR COUNT(p.id)<>2 OR MAX(c.actor_id)=MAX(d.verified_by) OR MAX(w.user_id)<>MAX(c.actor_id) OR MAX(w.currency)<>MAX(d.currency) OR COALESCE(SUM(CASE WHEN p.direction='debit' AND a.id=d.asset_account_id AND a.type=IF(d.method='cash','cash_desk','wallet_bank') AND a.module='wallet' THEN p.cents ELSE 0 END),0)<>MAX(d.amount_cents) OR COALESCE(SUM(CASE WHEN p.direction='credit' AND a.id=w.ledger_account_id AND a.type='wallet' AND a.module='wallet' AND a.owner_user_id=c.actor_id THEN p.cents ELSE 0 END),0)<>MAX(d.amount_cents)")->pluck('d.id')->count();
        $missingOutcome = DB::table('wallet_parent_deposits as d')->where('d.school_id', $school)->whereNotExists(fn ($q) => $q->selectRaw('1')->from('wallet_deposit_actions as a')->whereColumn('a.school_id', 'd.school_id')->whereColumn('a.claim_id', 'd.claim_id')->whereColumn('a.deposit_id', 'd.id')->whereColumn('a.actor_id', 'd.verified_by')->where('a.status', 'verified'))->count();
        $invalidOutcome = DB::table('wallet_deposit_actions as a')->where('a.school_id', $school)->where('a.status', 'verified')->whereNotExists(fn ($q) => $q->selectRaw('1')->from('wallet_parent_deposits as d')->whereColumn('d.school_id', 'a.school_id')->whereColumn('d.claim_id', 'a.claim_id')->whereColumn('d.id', 'a.deposit_id')->whereColumn('d.verified_by', 'a.actor_id'))->count();
        $orphans = DB::table('ledger_entries as e')->where('e.school_id', $school)->where('e.source', 'like', 'wallet-parent-deposit:%')->whereNotExists(fn ($q) => $q->selectRaw('1')->from('wallet_parent_deposits as d')->whereColumn('d.school_id', 'e.school_id')->whereColumn('d.ledger_entry_id', 'e.id'))->count();

        return ['parent_deposit_mismatches' => $bad, 'parent_deposit_outcome_mismatches' => $missingOutcome + $invalidOutcome, 'unattributed_parent_deposits' => $orphans];
    }

    public static function healthy(array $report): bool
    {
        return ! array_filter($report, fn ($count) => $count !== 0);
    }
}
