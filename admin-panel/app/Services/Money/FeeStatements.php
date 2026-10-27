<?php

declare(strict_types=1);

namespace App\Services\Money;

use App\Models\Money\FeeAccount;
use App\Models\Money\FeePayment;
use App\Models\Money\InvoiceLine;
use App\Services\Platform\Ledger\LedgerService;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;

/** Internal projections. Public callers must apply fresh finance or holder policy. */
final class FeeStatements
{
    public function charges(FeeAccount $account, ?int $holder = null): array
    {
        return $this->chargesFor([(int) $account->id], $holder);
    }

    public function chargesFor(array $accountIds, ?int $holder = null): array
    {
        $school = app(CurrentSchool::class)->id();
        $allocated = DB::table('payment_allocations as a')->join('payment_allocation_runs as r', function ($j) {
            $j->on('r.school_id', '=', 'a.school_id')->on('r.id', '=', 'a.allocation_run_id');
        })->join('payments as p', function ($j) {
            $j->on('p.school_id', '=', 'a.school_id')->on('p.id', '=', 'a.payment_id');
        })->where('a.school_id', $school)->whereIn('p.fee_account_id', $accountIds)->whereNotNull('r.sealed_at')
            ->when($holder !== null, fn ($q) => $q->where('p.holder_user_id', $holder))
            ->selectRaw('a.invoice_line_id, p.holder_user_id, SUM(a.amount_cents) AS cents')->groupBy('a.invoice_line_id', 'p.holder_user_id')->get();
        $byShare = [];
        foreach ($allocated as $a) {
            $byShare[(int) $a->invoice_line_id][(int) $a->holder_user_id] = (int) $a->cents;
        }
        $released = FeeCreditHistory::releases()->whereIn('p.fee_account_id', $accountIds)->when($holder !== null, fn ($q) => $q->where('p.holder_user_id', $holder))
            ->selectRaw('a.invoice_line_id, p.holder_user_id, SUM(c.amount_cents) AS cents')->groupBy('a.invoice_line_id', 'p.holder_user_id')->get();
        foreach ($released as $release) {
            $byShare[(int) $release->invoice_line_id][(int) $release->holder_user_id] = ($byShare[(int) $release->invoice_line_id][(int) $release->holder_user_id] ?? 0) - (int) $release->cents;
        }
        $credited = [];
        foreach (FeeCreditHistory::notes()->whereIn('n.fee_account_id', $accountIds)->when($holder !== null, fn ($q) => $q->where('n.holder_user_id', $holder))
            ->selectRaw('n.invoice_line_id, n.holder_user_id, SUM(n.amount_cents) AS cents')->groupBy('n.invoice_line_id', 'n.holder_user_id')->get() as $credit) {
            $credited[(int) $credit->invoice_line_id][(int) $credit->holder_user_id] = (int) $credit->cents;
        }
        $lines = InvoiceLine::join('invoices as i', function ($j) {
            $j->on('i.school_id', '=', 'invoice_lines.school_id')->on('i.id', '=', 'invoice_lines.invoice_id');
        })->join('bill_runs as b', function ($j) {
            $j->on('b.school_id', '=', 'i.school_id')->on('b.id', '=', 'i.bill_run_id');
        })->whereIn('i.fee_account_id', $accountIds)->whereNotNull('i.sealed_at')->where('b.state', 'posted')
            ->orderBy('i.due_on')->orderBy('i.issued_on')->orderBy('invoice_lines.id')
            ->get(['invoice_lines.*', 'i.fee_account_id', 'i.number as invoice_number', 'i.due_on', 'i.issued_on']);
        $rows = [];
        foreach ($lines as $line) {
            foreach ($line->holder_shares as $share) {
                if ($holder !== null && $holder !== $share['user_id']) {
                    continue;
                }
                $used = $byShare[(int) $line->id][$share['user_id']] ?? 0;
                $credit = $credited[(int) $line->id][$share['user_id']] ?? 0;
                $net = $share['amount_cents'] - $credit;
                abort_unless($credit >= 0 && $net >= 0 && $used >= 0 && $used <= $net, 409, 'Fee history needs reconciliation.');
                $rows[] = ['fee_account_id' => (int) $line->getAttribute('fee_account_id'), 'invoice_line_id' => (int) $line->id, 'invoice_id' => (int) $line->invoice_id, 'invoice_number' => (string) $line->getAttribute('invoice_number'),
                    'learner_id' => (int) $line->learner_id, 'learner_name' => $line->learner_name, 'holder_user_id' => $share['user_id'], 'holder_name' => $share['name'],
                    'description' => $line->description, 'period_start' => $line->period_start->toDateString(), 'period_end' => $line->period_end->toDateString(),
                    'issued_on' => (string) $line->getAttribute('issued_on'), 'due_on' => (string) $line->getAttribute('due_on'), 'currency' => $line->currency, 'gross_charged_cents' => $share['amount_cents'], 'credited_cents' => $credit, 'charged_cents' => $net,
                    'allocated_cents' => $used, 'outstanding_cents' => $net - $used];
            }
        }

        return $rows;
    }

    public function receipts(FeeAccount $account, ?int $holder = null): array
    {
        return $this->receiptsFor([(int) $account->id], $holder);
    }

