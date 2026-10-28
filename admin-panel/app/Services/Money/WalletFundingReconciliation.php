<?php

declare(strict_types=1);

namespace App\Services\Money;

use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;

final class WalletFundingReconciliation
{
    public function inspect(): array
    {
        $school = app(CurrentSchool::class)->id();
        $bad = DB::table('wallet_fundings as f')->leftJoin('ledger_postings as p', function ($j) {
            $j->on('p.school_id', '=', 'f.school_id')->on('p.entry_id', '=', 'f.ledger_entry_id');
        })->leftJoin('ledger_accounts as a', function ($j) {
            $j->on('a.school_id', '=', 'p.school_id')->on('a.id', '=', 'p.account_id');
        })->join('ledger_entries as e', function ($j) {
            $j->on('e.school_id', '=', 'f.school_id')->on('e.id', '=', 'f.ledger_entry_id');
        })->where('f.school_id', $school)->groupBy('f.id', 'f.amount_cents', 'f.asset_account_id', 'f.master_account_id', 'f.currency', 'f.method', 'e.posted_at', 'e.currency')->havingRaw("MAX(e.posted_at) IS NULL OR MAX(e.currency)<>MAX(f.currency) OR COUNT(p.id)<>2 OR COALESCE(SUM(CASE WHEN p.direction='debit' AND a.id=f.asset_account_id AND a.type=IF(f.method='cash','cash_desk','wallet_bank') AND a.module='wallet' AND a.currency=f.currency THEN p.cents ELSE 0 END),0)<>MAX(f.amount_cents) OR COALESCE(SUM(CASE WHEN p.direction='credit' AND a.id=f.master_account_id AND a.type='wallet' AND a.module='wallet' AND a.currency=f.currency THEN p.cents ELSE 0 END),0)<>MAX(f.amount_cents)")->pluck('f.id')->count();
        $missing = 0;
        foreach (['payments' => 'fees', 'wallet_fundings' => 'wallet', 'wallet_parent_deposits' => 'parent'] as $table => $domain) {
            $missing += DB::table($table.' as d')->where('d.school_id', $school)->whereNotExists(function ($q) use ($domain) {
                $q->selectRaw('1')->from('financial_receipts as r')->whereColumn('r.school_id', 'd.school_id')->where('r.domain', $domain)->whereColumn('r.record_id', 'd.id')->whereColumn('r.ledger_entry_id', 'd.ledger_entry_id')->whereColumn('r.currency', 'd.currency')->whereColumn('r.amount_cents', 'd.amount_cents')->whereColumn('r.source_reference', 'd.source_reference')->whereColumn('r.method', 'd.method');
            })->count();
        }
        $orphan = DB::table('financial_receipts as r')->where('r.school_id', $school)->whereRaw("((r.domain='fees' AND NOT EXISTS(SELECT 1 FROM payments p WHERE p.school_id=r.school_id AND p.id=r.record_id)) OR (r.domain='wallet' AND NOT EXISTS(SELECT 1 FROM wallet_fundings f WHERE f.school_id=r.school_id AND f.id=r.record_id)) OR (r.domain='parent' AND NOT EXISTS(SELECT 1 FROM wallet_parent_deposits p WHERE p.school_id=r.school_id AND p.id=r.record_id)))")->count();
        $unattributed = DB::table('ledger_entries as e')->where('e.school_id', $school)->where('e.source', 'like', 'wallet-funding:%')->whereNotExists(function ($q) {
            $q->selectRaw('1')->from('wallet_fundings as f')->whereColumn('f.school_id', 'e.school_id')->whereColumn('f.ledger_entry_id', 'e.id');
        })->count();

        return ['wallet_funding_mismatches' => $bad, 'external_receipt_mismatches' => $missing + $orphan, 'unattributed_wallet_funding_entries' => $unattributed];
    }

    public static function healthy(array $report): bool
    {
        return ! array_filter($report, fn ($v) => $v !== 0);
    }
}
