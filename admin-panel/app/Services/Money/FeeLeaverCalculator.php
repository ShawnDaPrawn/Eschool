<?php

declare(strict_types=1);

namespace App\Services\Money;

use App\Models\Money\BillRun;
use App\Models\Money\FeeAccount;
use App\Models\Money\FeeLeaverPolicy;
use App\Models\Money\FeeLifecyclePolicy;
use App\Models\Money\Invoice;
use App\Models\Money\InvoiceLine;
use App\Models\People\Enrolment;
use App\Models\SessionYear;
use App\Models\User;
use App\Policies\Money\MoneyPolicy;
use App\Services\Platform\Ledger\CentsRatio;
use App\Services\Platform\Ledger\Money;
use App\Services\Platform\Tenancy\CurrentSchool;
use Ramsey\Uuid\Uuid;

final class FeeLeaverCalculator
{
    public function snapshot(User $actor, int $invoiceId, int $learner, string $operation): array
    {
        app(MoneyPolicy::class)->authorize($actor, FeeLeaverPolicies::PERMISSION, locked: true);
        $policy = FeeLeaverPolicy::lockForUpdate()->first();
        abort_unless($policy !== null, 422, 'Choose explicit leaver credit rules before reviewing a departure.');
        $invoice = Invoice::whereNotNull('sealed_at')->lockForUpdate()->findOrFail($invoiceId);
        $bill = BillRun::where('kind', 'annual')->where('state', 'posted')->lockForUpdate()->findOrFail($invoice->bill_run_id);
        $year = SessionYear::lockForUpdate()->findOrFail($invoice->session_year_id);
        $yearStart = $bill->snapshot['year_start'] ?? null;
        $yearEnd = $bill->snapshot['year_end'] ?? null;
        abort_unless(is_string($yearStart) && is_string($yearEnd) && preg_match('/\A\d{4}-\d{2}-\d{2}\z/D', $yearStart) && preg_match('/\A\d{4}-\d{2}-\d{2}\z/D', $yearEnd) && $yearStart <= $yearEnd, 409, 'The original billed school-year dates need reconciliation.');
        $account = FeeAccount::lockForUpdate()->findOrFail($invoice->fee_account_id);
        $lines = InvoiceLine::where('invoice_id', $invoiceId)->where('learner_id', $learner)->whereNull('fee_charge_id')->orderBy('id')->lockForUpdate()->get();
        abort_unless($lines->isNotEmpty(), 404);
        $source = collect($bill->snapshot['rows'])->firstWhere('learner_id', $learner);
        abort_unless($source !== null && $source['state'] === 'ready', 409, 'The original reviewed tuition source needs reconciliation.');
        $attendance = app(FeeEnrolmentPeriods::class)->continuous(Enrolment::where('learner_id', $learner)->where('session_year_id', $year->id)->orderBy('starts_on')->orderBy('id')->lockForUpdate()->get());
        abort_unless($attendance['issues'] === [], 422, 'Confirm a single continuous school attendance period before reviewing leaver credit.');
        $today = now(app(CurrentSchool::class)->school()->timezone ?? 'UTC')->toDateString();
        abort_unless($attendance['ends_on'] !== null && $attendance['ends_on'] <= $today, 422, 'The learner must have a confirmed departure dated today or earlier.');
        $originalStart = $source['enrolment_start'] ?? $yearStart;
        abort_unless($attendance['starts_on'] === $originalStart || (! isset($source['enrolment_start']) && $attendance['starts_on'] <= $originalStart), 409, 'The original joining dates changed. Review a billing correction separately.');
        if (isset($source['enrolment_id'])) {
            abort_unless(in_array($source['enrolment_id'], array_column($attendance['segments'], 'id'), true), 409, 'The original enrolment identity changed.');
        }
        $contract = $bill->snapshot['lifecycle_policy'] ?? null;
        $origin = $contract === null ? 'explicit_prior_policyless_rule' : 'original_bill_policy';
        if ($contract === null) {
            $contract = ['join_basis' => 'full_year', 'leaver_basis' => $policy->prior_policyless_basis, 'revision' => $policy->revision];
        }
        $originalGross = (int) $lines->sum('amount_cents');
        $annual = $source['annual_cents'] ?? $originalGross;
        $quote = app(FeeProration::class)->charge($annual, $yearStart, $yearEnd, $originalStart, $attendance['ends_on'], new FeeLifecyclePolicy(array_intersect_key($contract, array_flip(['join_basis', 'leaver_basis', 'revision']))));
        $served = min($originalGross, $quote['amount_cents']);
        $rows = array_values(array_filter(app(FeeStatements::class)->finance($account)['charges'], fn ($row) => $row['invoice_id'] === $invoiceId && $row['learner_id'] === $learner && $row['charge_kind'] === 'annual_tuition'));
        $originalByHolder = [];
        $currentByHolder = [];
        foreach ($rows as $row) {
            $id = $row['holder_user_id'];
            $originalByHolder[$id] = ($originalByHolder[$id] ?? 0) + $row['gross_charged_cents'];
            $currentByHolder[$id] = ($currentByHolder[$id] ?? 0) + $row['charged_cents'];
        }
        abort_unless(array_sum($originalByHolder) === $originalGross, 409, 'Original tuition shares need reconciliation.');
        $servedByHolder = FeeTaxAmounts::split($served, $originalByHolder);
        $prior = FeeCreditHistory::notes()->whereIn('n.invoice_line_id', $lines->pluck('id'))->orderBy('n.id')->get(['n.id', 'n.holder_user_id', 'n.reason_code', 'n.amount_cents']);
        $reductions = [];
        $other = [];
        $leaver = [];
        foreach ($prior as $note) {
            $id = (int) $note->holder_user_id;
            $value = (int) $note->amount_cents;
            switch ($note->reason_code) {
                case 'leaver_credit':$leaver[$id] = ($leaver[$id] ?? 0) + $value;
                    break;
                case 'fee_reduction':$reductions[$id] = ($reductions[$id] ?? 0) + $value;
                    break;
                default:$other[$id] = ($other[$id] ?? 0) + $value;
            }
        }
        $credits = [];
        $holders = [];
        $amount = 0;
        foreach ($originalByHolder as $holder => $gross) {
            $reduction = $reductions[$holder] ?? 0;
            if ($policy->prior_reductions === 'proportion_reductions' && $gross > 0) {
                $reduction = CentsRatio::portion($reduction, $servedByHolder[$holder], $gross);
            }
            $target = max(0, $servedByHolder[$holder] - $reduction - ($other[$holder] ?? 0));
            $credit = max(0, $currentByHolder[$holder] - $target);
            $holders[] = ['holder_user_id' => $holder, 'original_cents' => $gross, 'served_cents' => $servedByHolder[$holder], 'fee_reductions_before_cents' => $reductions[$holder] ?? 0, 'retained_reduction_cents' => $reduction, 'other_credits_before_cents' => $other[$holder] ?? 0, 'leaver_credits_before_cents' => $leaver[$holder] ?? 0, 'current_cents' => $currentByHolder[$holder], 'target_cents' => min($target, $currentByHolder[$holder]), 'credit_cents' => $credit];
            if ($credit === 0) {
                continue;
            }$weights = [];
            $byLine = [];
            foreach ($rows as $row) {
                if ($row['holder_user_id'] === $holder && $row['charged_cents'] > 0) {
                    $weights[$row['invoice_line_id']] = $row['charged_cents'];
                    $byLine[$row['invoice_line_id']] = $row;
                }
            }
            $slices = FeeTaxAmounts::split($credit, $weights);
            foreach ($slices as $line => $cents) {
                if ($cents === 0) {
                    continue;
                }$input = ['operation_id' => Uuid::uuid5(Uuid::NAMESPACE_URL, 'fee-leaver:'.$operation.':line:'.$line.':holder:'.$holder)->toString(), 'invoice_line_id' => $line, 'holder_user_id' => $holder, 'amount' => Money::ofCents($cents, $invoice->currency)->toDecimalString(), 'reason_code' => 'leaver_credit', 'reason' => 'Approved school departure fee adjustment'];
                $credits[] = ['input' => $input, 'preview' => app(FeeCreditNotes::class)->preview($actor, $input)];
                $amount += $cents;
            }
        }
        abort_unless($amount > 0, 422, 'No additional leaver credit is due under the original charge and the reviewed rule.');

        return ['fee_leaver_policy_id' => (int) $policy->id, 'policy' => $policy->only(['revision', 'prior_policyless_basis', 'prior_reductions', 'independent_approval']), 'policy_origin' => $origin, 'original_contract' => $contract, 'invoice_id' => $invoiceId, 'invoice_number' => $invoice->number, 'fee_account_id' => (int) $account->id, 'session_year_id' => (int) $year->id, 'learner_id' => $learner, 'currency' => $invoice->currency, 'original_gross_cents' => $originalGross, 'original_annual_cents' => $annual, 'served_cents' => $served, 'amount_cents' => $amount, 'attendance' => $attendance, 'quote' => $quote, 'holders' => $holders, 'prior_credit_ids' => $prior->pluck('id')->map(fn ($id) => (int) $id)->all(), 'credits' => $credits];
    }
}
