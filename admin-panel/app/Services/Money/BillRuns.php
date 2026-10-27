<?php

declare(strict_types=1);

namespace App\Services\Money;

use App\Models\Money\BillRun;
use App\Models\Money\FeeAccount;
use App\Models\Money\Invoice;
use App\Models\Money\InvoiceLine;
use App\Models\SessionYear;
use App\Models\User;
use App\Policies\Money\MoneyPolicy;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Ledger\AccountType;
use App\Services\Platform\Ledger\LedgerService;
use App\Services\Platform\Ledger\Money;
use App\Services\Platform\Ledger\Posting;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\Validator;

final class BillRuns
{
    public const PERMISSION = 'fee-billing-manage';

    public function prepare(User $actor, array $input): BillRun
    {
        app(MoneyPolicy::class)->authorize($actor, self::PERMISSION);
        $data = Validator::make($input, self::prepareRules())->validate();
        $receipt = app(BillingOperations::class)->once($actor, self::PERMISSION, 'bill_prepare', $data, function () use ($actor, $data): array {
            $snapshot = app(BillingPreview::class)->build($actor, (int) $data['session_year_id'], $data['as_of'], $data['due_on'], $data['tax_treatment']);
            $run = BillRun::create(['session_year_id' => $data['session_year_id'], 'as_of' => $data['as_of'], 'due_on' => $data['due_on'], 'tax_treatment' => $data['tax_treatment'],
                'state' => 'preview', 'revision' => 1, 'fingerprint' => $this->fingerprint($snapshot), 'snapshot' => $snapshot, 'prepared_by' => $actor->id]);
            Audit::record('money.bill_run_prepared', $run, [], ['learners' => $snapshot['learners'], 'billable' => $snapshot['billable'], 'exceptions' => array_sum($snapshot['exceptions'])]);

            return ['id' => (int) $run->id, 'revision' => 1];
        });

        return BillRun::findOrFail($receipt['id']);
    }

    public function approve(User $actor, int $id, array $input): BillRun
    {
        app(MoneyPolicy::class)->authorize($actor, self::PERMISSION);
        $data = Validator::make($input, self::operationRules())->validate() + ['run_id' => $id];
        $receipt = app(BillingOperations::class)->once($actor, self::PERMISSION, 'bill_approve', $data, function () use ($actor, $id, $data): array {
            $run = BillRun::where('kind', 'annual')->lockForUpdate()->findOrFail($id);
            abort_unless($run->state === 'preview' && $run->revision === (int) $data['revision'], 409, 'This bill run changed. Review its current state.');
            $this->verify($actor, $run);
            $run->update(['state' => 'approved', 'revision' => $run->revision + 1, 'approved_by' => $actor->id, 'approved_at' => now('UTC')]);
            Audit::record('money.bill_run_approved', $run, [], ['revision' => $run->revision, 'fingerprint' => $run->fingerprint]);

            return ['id' => $id, 'revision' => $run->revision];
        });

        return BillRun::findOrFail($receipt['id']);
    }

    public function post(User $actor, int $id, array $input): BillRun
    {
        app(MoneyPolicy::class)->authorize($actor, self::PERMISSION);
        $data = Validator::make($input, self::postingRules())->validate() + ['run_id' => $id];
        $receipt = app(BillingOperations::class)->once($actor, self::PERMISSION, 'bill_post', $data, function () use ($actor, $id, $data): array {
            $run = BillRun::where('kind', 'annual')->lockForUpdate()->findOrFail($id);
            abort_unless($run->state === 'approved' && $run->revision === (int) $data['revision'], 409, 'Approve and review the current bill run before posting.');
            $year = SessionYear::where('default', 1)->lockForUpdate()->first();
            abort_unless($year !== null && (int) $year->id === (int) $run->session_year_id, 422, 'Only the current school year can post invoices.');
            $snapshot = $this->verify($actor, $run);
            $groups = [];
            foreach ($snapshot['rows'] as $row) {
                if ($row['state'] === 'ready') {
                    $groups[$row['account_id']][] = $row;
                }
            }
            ksort($groups);
            $invoices = [];
            $issued = now(app(CurrentSchool::class)->school()->timezone ?? 'UTC')->toDateString();
            foreach ($groups as $accountId => $rows) {
                $invoices[] = $this->invoice($actor, $run, (int) $accountId, $rows, $issued);
            }
            $run->update(['state' => 'posted', 'revision' => $run->revision + 1, 'posted_at' => now('UTC')]);
            Audit::record('money.bill_run_posted', $run, [], ['revision' => $run->revision, 'invoice_count' => count($invoices), 'fingerprint' => $run->fingerprint]);

            return ['id' => $id, 'revision' => $run->revision, 'invoice_ids' => $invoices];
        });

        return BillRun::findOrFail($receipt['id']);
    }

