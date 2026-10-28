<?php

declare(strict_types=1);

namespace App\Services\Money;

use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;

final class WalletWithdrawalReconciliation
{
    public function inspect(): array
    {
        $school = app(CurrentSchool::class)->id();
        $bad = 0;
        foreach (DB::table('wallet_withdrawals')->where('school_id', $school)->orderBy('id')->get() as $row) {
            $wallet = DB::table('wallet_accounts')->where('school_id', $school)->where('id', $row->wallet_account_id)->first();
            $hold = DB::table('ledger_accounts')->where('school_id', $school)->where('id', $row->pending_account_id)->first();
            $actions = DB::table('wallet_withdrawal_actions')->where('school_id', $school)->where('withdrawal_id', $row->id)->orderBy('id')->get();
            $approved = $actions->where('status', 'approved');
            $terminals = $actions->whereIn('status', ['paid', 'cancelled', 'rejected']);
            $sound = $wallet !== null && $wallet->user_id === $row->holder_user_id && $wallet->currency === $row->currency && $hold !== null && $hold->owner_user_id === null && $hold->type === 'wallet' && $hold->module === 'wallet' && $hold->currency === $row->currency && $hold->reference === 'wallet:withdrawal:'.$row->operation_id;
            if ($wallet !== null) {
                $sound = $sound && $this->entry((int) $row->reserved_entry_id, (int) $wallet->ledger_account_id, (int) $row->pending_account_id, (int) $row->amount_cents, $row->currency, 'wallet-withdrawal:reserved:'.$row->operation_id);
            }
            $sound = $sound && $approved->count() <= 1 && $terminals->count() <= 1 && ! $approved->contains(fn ($a) => $a->actor_id === $row->holder_user_id);
            foreach ($actions as $action) {
                if ($action->status === 'approved') {
                    $sound = $sound && $action->ledger_entry_id === null;

                    continue;
                }
                if ($action->status === 'paid') {
                    $asset = DB::table('ledger_accounts')->where('school_id', $school)->where('id', $action->counter_account_id)->first();
                    $sound = $sound && $approved->count() === 1 && $approved->first()->id < $action->id && $action->actor_id !== $row->holder_user_id && $asset !== null && $asset->module === 'wallet' && $asset->owner_user_id === null && $asset->currency === $row->currency && $asset->type === ($row->method === 'cash' ? 'cash_desk' : 'wallet_bank') && $asset->reference === 'wallet:'.$row->method.':'.$row->currency && ($row->method === 'cash' ? $action->cash_operator_id === $action->actor_id : $action->cash_operator_id === null);
                } else {
                    $sound = $sound && $wallet !== null && $action->counter_account_id === $wallet->ledger_account_id;
                }
                $sound = $sound && $this->entry((int) $action->ledger_entry_id, (int) $row->pending_account_id, (int) $action->counter_account_id, (int) $row->amount_cents, $row->currency, 'wallet-withdrawal:'.$action->status.':'.$action->operation_id);
            }
            $raw = (int) DB::table('ledger_postings')->where('school_id', $school)->where('account_id', $row->pending_account_id)->selectRaw("COALESCE(SUM(CASE direction WHEN 'credit' THEN cents ELSE -cents END),0) AS cents")->value('cents');
            $sound = $sound && $raw === ($terminals->isEmpty() ? (int) $row->amount_cents : 0);
            if (! $sound) {
                $bad++;
            }
        }
        $orphan = DB::table('ledger_entries as e')->where('e.school_id', $school)->where('e.source', 'like', 'wallet-withdrawal:%')->whereNotExists(fn ($q) => $q->selectRaw('1')->from('wallet_withdrawals as w')->whereColumn('w.school_id', 'e.school_id')->whereColumn('w.reserved_entry_id', 'e.id'))->whereNotExists(fn ($q) => $q->selectRaw('1')->from('wallet_withdrawal_actions as a')->whereColumn('a.school_id', 'e.school_id')->whereColumn('a.ledger_entry_id', 'e.id'))->count();
        $missing = 0;
        foreach (['fee_refund_actions' => 'fees', 'wallet_withdrawal_actions' => 'wallet'] as $table => $domain) {
            $state = $domain === 'fees' ? 'action' : 'status';
            $missing += DB::table($table.' as a')->where('a.school_id', $school)->where('a.'.$state, 'paid')->whereNotExists(fn ($q) => $q->selectRaw('1')->from('financial_disbursements as d')->whereColumn('d.school_id', 'a.school_id')->where('d.domain', $domain)->whereColumn('d.record_id', 'a.id')->whereColumn('d.identity_hash', 'a.reference_hash')->whereColumn('d.ledger_entry_id', 'a.ledger_entry_id'))->count();
        }
        $badRegistry = DB::table('financial_disbursements as d')->where('d.school_id', $school)->whereRaw("(d.domain='fees' AND NOT EXISTS(SELECT 1 FROM fee_refund_actions a JOIN fee_refunds r ON r.school_id=a.school_id AND r.id=a.fee_refund_id WHERE a.school_id=d.school_id AND a.id=d.record_id AND a.action='paid' AND a.reference_hash=d.identity_hash AND a.currency=d.currency AND a.amount_cents=d.amount_cents AND a.ledger_entry_id=d.ledger_entry_id AND r.method=d.method)) OR (d.domain='wallet' AND NOT EXISTS(SELECT 1 FROM wallet_withdrawal_actions a JOIN wallet_withdrawals r ON r.school_id=a.school_id AND r.id=a.withdrawal_id WHERE a.school_id=d.school_id AND a.id=d.record_id AND a.status='paid' AND a.reference_hash=d.identity_hash AND r.currency=d.currency AND r.amount_cents=d.amount_cents AND a.ledger_entry_id=d.ledger_entry_id AND r.method=d.method))")->count();

        return ['wallet_withdrawal_mismatches' => $bad, 'unattributed_wallet_withdrawal_entries' => $orphan, 'outside_disbursement_mismatches' => $missing + $badRegistry];
    }

    private function entry(int $entry, int $debit, int $credit, int $cents, string $currency, string $source): bool
    {
        $school = app(CurrentSchool::class)->id();
        $header = DB::table('ledger_entries')->where('school_id', $school)->where('id', $entry)->first();
        $postings = DB::table('ledger_postings')->where('school_id', $school)->where('entry_id', $entry)->get();

        return $header !== null && $header->posted_at !== null && $header->source === $source && $header->currency === $currency && $postings->count() === 2 && $postings->where('account_id', $debit)->where('direction', 'debit')->where('cents', $cents)->count() === 1 && $postings->where('account_id', $credit)->where('direction', 'credit')->where('cents', $cents)->count() === 1;
    }

    public static function healthy(array $report): bool
    {
        return ! array_filter($report, fn ($count) => $count !== 0);
    }
}
