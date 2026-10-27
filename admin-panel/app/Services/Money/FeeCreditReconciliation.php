<?php

declare(strict_types=1);

namespace App\Services\Money;

use App\Services\Platform\Ledger\CentsRatio;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;

final class FeeCreditReconciliation
{
    public function inspect(?array $accountIds = null): array
    {
        $school = app(CurrentSchool::class)->id();
        $notes = DB::table('fee_credit_notes')->where('school_id', $school)->when($accountIds !== null, fn ($q) => $q->whereIn('fee_account_id', $accountIds));
        $drafts = (clone $notes)->whereNull('sealed_at')->count();
        $badNotes = DB::table('fee_credit_notes as n')->leftJoin('fee_credit_releases as c', fn ($j) => $j->on('c.school_id', '=', 'n.school_id')->on('c.credit_note_id', '=', 'n.id'))
            ->where('n.school_id', $school)->when($accountIds !== null, fn ($q) => $q->whereIn('n.fee_account_id', $accountIds))->whereNotNull('n.sealed_at')
            ->groupBy('n.id', 'n.released_cents')->havingRaw('COALESCE(SUM(c.amount_cents),0) <> n.released_cents')->pluck('n.id')->count();
        $releases = FeeCreditHistory::releases()->when($accountIds !== null, fn ($q) => $q->whereIn('p.fee_account_id', $accountIds));
        $badReleaseLinks = (clone $releases)->where(fn ($q) => $q->whereColumn('n.invoice_line_id', '<>', 'a.invoice_line_id')->orWhereColumn('n.holder_user_id', '<>', 'p.holder_user_id')->orWhereColumn('n.fee_account_id', '<>', 'p.fee_account_id')->orWhereRaw('NOT(n.credit_account_id <=> p.credit_account_id)'))->count();
        $badReleases = (clone $releases)->groupBy('a.id', 'a.amount_cents')->havingRaw('SUM(c.amount_cents) > a.amount_cents')->pluck('a.id')->count();
        $credited = [];
        $vatCredited = [];
        foreach ((clone $notes)->whereNotNull('sealed_at')->selectRaw('invoice_line_id, holder_user_id, SUM(amount_cents) AS cents,SUM(vat_cents) AS vat')->groupBy('invoice_line_id', 'holder_user_id')->get() as $n) {
            $credited[(int) $n->invoice_line_id][(int) $n->holder_user_id] = (int) $n->cents;
            $vatCredited[(int) $n->invoice_line_id][(int) $n->holder_user_id] = (int) $n->vat;
        }
        $used = [];
        $allocated = DB::table('payment_allocations as a')->join('payments as p', fn ($j) => $j->on('p.school_id', '=', 'a.school_id')->on('p.id', '=', 'a.payment_id'))
            ->join('payment_allocation_runs as r', fn ($j) => $j->on('r.school_id', '=', 'a.school_id')->on('r.id', '=', 'a.allocation_run_id'))->where('a.school_id', $school)->whereNotNull('r.sealed_at')
            ->when($accountIds !== null, fn ($q) => $q->whereIn('p.fee_account_id', $accountIds))->selectRaw('a.invoice_line_id, p.holder_user_id, SUM(a.amount_cents) AS cents')->groupBy('a.invoice_line_id', 'p.holder_user_id')->get();
        foreach ($allocated as $a) {
            $used[(int) $a->invoice_line_id][(int) $a->holder_user_id] = (int) $a->cents;
        }
        foreach ((clone $releases)->selectRaw('a.invoice_line_id, p.holder_user_id, SUM(c.amount_cents) AS cents')->groupBy('a.invoice_line_id', 'p.holder_user_id')->get() as $c) {
            $used[(int) $c->invoice_line_id][(int) $c->holder_user_id] = ($used[(int) $c->invoice_line_id][(int) $c->holder_user_id] ?? 0) - (int) $c->cents;
        }
        $badShares = 0;
        $lines = DB::table('invoice_lines as l')->join('invoices as i', fn ($j) => $j->on('i.school_id', '=', 'l.school_id')->on('i.id', '=', 'l.invoice_id'))->where('l.school_id', $school)->whereNotNull('i.sealed_at')
            ->when($accountIds !== null, fn ($q) => $q->whereIn('i.fee_account_id', $accountIds))->get(['l.id', 'l.holder_shares', 'l.vat_cents']);
        foreach ($lines as $line) {
            $lineVat = 0;
            foreach (json_decode($line->holder_shares, true, flags: JSON_THROW_ON_ERROR) as $share) {
                $credit = $credited[(int) $line->id][$share['user_id']] ?? 0;
                $paid = $used[(int) $line->id][$share['user_id']] ?? 0;
                $originalVat = $share['vat_cents'] ?? 0;
                $lineVat += $originalVat;
                $usedVat = $vatCredited[(int) $line->id][$share['user_id']] ?? 0;
                $valid = $credit >= 0 && $credit <= $share['amount_cents'] && $originalVat >= 0 && $originalVat <= $share['amount_cents'];
                $expectedVat = $valid && $share['amount_cents'] > 0 ? CentsRatio::portion($originalVat, $credit, $share['amount_cents']) : 0;
                if (! $valid || $usedVat !== $expectedVat) {
                    $badShares++;
                }
                if ($credit < 0 || $paid < 0 || $credit + $paid > $share['amount_cents']) {
                    $badShares++;
                }
            }
            if ($lineVat !== (int) $line->vat_cents) {
                $badShares++;
            }
        }
        $income = [];
        $vatDifferences = [];
        if ($accountIds === null) {
            $invoiceLines = DB::table('invoice_lines as l')->join('invoices as i', fn ($j) => $j->on('i.school_id', '=', 'l.school_id')->on('i.id', '=', 'l.invoice_id'))->where('l.school_id', $school)->whereNotNull('i.sealed_at');
            $billed = (clone $invoiceLines)->selectRaw('l.currency,SUM(l.amount_cents-l.vat_cents) AS cents')->groupBy('l.currency')->pluck('cents', 'currency');
            $vatBilled = (clone $invoiceLines)->selectRaw('l.currency,SUM(l.vat_cents) AS cents')->groupBy('l.currency')->pluck('cents', 'currency');
            $credits = FeeCreditHistory::notes()->selectRaw('n.currency, SUM(n.amount_cents-n.vat_cents) AS cents')->groupBy('n.currency')->pluck('cents', 'currency');
            $posted = DB::table('ledger_postings as p')->join('ledger_entries as e', fn ($j) => $j->on('e.school_id', '=', 'p.school_id')->on('e.id', '=', 'p.entry_id'))
                ->join('ledger_accounts as a', fn ($j) => $j->on('a.school_id', '=', 'p.school_id')->on('a.id', '=', 'p.account_id'))->where('p.school_id', $school)->where('a.type', 'fee_income')->whereNotNull('e.posted_at')
                ->selectRaw("a.currency, SUM(CASE WHEN p.direction = 'credit' THEN p.cents ELSE -p.cents END) AS cents")->groupBy('a.currency')->pluck('cents', 'currency');
            $vatCredits = FeeCreditHistory::notes()->selectRaw('n.currency,SUM(n.vat_cents) AS cents')->groupBy('n.currency')->pluck('cents', 'currency');
            $vatPosted = DB::table('ledger_postings as p')->join('ledger_entries as e', fn ($j) => $j->on('e.school_id', '=', 'p.school_id')->on('e.id', '=', 'p.entry_id'))->join('ledger_accounts as a', fn ($j) => $j->on('a.school_id', '=', 'p.school_id')->on('a.id', '=', 'p.account_id'))->where('p.school_id', $school)->where('a.type', 'fee_vat')->whereNotNull('e.posted_at')->selectRaw("a.currency,SUM(CASE WHEN p.direction='credit' THEN p.cents ELSE -p.cents END) AS cents")->groupBy('a.currency')->pluck('cents', 'currency');
            foreach (array_unique([...$vatBilled->keys()->all(), ...$vatPosted->keys()->all()]) as $currency) {
                $expected = (int) ($vatBilled[$currency] ?? 0) - (int) ($vatCredits[$currency] ?? 0);
                $actual = (int) ($vatPosted[$currency] ?? 0);
                if ($expected !== $actual) {
                    $vatDifferences[] = ['currency' => $currency, 'expected_cents' => $expected, 'posting_cents' => $actual];
                }
            }
            foreach (array_unique([...$billed->keys()->all(), ...$posted->keys()->all()]) as $currency) {
                $expected = (int) ($billed[$currency] ?? 0) - (int) ($credits[$currency] ?? 0);
                if ($expected !== (int) ($posted[$currency] ?? 0)) {
                    $income[] = ['currency' => $currency, 'expected_cents' => $expected, 'posting_cents' => (int) ($posted[$currency] ?? 0)];
                }
            }
        }

        return ['incomplete_credit_notes' => $drafts, 'credit_note_mismatches' => $badNotes, 'credit_release_mismatches' => $badReleases + $badReleaseLinks, 'fee_share_mismatches' => $badShares, 'fee_income_differences' => $income, 'fee_vat_differences' => $vatDifferences];
    }
}