    private function verify(User $actor, BillRun $run): array
    {
        $fresh = app(BillingPreview::class)->build($actor, (int) $run->session_year_id, $run->as_of->toDateString(), $run->due_on->toDateString(), $run->tax_treatment);
        abort_unless(hash_equals($run->fingerprint, $this->fingerprint($fresh)), 409, 'Billing inputs changed. Prepare and review a fresh preview.');
        abort_unless($fresh['exceptions'] === [] && $fresh['billable'] > 0, 422, 'Resolve every bill-run exception before approval or posting.');

        return $fresh;
    }

    private function invoice(User $actor, BillRun $run, int $accountId, array $rows, string $issued): int
    {
        $account = FeeAccount::lockForUpdate()->findOrFail($accountId);
        $total = array_sum(array_column($rows, 'amount_cents'));
        $ledger = app(LedgerService::class);
        $receivable = $ledger->openAccount(AccountType::FeeReceivable, 'fees:account:'.$accountId.':receivable', $account->currency);
        $income = $ledger->openAccount(AccountType::FeeIncome, 'fees:income:'.$account->currency, $account->currency);
        $account->update(['ledger_account_id' => $receivable]);
        $identity = 'bill-run:'.$run->id.':account:'.$accountId;
        $entry = $ledger->post($identity, $identity, 'Annual tuition invoice', [Posting::debit($receivable, Money::ofCents($total, $account->currency)), Posting::credit($income, Money::ofCents($total, $account->currency))]);
        $holders = [];
        $lines = [];
        foreach ($rows as $row) {
            $shares = BillingPreview::shares($row['amount_cents'], $row['holders']);
            foreach ($shares as $h) {
                $holders[$h['user_id']] ??= $h + ['total_cents' => 0];
                $holders[$h['user_id']]['total_cents'] += $h['amount_cents'];
            }
            $lines[] = ['session_year_id' => $run->session_year_id, 'learner_id' => $row['learner_id'], 'fee_plan_id' => $row['plan_id'], 'plan_revision' => $row['plan_revision'],
                'learner_name' => $row['name'], 'description' => $row['plan_names'][0], 'period_start' => $run->snapshot['year_start'], 'period_end' => $run->snapshot['year_end'],
                'currency' => $row['currency'], 'amount_cents' => $row['amount_cents'], 'vat_cents' => 0, 'holder_shares' => $shares];
        }
        foreach ($holders as &$holder) {
            unset($holder['amount_cents']);
        }
        unset($holder);
        $invoice = Invoice::create(['fee_account_id' => $accountId, 'bill_run_id' => $run->id, 'session_year_id' => $run->session_year_id, 'ledger_entry_id' => $entry,
            'number' => 'BILL-'.$run->id.'-'.$accountId, 'account_name' => $account->name, 'currency' => $account->currency, 'total_cents' => $total,
            'holders' => array_values($holders), 'issued_on' => $issued, 'due_on' => $run->due_on->toDateString(), 'created_by' => $actor->id, 'created_at' => now('UTC')]);
        foreach ($lines as $line) {
            InvoiceLine::create(['invoice_id' => $invoice->id, ...$line]);
        }
        $invoice->update(['sealed_at' => now('UTC')]);

        return (int) $invoice->id;
    }

    private function fingerprint(array $snapshot): string
    {
        return hash_hmac('sha256', json_encode($snapshot, JSON_THROW_ON_ERROR), (string) config('app.key'));
    }

    public static function prepareRules(): array
    {
        return ['operation_id' => ['required', 'uuid'], 'session_year_id' => ['required', 'integer', 'min:1'], 'as_of' => ['required', 'date_format:Y-m-d'],
            'due_on' => ['required', 'date_format:Y-m-d', 'after_or_equal:as_of'], 'tax_treatment' => ['required', 'in:no_vat']];
    }

    public static function operationRules(): array
    {
        return ['operation_id' => ['required', 'uuid'], 'revision' => ['required', 'integer', 'min:1']];
    }

    public static function postingRules(): array
    {
        return self::operationRules() + ['confirmed' => ['required', 'accepted']];
    }
}