    public function receiptsFor(array $accountIds, ?int $holder = null): array
    {
        $payments = FeePayment::whereIn('fee_account_id', $accountIds)->when($holder !== null, fn ($q) => $q->where('holder_user_id', $holder))->orderByDesc('received_on')->orderByDesc('id')->get();
        $school = app(CurrentSchool::class)->id();
        $used = DB::table('payment_allocations as a')->join('payment_allocation_runs as r', function ($j) {
            $j->on('r.school_id', '=', 'a.school_id')->on('r.id', '=', 'a.allocation_run_id');
        })->where('a.school_id', $school)->whereIn('a.payment_id', $payments->pluck('id'))->whereNotNull('r.sealed_at')
            ->selectRaw('a.payment_id, SUM(a.amount_cents) AS cents')->groupBy('a.payment_id')->pluck('cents', 'payment_id');

        $released = FeeCreditHistory::releases()->whereIn('c.payment_id', $payments->pluck('id'))->selectRaw('c.payment_id, SUM(c.amount_cents) AS cents')->groupBy('c.payment_id')->pluck('cents', 'payment_id');

        return $payments->map(function ($p) use ($used, $released): array {
            $restored = (int) ($released[$p->id] ?? 0);
            $applied = (int) ($used[$p->id] ?? 0) - $restored;
            abort_unless($applied >= 0 && $applied <= $p->amount_cents, 409, 'Payment history needs reconciliation.');

            return ['id' => (int) $p->id, 'fee_account_id' => $p->fee_account_id, 'number' => 'FEE-PAY-'.$p->id, 'holder_user_id' => $p->holder_user_id, 'pinned_learner_id' => $p->pinned_learner_id,
                'method' => $p->method, 'received_on' => $p->received_on->toDateString(), 'currency' => $p->currency,
                'amount_cents' => $p->amount_cents, 'released_cents' => $restored, 'allocated_cents' => $applied, 'credit_cents' => $p->amount_cents - $applied];
        })->all();
    }

    public function finance(FeeAccount $account): array
    {
        abort_unless((int) $account->school_id === app(CurrentSchool::class)->id(), 404);
        $this->complete($account);
        $charges = $this->charges($account);
        $payments = $this->receipts($account);
        $debt = array_sum(array_column($charges, 'outstanding_cents'));
        $credit = array_sum(array_column($payments, 'credit_cents'));
        $receivable = $account->ledger_account_id === null ? 0 : app(LedgerService::class)->balance((int) $account->ledger_account_id)->cents;
        abort_unless($debt === $receivable, 409, 'Invoice balances differ from the ledger. Reconcile before continuing.');

        return ['account_id' => (int) $account->id, 'name' => $account->name, 'reference' => $account->reference, 'currency' => $account->currency,
            'outstanding_cents' => $debt, 'credit_cents' => $credit, 'net_cents' => $debt - $credit, 'gross_charged_cents' => array_sum(array_column($charges, 'gross_charged_cents')), 'credited_cents' => array_sum(array_column($charges, 'credited_cents')), 'charged_cents' => array_sum(array_column($charges, 'charged_cents')), 'charges' => $charges, 'payments' => $payments, 'credit_notes' => $this->creditNotesFor([(int) $account->id])];
    }

    public function creditNotesFor(array $accountIds, ?int $holder = null): array
    {
        return FeeCreditHistory::notes()->join('invoice_lines as l', fn ($j) => $j->on('l.school_id', '=', 'n.school_id')->on('l.id', '=', 'n.invoice_line_id'))
            ->join('invoices as i', fn ($j) => $j->on('i.school_id', '=', 'l.school_id')->on('i.id', '=', 'l.invoice_id'))
            ->whereIn('n.fee_account_id', $accountIds)->when($holder !== null, fn ($q) => $q->where('n.holder_user_id', $holder))->orderByDesc('n.id')
            ->get(['n.id', 'n.fee_account_id', 'n.invoice_line_id', 'n.holder_user_id', 'n.amount_cents', 'n.unpaid_cents', 'n.released_cents', 'n.reason_code', 'n.issued_on', 'n.currency', 'l.learner_id', 'l.learner_name', 'i.number as invoice_number'])
            ->map(fn ($n) => ['id' => (int) $n->id, 'number' => 'FEE-CN-'.$n->id, 'fee_account_id' => (int) $n->fee_account_id, 'invoice_line_id' => (int) $n->invoice_line_id, 'holder_user_id' => (int) $n->holder_user_id,
                'learner_id' => (int) $n->learner_id, 'learner_name' => $n->learner_name, 'invoice_number' => $n->invoice_number, 'amount_cents' => (int) $n->amount_cents, 'unpaid_cents' => (int) $n->unpaid_cents,
                'released_cents' => (int) $n->released_cents, 'reason_code' => $n->reason_code, 'issued_on' => $n->issued_on, 'currency' => $n->currency])->all();
    }

    public function complete(FeeAccount $account): void
    {
        $this->completeAccounts([(int) $account->id]);
    }

    public function completeAccounts(array $accountIds): void
    {
        $school = app(CurrentSchool::class)->id();
        $invoiceDraft = DB::table('invoices as i')->join('bill_runs as b', function ($j) {
            $j->on('b.school_id', '=', 'i.school_id')->on('b.id', '=', 'i.bill_run_id');
        })->where('i.school_id', $school)->whereIn('i.fee_account_id', $accountIds)
            ->where(fn ($q) => $q->whereNull('i.sealed_at')->orWhere('b.state', '<>', 'posted'))->first();
        abort_unless($invoiceDraft === null, 409, 'An incomplete invoice needs reconciliation before continuing.');
        $draft = DB::table('payment_allocation_runs as r')->join('payments as p', function ($j) {
            $j->on('p.school_id', '=', 'r.school_id')->on('p.id', '=', 'r.payment_id');
        })->where('r.school_id', $school)->whereIn('p.fee_account_id', $accountIds)->whereNull('r.sealed_at')->first();
        abort_unless($draft === null, 409, 'An incomplete allocation needs reconciliation before continuing.');
        $report = app(FeePaymentReconciliation::class)->inspect($accountIds);
        abort_unless(FeePaymentReconciliation::healthy($report), 409, 'Fee balances need reconciliation before a reliable statement can be shown.');
    }
}
