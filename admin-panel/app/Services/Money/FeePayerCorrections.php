<?php

declare(strict_types=1);

namespace App\Services\Money;

use App\Models\Money\FeeAccount;
use App\Models\Money\FeeAccountHolder;
use App\Models\Money\FeeCharge;
use App\Models\Money\FeePayerCorrection;
use App\Models\Money\Invoice;
use App\Models\Money\InvoiceLine;
use App\Models\User;
use App\Policies\Money\MoneyPolicy;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Ledger\Money;
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
            $invoiceSnapshot = $s + ['holder' => $s['new_holder'], 'credit_note_id' => (int) $credit->id, 'description' => 'Payer correction — original unpaid charge', 'receivable_account_id' => $s['credit']['receivable_account_id'], 'income_account_id' => $s['credit']['income_account_id'], 'vat_account_id' => $s['credit']['vat_account_id']];
            $charge = app(FeeAdjustmentInvoices::class)->post($actor, 'payer_correction', $invoiceSnapshot, $data['operation_id'], $data['fingerprint'], function (FeeCharge $charge) use ($s, $data, $credit, $actor): void {
                FeePayerCorrection::create(['fee_account_id' => $s['fee_account_id'], 'original_invoice_line_id' => $s['invoice_line_id'], 'original_holder_id' => $data['holder_user_id'], 'new_holder_id' => $data['new_holder_user_id'], 'credit_note_id' => $credit->id, 'replacement_charge_id' => $charge->id, 'currency' => $s['currency'], 'amount_cents' => $s['amount_cents'], 'vat_cents' => $s['vat_cents'], 'actor_id' => $actor->id, 'created_at' => now('UTC')]);
            });
            $correction = FeePayerCorrection::where('replacement_charge_id', $charge->id)->sole();
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
