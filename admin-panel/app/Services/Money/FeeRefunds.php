<?php

declare(strict_types=1);

namespace App\Services\Money;

use App\Events\Money\FeeRefundChanged;
use App\Models\Money\FeeAccount;
use App\Models\Money\FeePayment;
use App\Models\Money\FeeRefund;
use App\Models\Money\FeeRefundAction;
use App\Models\Money\FeeRefundPolicy;
use App\Models\User;
use App\Policies\Money\MoneyPolicy;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Events\Outbox;
use App\Services\Platform\Ledger\AccountType;
use App\Services\Platform\Ledger\LedgerService;
use App\Services\Platform\Ledger\Money;
use App\Services\Platform\Ledger\Posting;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\Validator;

final class FeeRefunds
{
    public function prepare(User $actor, array $input): FeeRefund
    {
        $data = Validator::make($input, self::prepareRules())->validate();
        $data['payment_id'] = (int) $data['payment_id'];
        $data['amount_cents'] = Money::fromDecimalString($data['amount'])->cents;
        unset($data['amount']);
        abort_unless($data['amount_cents'] > 0 && $data['amount_cents'] <= FeePlans::MAX_CENTS, 422, 'Use a positive supported refund amount.');
        $receipt = app(BillingOperations::class)->once($actor, FeeRefundPolicies::PERMISSION, 'fee_refund_prepare', $data, function () use ($actor, $data): array {
            $snapshot = $this->snapshot($data['payment_id'], $data['amount_cents'], $data['method']);
            $request = FeeRefund::create(['payment_id' => $data['payment_id'], 'fee_account_id' => $snapshot['fee_account_id'], 'holder_user_id' => $snapshot['holder_user_id'], 'pinned_learner_id' => $snapshot['pinned_learner_id'], 'fee_refund_policy_id' => $snapshot['fee_refund_policy_id'], 'operation_id' => $data['operation_id'], 'currency' => $snapshot['currency'], 'amount_cents' => $data['amount_cents'], 'method' => $data['method'], 'fingerprint' => $this->fingerprint($snapshot), 'snapshot' => $snapshot, 'private_explanation' => $data['private_explanation'], 'prepared_by' => $actor->id, 'created_at' => now('UTC')]);
            Audit::record('money.fee_refund_prepared', $request, [], ['payment_id' => $data['payment_id'], 'amount_cents' => $data['amount_cents']]);

            return ['id' => (int) $request->id];
        });

        return FeeRefund::findOrFail($receipt['id']);
    }

    public function reserve(User $actor, int $id, array $input): FeeRefund
    {
        $data = Validator::make($input, self::actionRules())->validate() + ['refund_id' => $id];
        $receipt = app(BillingOperations::class)->once($actor, FeeRefundPolicies::PERMISSION, 'fee_refund_reserve', $data, function () use ($actor, $id, $data): array {
            $request = FeeRefund::lockForUpdate()->findOrFail($id);
            abort_unless($this->state($request) === 'preview', 409, 'Only a current unreserved refund can be approved.');
            $snapshot = $this->snapshot((int) $request->payment_id, $request->amount_cents, $request->method);
            abort_unless(hash_equals($request->fingerprint, $this->fingerprint($snapshot)), 409, 'Receipt credit or refund rules changed. Prepare a fresh review.');
            abort_unless(! $snapshot['policy']['independent_approval'] || (int) $actor->id !== (int) $request->prepared_by, 403, 'Another Finance operator must approve this refund.');
            $ledger = app(LedgerService::class);
            $pending = $ledger->openAccount(AccountType::FeeRefund, 'fees:refund:'.$id, $request->currency);
            $identity = 'fee-refund:reserved:'.$id;
            $amount = Money::ofCents($request->amount_cents, $request->currency);
            $entry = $ledger->post($identity, $identity, 'Reserved original receipt refund', [Posting::debit($snapshot['credit_account_id'], $amount), Posting::credit($pending, $amount)]);
            $this->action($actor, $request, $data['operation_id'], 'reserved', $entry, $pending, $snapshot['credit_account_id']);
            $this->complete($request);

            return ['id' => $id];
        });

        return FeeRefund::findOrFail($receipt['id']);
    }

