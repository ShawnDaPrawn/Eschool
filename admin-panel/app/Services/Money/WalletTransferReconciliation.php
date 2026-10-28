<?php

declare(strict_types=1);

namespace App\Services\Money;

use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;

final class WalletTransferReconciliation
{
    public function inspect(): array
    {
        $school = app(CurrentSchool::class)->id();
        $bad = DB::table('wallet_transfers as t')->join('wallet_accounts as f', fn ($j) => $j->on('f.school_id', '=', 't.school_id')->on('f.id', '=', 't.from_wallet_id'))->join('wallet_accounts as w', fn ($j) => $j->on('w.school_id', '=', 't.school_id')->on('w.id', '=', 't.to_wallet_id'))->leftJoin('ledger_postings as p', fn ($j) => $j->on('p.school_id', '=', 't.school_id')->on('p.entry_id', '=', 't.ledger_entry_id'))->leftJoin('ledger_entries as e', fn ($j) => $j->on('e.school_id', '=', 't.school_id')->on('e.id', '=', 't.ledger_entry_id'))->where('t.school_id', $school)->groupBy('t.id')->havingRaw("MAX(e.posted_at) IS NULL OR MAX(e.currency)<>MAX(t.currency) OR MAX(e.source)<>CONCAT('wallet-transfer:',MAX(t.operation_id)) OR COUNT(p.id)<>2 OR MAX(f.user_id)<>MAX(t.from_owner_id) OR MAX(w.user_id)<>MAX(t.to_owner_id) OR MAX(f.currency)<>MAX(t.currency) OR MAX(w.currency)<>MAX(t.currency) OR COALESCE(SUM(CASE WHEN p.direction='debit' AND p.account_id=f.ledger_account_id THEN p.cents ELSE 0 END),0)<>MAX(t.amount_cents) OR COALESCE(SUM(CASE WHEN p.direction='credit' AND p.account_id=w.ledger_account_id THEN p.cents ELSE 0 END),0)<>MAX(t.amount_cents)")->pluck('t.id')->count();
        $orphans = DB::table('ledger_entries as e')->where('e.school_id', $school)->where('e.source', 'like', 'wallet-transfer:%')->whereNotExists(fn ($q) => $q->selectRaw('1')->from('wallet_transfers as t')->whereColumn('t.school_id', 'e.school_id')->whereColumn('t.ledger_entry_id', 'e.id'))->count();

        return ['wallet_transfer_mismatches' => $bad, 'unattributed_wallet_transfers' => $orphans];
    }

    public static function healthy(array $report): bool
    {
        return ! array_filter($report, fn ($count) => $count !== 0);
    }
}
