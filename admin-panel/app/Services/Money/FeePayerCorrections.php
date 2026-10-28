<?php

declare(strict_types=1);

namespace App\Services\Money;

use App\Models\Money\BillRun;
use App\Models\Money\FeeAccount;
use App\Models\Money\FeeAccountHolder;
use App\Models\Money\FeeCharge;
use App\Models\Money\FeePayerCorrection;
use App\Models\Money\Invoice;
use App\Models\Money\InvoiceLine;
use App\Models\User;
use App\Policies\Money\MoneyPolicy;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Ledger\LedgerService;
use App\Services\Platform\Ledger\Money;
use App\Services\Platform\Ledger\Posting;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Ramsey\Uuid\Uuid;

/** Unpaid debt only. Original invoices, allocations and receipt credits never move. */
final class FeePayerCorrections
{
    public const PERMISSION = 'fee-credits-manage';

    public function authorize(User $actor, bool $write = false): void
    {
        app(MoneyPolicy::class)->authorize($actor, self::PERMISSION, $write, locked: true);
        app(MoneyPolicy::class)->authorize($actor, 'fee-billing-manage', $write, locked: true);
    }

    public function preview(User $actor, array $input): array
    {
        $data = $this->validated($input);

        return DB::transaction(function () use ($actor, $data): array {
            DB::table('schools')->where('id', app(CurrentSchool::class)->id())->lockForUpdate()->firstOrFail();
            $this->authorize($actor);
            $s = $this->snapshot($actor, $data);

            return $s + ['fingerprint' => $this->fingerprint($s)];
        });
    }