    public function payout(User $actor, int $id, array $input): FeeRefund
    {
        if (is_string($input['payout_reference'] ?? null)) {
            $input['payout_reference'] = FeePayments::reference($input['payout_reference']);
        }
        $data = Validator::make($input, self::payoutRules())->validate() + ['refund_id' => $id];
        $data['payout_reference'] = FeePayments::reference($data['payout_reference']);
        $receipt = app(BillingOperations::class)->once($actor, FeeRefundPolicies::PERMISSION, 'fee_refund_payout', $data, function () use ($actor, $id, $data): array {
            $request = FeeRefund::lockForUpdate()->findOrFail($id);
            $reserve = $this->reservation($request);
            $this->freshApprover($reserve);
            $policy = $this->policy($request->method);
            abort_unless(! $policy->independent_approval || (int) $reserve->actor_id !== (int) $request->prepared_by, 409, 'Refund approval policy changed. Cancel and review this refund again.');
            $this->complete($request);
            $hash = hash('sha256', json_encode([$request->method, $request->currency, $data['payout_reference']], JSON_THROW_ON_ERROR));
            abort_if(FeeRefundAction::where('reference_hash', $hash)->lockForUpdate()->exists(), 409, 'This verified payout reference is already recorded.');
            $ledger = app(LedgerService::class);
            $asset = $ledger->openAccount($request->method === 'cash' ? AccountType::CashDesk : AccountType::FeeBank, 'fees:'.$request->method.':'.$request->currency, $request->currency);
            abort_unless($ledger->balance($asset)->cents >= $request->amount_cents, 422, 'Reconcile and fund the recorded payout asset before making an outside refund.');
            $amount = Money::ofCents($request->amount_cents, $request->currency);
            $identity = 'fee-refund:paid:'.$id;
            $entry = $ledger->post($identity, $identity, 'Verified manual original-holder refund', [Posting::debit((int) $reserve->pending_account_id, $amount), Posting::credit($asset, $amount)]);
            $evidence = json_encode(['reference' => $data['payout_reference'], 'verification' => $data['private_evidence']], JSON_THROW_ON_ERROR);
            $this->action($actor, $request, $data['operation_id'], 'paid', $entry, (int) $reserve->pending_account_id, $asset, $evidence, $hash);
            $this->complete($request);

            return ['id' => $id];
        });

        return FeeRefund::findOrFail($receipt['id']);
    }

    public function cancel(User $actor, int $id, array $input): FeeRefund
    {
        $data = Validator::make($input, self::cancelRules())->validate() + ['refund_id' => $id];
        $receipt = app(BillingOperations::class)->once($actor, FeeRefundPolicies::PERMISSION, 'fee_refund_cancel', $data, function () use ($actor, $id, $data): array {
            $request = FeeRefund::lockForUpdate()->findOrFail($id);
            $reserve = $this->reservation($request);
            $this->complete($request);
            $payment = FeePayment::lockForUpdate()->findOrFail($request->payment_id);
            $amount = Money::ofCents($request->amount_cents, $request->currency);
            $identity = 'fee-refund:cancelled:'.$id;
            $entry = app(LedgerService::class)->post($identity, $identity, 'Cancelled original receipt refund reservation', [Posting::debit((int) $reserve->pending_account_id, $amount), Posting::credit((int) $payment->credit_account_id, $amount)]);
            $this->action($actor, $request, $data['operation_id'], 'cancelled', $entry, (int) $reserve->pending_account_id, (int) $payment->credit_account_id, $data['private_evidence']);
            $this->complete($request);

            return ['id' => $id];
        });

        return FeeRefund::findOrFail($receipt['id']);
    }

    public function state(FeeRefund $request): string
    {
        $actions = FeeRefundAction::where('fee_refund_id', $request->id)->orderBy('id')->lockForUpdate()->pluck('action')->all();
        if (in_array('cancelled', $actions, true)) {
            return 'cancelled';
        }if (in_array('paid', $actions, true)) {
            return 'paid';
        }

        return in_array('reserved', $actions, true) ? 'reserved' : 'preview';
    }

