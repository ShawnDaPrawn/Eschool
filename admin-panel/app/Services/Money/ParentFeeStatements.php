<?php

declare(strict_types=1);

namespace App\Services\Money;

use App\Models\Money\FeeAccount;
use App\Models\Money\FeeAccountHolder;
use App\Models\Money\FeeAccountLearner;
use App\Models\Money\FeePayment;
use App\Models\Money\Invoice;
use App\Models\User;
use App\Policies\Money\ParentFeePolicy;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;

final class ParentFeeStatements
{
    public function index(User $actor, int $page = 1, int $perPage = 20): array
    {
        return DB::transaction(function () use ($actor, $page, $perPage): array {
            $all = $this->owned($actor);
            $summaries = array_map(fn ($s) => $this->summary($s), array_values($all));
            app(ParentFeePolicy::class)->authorize($actor);

            return $this->page($summaries, $page, $perPage) + ['school_id' => app(CurrentSchool::class)->id(), 'holder_user_id' => (int) $actor->id];
        });
    }

    public function show(User $actor, int $id, int $chargesPage = 1, int $receiptsPage = 1, int $perPage = 20): array
    {
        return DB::transaction(function () use ($actor, $id, $chargesPage, $receiptsPage, $perPage): array {
            $all = $this->owned($actor);
            abort_unless(isset($all[$id]), 404);
            $statement = $all[$id];
            app(ParentFeePolicy::class)->authorize($actor);

            return $this->summary($statement) + ['charges' => $this->page($statement['charges'], $chargesPage, $perPage), 'receipts' => $this->page($statement['payments'], $receiptsPage, $perPage)];
        });
    }

    private function owned(User $actor): array
    {
        $policy = app(ParentFeePolicy::class);
        $policy->authorize($actor);
        $visible = $policy->learners($actor);
        if ($visible === []) {
            return [];
        }
        $holderRows = FeeAccountHolder::where('user_id', $actor->id)->get(['fee_account_id', 'session_year_id']);
        $ids = [...$holderRows->pluck('fee_account_id')->all(), ...FeePayment::where('holder_user_id', $actor->id)->pluck('fee_account_id')->all(),
            ...Invoice::whereNotNull('sealed_at')->whereJsonContains('holders', ['user_id' => (int) $actor->id])->pluck('fee_account_id')->all()];
        $ids = array_values(array_unique(array_map('intval', $ids)));
        if ($ids === []) {
            return [];
        }
        $accounts = FeeAccount::whereIn('id', $ids)->orderBy('id')->get();
        $projections = app(FeeStatements::class);
        $projections->completeAccounts($ids);
        $charges = $projections->chargesFor($ids, (int) $actor->id);
        $payments = $projections->receiptsFor($ids, (int) $actor->id);
        $school = app(CurrentSchool::class)->id();
        $hiddenPaymentIds = DB::table('payment_allocations as a')->join('payments as p', function ($j) {
            $j->on('p.school_id', '=', 'a.school_id')->on('p.id', '=', 'a.payment_id');
        })->join('invoice_lines as l', function ($j) {
            $j->on('l.school_id', '=', 'a.school_id')->on('l.id', '=', 'a.invoice_line_id');
        })->where('a.school_id', $school)->where('p.holder_user_id', $actor->id)->whereIn('p.fee_account_id', $ids)
            ->whereNotIn('l.learner_id', $visible)->pluck('a.payment_id')->map(fn ($id) => (int) $id)->all();
        $assigned = FeeAccountLearner::whereIn('fee_account_id', $ids)->whereIn('learner_id', $visible)->get(['fee_account_id', 'session_year_id']);
        $configured = [];
        foreach ($holderRows as $holder) {
            $configured[(int) $holder->fee_account_id][(int) $holder->session_year_id] = true;
        }
        $emptyAllowed = [];
        foreach ($assigned as $link) {
            if (isset($configured[(int) $link->fee_account_id][(int) $link->session_year_id])) {
                $emptyAllowed[(int) $link->fee_account_id] = true;
            }
        }
        $byAccount = [];
        foreach ($accounts as $account) {
            $id = (int) $account->id;
            $rows = [];
            foreach ($charges as $row) {
                if ($row['fee_account_id'] !== $id || ! in_array($row['learner_id'], $visible, true)) {
                    continue;
                }
                // Explicit whitelist: another holder's identity, shares, ledger
                // and source references never enter the parent's resource.
                $rows[] = array_intersect_key($row, array_flip(['invoice_line_id', 'invoice_number', 'learner_id', 'learner_name', 'description', 'period_start', 'period_end',
                    'issued_on', 'due_on', 'currency', 'charged_cents', 'allocated_cents', 'outstanding_cents']));
            }
            // Owning a receipt does not restore guardian visibility to this
            // family. A visible learner elsewhere cannot authorize its history.
            if ($rows === [] && ! isset($emptyAllowed[$id])) {
                continue;
            }
            $receipts = [];
            foreach ($payments as $payment) {
                if ($payment['fee_account_id'] !== $id || in_array($payment['id'], $hiddenPaymentIds, true)
                    || ($payment['pinned_learner_id'] !== null && ! in_array($payment['pinned_learner_id'], $visible, true))) {
                    continue;
                }
                $receipts[] = array_intersect_key($payment, array_flip(['id', 'number', 'pinned_learner_id', 'method', 'received_on', 'currency', 'amount_cents', 'allocated_cents', 'credit_cents']));
            }
            $byAccount[$id] = ['id' => $id, 'name' => $account->name, 'reference' => $account->reference, 'currency' => $account->currency, 'school_id' => $school, 'holder_user_id' => (int) $actor->id, 'charges' => $rows, 'payments' => $receipts];
        }

        return $byAccount;
    }

    private function summary(array $statement): array
    {
        $charges = $statement['charges'];
        $outstanding = array_sum(array_column($charges, 'outstanding_cents'));
        $credit = array_sum(array_column($statement['payments'], 'credit_cents'));
        $due = array_column(array_filter($charges, fn ($r) => $r['outstanding_cents'] > 0), 'due_on');
        sort($due);

        return array_intersect_key($statement, array_flip(['id', 'name', 'reference', 'currency', 'school_id', 'holder_user_id'])) + [
            'charged_cents' => array_sum(array_column($charges, 'charged_cents')), 'allocated_cents' => array_sum(array_column($charges, 'allocated_cents')),
            'outstanding_cents' => $outstanding, 'credit_cents' => $credit, 'net_cents' => $outstanding - $credit, 'next_due_on' => $due[0] ?? null,
            'scope' => 'your_visible_ledger_records',
        ];
    }

    private function page(array $rows, int $page, int $perPage): array
    {
        abort_unless($page >= 1 && $page <= 1000000 && $perPage >= 1 && $perPage <= 50, 422);

        return ['items' => array_values(array_slice($rows, ($page - 1) * $perPage, $perPage)), 'current_page' => $page, 'per_page' => $perPage, 'total' => count($rows)];
    }
}