    public function post(User $actor, array $input): FeePayerCorrection
    {
        $data = $this->validated($input, true);
        $this->authorize($actor);
        $receipt = app(BillingOperations::class)->once($actor, self::PERMISSION, 'payer_correction', $data, function () use ($actor, $data): array {
            $this->authorize($actor, true);
            $s = $this->snapshot($actor, $data);
            abort_unless(hash_equals($data['fingerprint'], $this->fingerprint($s)), 409, 'The unpaid debt or intended payer changed. Review a fresh correction.');
            $credit = app(FeeCreditNotes::class)->post($actor, $this->creditInput($data) + ['fingerprint' => $s['credit']['fingerprint'], 'confirmed' => true]);
            abort_unless($credit->unpaid_cents === $s['amount_cents'] && $credit->released_cents === 0, 409, 'A payer correction cannot move paid receipt credit.');
            $account = FeeAccount::lockForUpdate()->findOrFail($s['fee_account_id']);
            $money = fn (int $cents) => Money::ofCents($cents, $s['currency']);
            $postings = [Posting::debit($s['credit']['receivable_account_id'], $money($s['amount_cents']))];
            if ($s['net_cents'] > 0) {
                $postings[] = Posting::credit($s['credit']['income_account_id'], $money($s['net_cents']));
            }
            if ($s['vat_cents'] > 0) {
                $postings[] = Posting::credit($s['credit']['vat_account_id'], $money($s['vat_cents']));
            }
            $source = 'fee-payer-correction:'.$data['operation_id'];
            $entry = app(LedgerService::class)->post($source, $source, 'Unpaid payer debt correction', $postings);
            $runSnapshot = ['year_id' => $s['session_year_id'], 'as_of' => $s['issued_on'], 'due_on' => $s['due_on'], 'tax_treatment' => $s['tax_treatment'], 'rows' => [], 'totals' => [$s['currency'] => $s['amount_cents']], 'exceptions' => [], 'learners' => 1, 'billable' => 1, 'skipped' => 0, 'account_count' => 1];
            $run = BillRun::create(['kind' => 'payer_correction', 'session_year_id' => $s['session_year_id'], 'as_of' => $s['issued_on'], 'due_on' => $s['due_on'], 'tax_treatment' => $s['tax_treatment'], 'state' => 'preview', 'revision' => 1, 'fingerprint' => $data['fingerprint'], 'snapshot' => $runSnapshot, 'prepared_by' => $actor->id]);
            $line = ['learner_id' => $s['learner_id'], 'learner_name' => $s['learner_name'], 'amount_cents' => $s['amount_cents'], 'vat_cents' => $s['vat_cents'], 'holder_shares' => [$s['new_holder']]];
            $snapshot = $s + ['lines' => [$line], 'credit_note_id' => (int) $credit->id];
            $charge = FeeCharge::create(['kind' => 'payer_correction', 'fee_item_id' => null, 'fee_account_id' => $account->id, 'session_year_id' => $s['session_year_id'], 'learner_id' => $s['learner_id'], 'bill_run_id' => $run->id, 'item_revision' => 1, 'scope' => 'learner', 'target_id' => $s['learner_id'], 'source_reference' => $source, 'reference_hash' => hash_hmac('sha256', $source, (string) config('app.key')), 'fingerprint' => $data['fingerprint'], 'currency' => $s['currency'], 'amount_cents' => $s['amount_cents'], 'vat_cents' => $s['vat_cents'], 'income_account_id' => $s['credit']['income_account_id'], 'vat_account_id' => $s['credit']['vat_account_id'], 'snapshot' => $snapshot, 'created_by' => $actor->id, 'created_at' => now('UTC')]);
            $run->update(['state' => 'approved', 'revision' => 2, 'approved_by' => $actor->id, 'approved_at' => now('UTC')]);
            $holder = $s['new_holder'];
            $holder['total_cents'] = $holder['amount_cents'];
            unset($holder['amount_cents']);
            $invoice = Invoice::create(['fee_account_id' => $account->id, 'bill_run_id' => $run->id, 'session_year_id' => $s['session_year_id'], 'ledger_entry_id' => $entry, 'number' => 'CORR-'.$charge->id, 'account_name' => $account->name, 'currency' => $s['currency'], 'total_cents' => $s['amount_cents'], 'holders' => [$holder], 'issued_on' => $s['issued_on'], 'due_on' => $s['due_on'], 'created_by' => $actor->id, 'created_at' => now('UTC')]);
            InvoiceLine::create(['invoice_id' => $invoice->id, 'session_year_id' => $s['session_year_id'], 'fee_charge_id' => $charge->id, 'fee_plan_id' => null, 'plan_revision' => null, 'learner_id' => $s['learner_id'], 'learner_name' => $s['learner_name'], 'description' => 'Payer correction — original unpaid charge', 'period_start' => $s['period_start'], 'period_end' => $s['period_end'], 'currency' => $s['currency'], 'amount_cents' => $s['amount_cents'], 'vat_cents' => $s['vat_cents'], 'holder_shares' => [$s['new_holder']]]);
            $invoice->update(['sealed_at' => now('UTC')]);
            $run->update(['state' => 'posted', 'revision' => 3, 'posted_at' => now('UTC')]);
            $correction = FeePayerCorrection::create(['fee_account_id' => $account->id, 'original_invoice_line_id' => $s['invoice_line_id'], 'original_holder_id' => $data['holder_user_id'], 'new_holder_id' => $data['new_holder_user_id'], 'credit_note_id' => $credit->id, 'replacement_charge_id' => $charge->id, 'currency' => $s['currency'], 'amount_cents' => $s['amount_cents'], 'vat_cents' => $s['vat_cents'], 'actor_id' => $actor->id, 'created_at' => now('UTC')]);
            $charge->update(['sealed_at' => now('UTC')]);
            app(FeeStatements::class)->complete($account);
            Audit::record('money.payer_debt_corrected', $correction, [], ['amount_cents' => $s['amount_cents'], 'credit_note_id' => $credit->id, 'replacement_charge_id' => $charge->id]);

            return ['id' => (int) $correction->id];
        });

        return FeePayerCorrection::findOrFail($receipt['id']);
    }

