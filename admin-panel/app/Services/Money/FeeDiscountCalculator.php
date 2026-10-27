<?php

declare(strict_types=1);

namespace App\Services\Money;

use App\Models\Money\FeeAccount;
use App\Models\Money\FeeAccountLearner;
use App\Models\Money\FeeDiscount;
use App\Models\Money\FeeDiscountRun;
use App\Models\Money\InvoiceLine;
use App\Models\People\Enrolment;
use App\Models\Platform\GuardianLink;
use App\Models\Platform\SchoolMembership;
use App\Models\User;
use App\Policies\Money\MoneyPolicy;
use App\Services\Platform\Ledger\CentsRatio;
use App\Services\Platform\Ledger\Money;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;

final class FeeDiscountCalculator
{
    public function snapshot(User $actor, FeeDiscount $rule, FeeAccount $account, int $learner, string $operation): array
    {
        app(MoneyPolicy::class)->authorize($actor, FeeDiscounts::PERMISSION, locked: true);
        $year = (int) $rule->session_year_id;
        app(FeeDiscounts::class)->currentYear($year);
        $date = now(app(CurrentSchool::class)->school()->timezone ?? 'UTC')->toDateString();
        abort_unless($rule->state === 'active' && $rule->starts_on <= $date && $rule->ends_on >= $date && $account->active && $rule->currency === $account->currency, 422, 'Use a currently approved discount and matching active family account.');
        abort_unless($rule->learner_id === null || $rule->learner_id === $learner, 404);
        $ids = FeeAccountLearner::where('session_year_id', $year)->where('fee_account_id', $account->id)->orderBy('learner_id')->lockForUpdate()->pluck('learner_id')->map(fn ($v) => (int) $v)->all();
        abort_unless(in_array($learner, $ids, true), 404);
        $enrolments = Enrolment::where('session_year_id', $year)->whereIn('learner_id', $ids)->onDate($date)->lockForUpdate()->get();
        $eligible = [];
        foreach ($enrolments->groupBy('learner_id') as $id => $links) {
            if ($links->count() === 1 && ! $links->first()->legacy_dates) {
                $eligible[] = (int) $id;
            }
        }sort($eligible);
        abort_unless(in_array($learner, $eligible, true), 422, 'Confirm a single dated enrolment before reducing a fee.');
        $evidence = ['rule_revision' => $rule->revision, 'date' => $date, 'family_learner_ids' => $ids, 'currently_enrolled_ids' => $eligible];
        if ($rule->kind === 'sibling') {
            $position = array_search($learner, $eligible, true) + 1;
            abort_unless($position >= $rule->sibling_position, 422, 'This learner does not meet the configured sibling position.');
            $evidence['sibling_position'] = $position;
        }
        if ($rule->kind === 'staff') {
            $guardians = GuardianLink::where('learner_id', $learner)->where('status', 'active')->where('can_see_fees', true)->lockForUpdate()->pluck('guardian_user_id');
            $people = User::whereIn('id', $guardians)->where('status', 1)->where('password_change_required', false)->lockForUpdate()->pluck('id');
            $staff = SchoolMembership::active()->where('school_id', app(CurrentSchool::class)->id())->whereIn('user_id', $people)->whereIn('role', $rule->staff_roles)->orderBy('user_id')->lockForUpdate()->get(['user_id', 'role']);
            abort_unless($staff->isNotEmpty(), 422, 'This learner has no current guardian in the configured staff roles.');
            $evidence['staff'] = $staff->toArray();
        }
        abort_if(FeeDiscountRun::where('fee_discount_id', $rule->id)->where('learner_id', $learner)->whereNotNull('sealed_at')->lockForUpdate()->exists(), 409, 'This rule has already been applied to this learner in this school year.');
        $other = FeeDiscountRun::where('session_year_id', $year)->where('learner_id', $learner)->whereNotNull('sealed_at')->orderBy('id')->lockForUpdate()->get(['id', 'fee_discount_id', 'snapshot']);
        $lines = InvoiceLine::where('session_year_id', $year)->where('learner_id', $learner)->when($rule->source_kind === 'annual_tuition', fn ($q) => $q->whereNull('fee_charge_id')->when($rule->fee_plan_id !== null, fn ($q) => $q->where('fee_plan_id', $rule->fee_plan_id)), fn ($q) => $q->whereIn('fee_charge_id', DB::table('fee_charges')->where('school_id', app(CurrentSchool::class)->id())->where('fee_item_id', $rule->fee_item_id)->select('id')))->lockForUpdate()->pluck('id')->all();
        $overlaps = [];
        foreach ($other as $prior) {
            if (array_intersect($lines, $prior->snapshot['line_ids']) !== []) {
                $overlaps[] = $prior->id;
                abort_unless($rule->overlap === 'remaining' && ($prior->snapshot['rule']['overlap'] ?? null) === 'remaining', 422, 'This charge already has an exclusive discount.');
            }
        }
        $rows = array_values(array_filter(app(FeeStatements::class)->finance($account)['charges'], fn ($row) => in_array($row['invoice_line_id'], $lines, true) && $row['charged_cents'] > 0));
        abort_unless($rows !== [], 422, 'Post the eligible source invoice before reviewing its linked reduction.');
        $basis = array_sum(array_column($rows, 'charged_cents'));
        abort_unless($basis <= FeePlans::MAX_CENTS, 422, 'Discount source exceeds the supported cents limit.');
        if ($rule->kind === 'early_settlement') {
            $paid = DB::table('payment_allocations as a')->join('payments as p', fn ($j) => $j->on('p.school_id', '=', 'a.school_id')->on('p.id', '=', 'a.payment_id'))->join('payment_allocation_runs as r', fn ($j) => $j->on('r.school_id', '=', 'a.school_id')->on('r.id', '=', 'a.allocation_run_id'))->where('a.school_id', app(CurrentSchool::class)->id())->whereIn('a.invoice_line_id', $lines)->where('p.fee_account_id', $account->id)->where('p.received_on', '<=', $rule->payment_deadline)->whereNotNull('r.sealed_at')->orderBy('a.id')->lockForUpdate()->get(['a.id', 'a.amount_cents']);
            $released = FeeCreditHistory::releases()->whereIn('a.id', $paid->pluck('id'))->sum('c.amount_cents');
            $paidCents = (int) $paid->sum('amount_cents') - (int) $released;
            $required = max(1, CentsRatio::portion($basis, $rule->minimum_paid_bp, 10000));
            abort_unless($paidCents >= $required, 422, 'Actual allocated receipts by the configured deadline do not meet the early-settlement threshold.');
            $evidence['eligible_payment_allocations'] = $paid->toArray();
            $evidence['released_cents'] = (int) $released;
            $evidence['paid_cents'] = $paidCents;
            $evidence['required_paid_cents'] = $required;
        }
        $amount = $rule->amount_kind === 'percent' ? CentsRatio::portion($basis, $rule->amount_value, 10000) : $rule->amount_value;
        abort_unless($amount > 0 && $amount <= $basis, 422, 'The configured reduction must be positive and cannot exceed remaining charges.');
        $weights = [];
        foreach ($rows as $n => $row) {
            $weights[$n] = $row['charged_cents'];
        }$slices = FeeTaxAmounts::split($amount, $weights);
        $credits = [];
        foreach ($rows as $n => $row) {
            if ($slices[$n] === 0) {
                continue;
            }
            $input = ['operation_id' => Uuid::uuid5(Uuid::NAMESPACE_URL, 'fee-discount:'.$operation.':line:'.$row['invoice_line_id'].':holder:'.$row['holder_user_id'])->toString(), 'invoice_line_id' => $row['invoice_line_id'], 'holder_user_id' => $row['holder_user_id'], 'amount' => Money::ofCents($slices[$n], $account->currency)->toDecimalString(), 'reason_code' => 'fee_reduction', 'reason' => 'Approved school fee reduction'];
            $credits[] = ['input' => $input, 'preview' => app(FeeCreditNotes::class)->preview($actor, $input)];
        }

        return ['rule_id' => (int) $rule->id, 'rule' => $rule->only(['name', 'kind', 'source_kind', 'session_year_id', 'learner_id', 'fee_plan_id', 'fee_item_id', 'currency', 'starts_on', 'ends_on', 'amount_kind', 'amount_value', 'sibling_position', 'minimum_paid_bp', 'payment_deadline', 'overlap', 'independent_approval', 'staff_roles', 'revision', 'prepared_by', 'approved_by']), 'fee_account_id' => (int) $account->id, 'account_revision' => $account->revision, 'learner_id' => $learner, 'session_year_id' => $year, 'currency' => $account->currency, 'basis_cents' => $basis, 'amount_cents' => $amount, 'remaining_cents' => $basis - $amount, 'line_ids' => $lines, 'prior_discount_ids' => $overlaps, 'evidence' => $evidence, 'credits' => $credits];
    }
}