    private function snapshot(int $paymentId, int $amount, string $method): array
    {
        $policy = $this->policy($method);
        $payment = FeePayment::lockForUpdate()->findOrFail($paymentId);
        $account = FeeAccount::lockForUpdate()->findOrFail($payment->fee_account_id);
        app(FeeStatements::class)->complete($account);
        app(FeePayments::class)->verifyCredit($payment);
        $available = app(FeePayments::class)->available($payment);
        abort_unless($amount <= $available, 422, 'Refund only currently unallocated credit on the original receipt.');
        $holder = User::withTrashed()->findOrFail($payment->holder_user_id, ['id', 'first_name', 'last_name']);

        return ['payment_id' => $paymentId, 'payment_number' => 'FEE-PAY-'.$paymentId, 'fee_account_id' => (int) $account->id, 'account_name' => $account->name, 'holder_user_id' => $payment->holder_user_id, 'holder_name' => trim($holder->first_name.' '.$holder->last_name), 'pinned_learner_id' => $payment->pinned_learner_id, 'currency' => $payment->currency, 'amount_cents' => $amount, 'method' => $method, 'available_before_cents' => $available, 'available_after_cents' => $available - $amount, 'credit_account_id' => $payment->credit_account_id, 'original_receipt_cents' => $payment->amount_cents, 'fee_refund_policy_id' => (int) $policy->id, 'policy' => $policy->only(['revision', 'cash_allowed', 'eft_allowed', 'independent_approval'])];
    }

    private function policy(string $method): FeeRefundPolicy
    {
        $policy = FeeRefundPolicy::lockForUpdate()->first();
        abort_unless($policy !== null && ($method === 'cash' ? $policy->cash_allowed : $policy->eft_allowed), 422, 'Choose an explicitly permitted school cash or EFT refund method.');

        return $policy;
    }

    private function reservation(FeeRefund $request): FeeRefundAction
    {
        abort_unless($this->state($request) === 'reserved', 409, 'This refund no longer has a pending reservation.');

        return FeeRefundAction::where('fee_refund_id', $request->id)->where('action', 'reserved')->lockForUpdate()->firstOrFail();
    }

    private function freshApprover(FeeRefundAction $reserve): void
    {
        app(MoneyPolicy::class)->authorize(User::findOrFail($reserve->actor_id), FeeRefundPolicies::PERMISSION, locked: true);
    }

    private function action(User $actor, FeeRefund $request, string $operation, string $action, int $entry, int $pending, int $counter, ?string $evidence = null, ?string $referenceHash = null): FeeRefundAction
    {
        $row = FeeRefundAction::create(['fee_refund_id' => $request->id, 'action' => $action, 'operation_id' => $operation, 'actor_id' => $actor->id, 'amount_cents' => $request->amount_cents, 'currency' => $request->currency, 'ledger_entry_id' => $entry, 'pending_account_id' => $pending, 'counter_account_id' => $counter, 'reference_hash' => $referenceHash, 'private_evidence' => $evidence, 'created_at' => now('UTC')]);
        Audit::record('money.fee_refund_'.$action, $row, [], ['refund_id' => $request->id, 'amount_cents' => $request->amount_cents, 'ledger_entry_id' => $entry]);

        Outbox::record(new FeeRefundChanged(app(CurrentSchool::class)->id(), (int) $request->id, $request->amount_cents, $action));

        return $row;
    }

    private function complete(FeeRefund $request): void
    {
        app(FeePayments::class)->verifyCredit(FeePayment::lockForUpdate()->findOrFail($request->payment_id));
        app(FeeStatements::class)->complete(FeeAccount::findOrFail($request->fee_account_id));
    }

    private function fingerprint(array $snapshot): string
    {
        return hash_hmac('sha256', json_encode($snapshot, JSON_THROW_ON_ERROR), (string) config('app.key'));
    }

    public static function prepareRules(): array
    {
        return ['operation_id' => ['required', 'uuid'], 'payment_id' => ['required', 'integer', 'min:1'], 'amount' => ['required', 'string', 'regex:/\A\d{1,10}(?:\.\d{1,2})?\z/D'], 'method' => ['required', 'in:cash,eft'], 'private_explanation' => ['required', 'string', 'min:5', 'max:1200']];
    }

    public static function actionRules(): array
    {
        return ['operation_id' => ['required', 'uuid'], 'confirmed' => ['required', 'accepted']];
    }

    public static function payoutRules(): array
    {
        return self::actionRules() + ['payout_reference' => ['required', 'string', 'max:96', 'regex:/\A[A-Za-z0-9][A-Za-z0-9 ._:\\/\-]{0,95}\z/D'], 'private_evidence' => ['required', 'string', 'min:5', 'max:1200'], 'verified_original_holder' => ['required', 'accepted']];
    }

    public static function cancelRules(): array
    {
        return self::actionRules() + ['private_evidence' => ['required', 'string', 'min:5', 'max:1200']];
    }
}
