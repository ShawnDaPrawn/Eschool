<?php

declare(strict_types=1);

namespace App\Services\Money;

use App\Events\Money\FeeMoneyChanged;
use App\Models\Money\FeeAccount;
use App\Models\Money\FeePayment;
use App\Models\Money\FeePaymentPolicy;
use App\Models\Money\PaymentAllocation;
use App\Models\Money\PaymentAllocationRun;
use App\Models\User;
use App\Policies\Money\MoneyPolicy;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Events\Outbox;
use App\Services\Platform\Ledger\LedgerService;
use App\Services\Platform\Ledger\Money;
use App\Services\Platform\Ledger\Posting;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

final class FeeAllocations
{
    public function preview(User $actor, int $payment): array
    {
        return DB::transaction(function () use ($actor, $payment): array {
            DB::table('schools')->where('id', app(CurrentSchool::class)->id())->lockForUpdate()->firstOrFail();
            app(MoneyPolicy::class)->authorize($actor, FeePayments::PERMISSION, locked: true);
            $record = FeePayment::lockForUpdate()->findOrFail($payment);
            $snapshot = $this->snapshot($record);

            return $snapshot + ['fingerprint' => $this->fingerprint($snapshot)];
        });
    }

    public function post(User $actor, int $id, array $input): PaymentAllocationRun
    {
        app(MoneyPolicy::class)->authorize($actor, FeePayments::PERMISSION);
        $data = Validator::make($input, self::rules())->validate() + ['payment_id' => $id];
        $receipt = app(BillingOperations::class)->once($actor, FeePayments::PERMISSION, 'fee_allocate', $data, function () use ($actor, $id, $data): array {
            $payment = FeePayment::lockForUpdate()->findOrFail($id);
            $snapshot = $this->snapshot($payment);
            abort_unless(hash_equals($this->fingerprint($snapshot), $data['fingerprint']), 409, 'Credit, charges or allocation policy changed. Review a new preview.');
            abort_unless($snapshot['amount_cents'] > 0, 422, 'There are no eligible unpaid charges for this held credit.');
            $account = FeeAccount::lockForUpdate()->findOrFail($payment->fee_account_id);
            abort_unless($account->ledger_account_id !== null, 409, 'The invoice receivable account needs reconciliation.');
            $source = 'fee-allocation:'.$payment->id.':'.$data['fingerprint'];
            $entry = app(LedgerService::class)->post($source, $source, 'Allocate held fee payment to invoice shares', [
                Posting::debit($payment->credit_account_id, Money::ofCents($snapshot['amount_cents'], $payment->currency)),
                Posting::credit((int) $account->ledger_account_id, Money::ofCents($snapshot['amount_cents'], $payment->currency)),
            ]);
            $run = PaymentAllocationRun::create(['payment_id' => $payment->id, 'ledger_entry_id' => $entry, 'allocation_order' => $snapshot['allocation_order'],
                'policy_revision' => $snapshot['policy_revision'], 'fingerprint' => $data['fingerprint'], 'snapshot' => $snapshot, 'amount_cents' => $snapshot['amount_cents'], 'created_by' => $actor->id, 'created_at' => now('UTC')]);
            foreach ($snapshot['rows'] as $row) {
                PaymentAllocation::create(['allocation_run_id' => $run->id, 'payment_id' => $payment->id, 'invoice_line_id' => $row['invoice_line_id'], 'amount_cents' => $row['amount_cents'], 'created_at' => now('UTC')]);
            }
            $run->update(['sealed_at' => now('UTC')]);
            app(FeePayments::class)->verifyCredit($payment);
            Audit::record('money.fee_payment_allocated', $run, [], ['payment_id' => $payment->id, 'amount_cents' => $snapshot['amount_cents'], 'ledger_entry_id' => $entry]);
            Outbox::record(new FeeMoneyChanged(app(CurrentSchool::class)->id(), (int) $payment->id, $snapshot['amount_cents'], (int) $run->id));

            return ['id' => (int) $run->id];
        });

        return PaymentAllocationRun::findOrFail($receipt['id']);
    }

    private function snapshot(FeePayment $payment): array
    {
        $account = FeeAccount::lockForUpdate()->findOrFail($payment->fee_account_id);
        app(FeeStatements::class)->complete($account);
        app(FeePayments::class)->verifyCredit($payment);
        $policy = FeePaymentPolicy::lockForUpdate()->first();
        $available = app(FeePayments::class)->available($payment);
        $left = $available;
        $rows = [];
        if ($policy !== null) {
            // All currently supported charges are tuition. Future fee items
            // must add their category priority before tuition_first differs.
            foreach (app(FeeStatements::class)->charges($account, $payment->holder_user_id) as $charge) {
                if ($left === 0) {
                    break;
                }
                if ($charge['outstanding_cents'] <= 0 || ($payment->pinned_learner_id !== null && $payment->pinned_learner_id !== $charge['learner_id'])) {
                    continue;
                }
                $cents = min($left, $charge['outstanding_cents']);
                $rows[] = ['invoice_line_id' => $charge['invoice_line_id'], 'invoice_number' => $charge['invoice_number'], 'learner_id' => $charge['learner_id'],
                    'learner_name' => $charge['learner_name'], 'due_on' => $charge['due_on'], 'outstanding_cents' => $charge['outstanding_cents'], 'amount_cents' => $cents];
                $left -= $cents;
            }
        }

        return ['payment_id' => (int) $payment->id, 'account_id' => (int) $account->id, 'holder_user_id' => $payment->holder_user_id, 'pinned_learner_id' => $payment->pinned_learner_id,
            'currency' => $payment->currency, 'allocation_order' => $policy?->allocation_order, 'policy_revision' => (int) ($policy->revision ?? 0),
            'available_cents' => $available, 'amount_cents' => $available - $left, 'credit_left_cents' => $left, 'rows' => $rows];
    }

    private function fingerprint(array $snapshot): string
    {
        return hash_hmac('sha256', json_encode($snapshot, JSON_THROW_ON_ERROR), (string) config('app.key'));
    }

    public static function rules(): array
    {
        return ['operation_id' => ['required', 'uuid'], 'fingerprint' => ['required', 'string', 'regex:/\A[a-f0-9]{64}\z/D'], 'confirmed' => ['required', 'accepted']];
    }
}