    private function snapshot(User $actor, array $data): array
    {
        $line = InvoiceLine::lockForUpdate()->findOrFail($data['invoice_line_id']);
        $invoice = Invoice::whereNotNull('sealed_at')->lockForUpdate()->findOrFail($line->invoice_id);
        $account = FeeAccount::where('active', true)->lockForUpdate()->findOrFail($invoice->fee_account_id);
        abort_unless(FeeAccountHolder::where('fee_account_id', $account->id)->where('session_year_id', $invoice->session_year_id)->where('user_id', $data['new_holder_user_id'])->lockForUpdate()->exists(), 422, 'Choose an explicitly configured current payer on this family account.');
        $holder = app(FeeAccounts::class)->holders([['user_id' => $data['new_holder_user_id'], 'share_bp' => 10000]], [(int) $line->learner_id])[0];
        $credit = app(FeeCreditNotes::class)->preview($actor, $this->creditInput($data));
        abort_unless($credit['unpaid_cents'] === $data['amount_cents'] && $credit['released_cents'] === 0, 422, 'Correct only this original holder’s unpaid debt. Paid money and receipt credit cannot move.');
        $holder += ['amount_cents' => $data['amount_cents'], 'vat_cents' => $credit['vat_cents']];
        $originalCharge = $line->fee_charge_id === null ? null : FeeCharge::whereNotNull('sealed_at')->findOrFail($line->fee_charge_id);

        return ['fee_account_id' => (int) $account->id, 'account_revision' => (int) $account->revision, 'session_year_id' => (int) $invoice->session_year_id, 'invoice_line_id' => (int) $line->id, 'learner_id' => (int) $line->learner_id, 'learner_name' => $line->learner_name, 'currency' => $line->currency, 'amount_cents' => $data['amount_cents'], 'vat_cents' => $credit['vat_cents'], 'net_cents' => $data['amount_cents'] - $credit['vat_cents'], 'new_holder' => $holder, 'period_start' => $line->period_start->toDateString(), 'period_end' => $line->period_end->toDateString(), 'due_on' => $line->getAttribute('installment_due_on') ?? $invoice->due_on->toDateString(), 'issued_on' => $credit['issued_on'], 'tax_treatment' => $originalCharge?->snapshot['tax_treatment'] ?? 'no_vat', 'tax_details' => $credit['tax_details'], 'credit' => $credit];
    }

    private function creditInput(array $data): array
    {
        return ['operation_id' => Uuid::uuid5(Uuid::NAMESPACE_URL, 'navigateed:unpaid-payer-credit:'.$data['operation_id'])->toString(), 'invoice_line_id' => $data['invoice_line_id'], 'holder_user_id' => $data['holder_user_id'], 'amount' => $data['amount'], 'reason_code' => 'billing_error', 'reason' => $data['reason']];
    }

    private function validated(array $input, bool $post = false): array
    {
        $data = Validator::make($input, self::rules($post))->validate();
        foreach (['invoice_line_id', 'holder_user_id', 'new_holder_user_id'] as $id) {
            $data[$id] = (int) $data[$id];
        }
        $data['amount_cents'] = Money::fromDecimalString($data['amount'])->cents;
        abort_unless($data['amount_cents'] > 0 && $data['amount_cents'] <= FeePlans::MAX_CENTS && $data['holder_user_id'] !== $data['new_holder_user_id'], 422, 'Choose a different payer and a positive supported unpaid amount.');

        return $data;
    }

    private function fingerprint(array $snapshot): string
    {
        return hash_hmac('sha256', json_encode($snapshot, JSON_THROW_ON_ERROR), (string) config('app.key'));
    }

    public static function rules(bool $post = false): array
    {
        return ['operation_id' => ['required', 'uuid'], 'invoice_line_id' => ['required', 'integer', 'min:1'], 'holder_user_id' => ['required', 'integer', 'min:1'], 'new_holder_user_id' => ['required', 'integer', 'min:1'], 'amount' => ['required', 'string', 'regex:/\A\d{1,10}(?:\.\d{1,2})?\z/D'], 'reason' => ['required', 'string', 'min:10', 'max:1200']] + ($post ? ['fingerprint' => ['required', 'string', 'regex:/\A[a-f0-9]{64}\z/D'], 'confirmed' => ['required', 'accepted']] : []);
    }
}
