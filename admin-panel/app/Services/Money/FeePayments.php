<?php

declare(strict_types=1);

namespace App\Services\Money;

use App\Events\Money\FeeMoneyChanged;
use App\Models\Money\FeeAccount;
use App\Models\Money\FeeAccountHolder;
use App\Models\Money\FeeAccountLearner;
use App\Models\Money\FeePayment;
use App\Models\Money\Invoice;
use App\Models\User;
use App\Policies\Money\MoneyPolicy;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Events\Outbox;
use App\Services\Platform\Ledger\AccountType;
use App\Services\Platform\Ledger\LedgerService;
use App\Services\Platform\Ledger\Money;
use App\Services\Platform\Ledger\Posting;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

final class FeePayments
{
    public const PERMISSION = 'fee-payments-manage';

    public function record(User $actor, array $input): FeePayment
    {
        app(MoneyPolicy::class)->authorize($actor, self::PERMISSION);
        if (is_string($input['source_reference'] ?? null)) {
            $input['source_reference'] = self::reference($input['source_reference']);
        }
        $data = Validator::make($input, self::rules())->validate();
        $data['amount_cents'] = Money::fromDecimalString($data['amount'])->cents;
        abort_unless($data['amount_cents'] > 0 && $data['amount_cents'] <= FeePlans::MAX_CENTS, 422, 'Use a positive supported receipt amount.');
        $data['pinned_learner_id'] = empty($data['pinned_learner_id']) ? null : (int) $data['pinned_learner_id'];
        $data['fee_account_id'] = (int) $data['fee_account_id'];
        $data['holder_user_id'] = (int) $data['holder_user_id'];
        unset($data['amount']);
        $receipt = app(BillingOperations::class)->once($actor, self::PERMISSION, 'fee_receipt', $data, function () use ($actor, $data): array {
            $account = FeeAccount::lockForUpdate()->findOrFail($data['fee_account_id']);
            abort_unless(array_key_exists($data['holder_user_id'], $this->holders($account)), 422, 'Choose an explicitly recorded holder of this family account.');
            abort_unless($data['received_on'] <= $this->today(), 422, 'Only confirmed cash or bank receipts dated today or earlier may be recorded.');
            if ($data['pinned_learner_id'] !== null) {
                abort_unless(in_array($data['pinned_learner_id'], $this->eligibleLearners($account, $data['holder_user_id']), true), 422, 'The learner must belong to this holder and family account.');
            }
            app(ExternalReceipts::class)->assertAvailable($data['method'], $data['source_reference']);
            abort_if(FeePayment::where('method', $data['method'])->where('source_reference', $data['source_reference'])->lockForUpdate()->first() !== null, 409, 'This cash receipt or bank transaction is already recorded.');
            $ledger = app(LedgerService::class);
            $asset = $ledger->openAccount($data['method'] === 'eft' ? AccountType::FeeBank : AccountType::CashDesk, 'fees:'.$data['method'].':'.$account->currency, $account->currency);
            // This is a family/holder liability, not a personal wallet. Its owner
            // and access are the immutable payment attribution and statement policy.
            $credit = $ledger->openAccount(AccountType::Family, 'fees:credit:'.$account->id.':holder:'.$data['holder_user_id'], $account->currency);
            $source = 'fee-receipt:'.$data['method'].':'.hash('sha256', $data['source_reference']);
            $entry = $ledger->post($source, $source, 'Verified manual fee receipt', [Posting::debit($asset, Money::ofCents($data['amount_cents'], $account->currency)), Posting::credit($credit, Money::ofCents($data['amount_cents'], $account->currency))]);
            $payment = FeePayment::create(['fee_account_id' => $account->id, 'holder_user_id' => $data['holder_user_id'], 'pinned_learner_id' => $data['pinned_learner_id'],
                'method' => $data['method'], 'provider' => 'manual', 'source_reference' => $data['source_reference'], 'status' => 'settled', 'collected_by' => 'school',
                'settlement_reference' => $data['method'] === 'eft' ? $data['source_reference'] : null, 'currency' => $account->currency,
                'amount_cents' => $data['amount_cents'], 'fee_cents' => 0, 'net_cents' => $data['amount_cents'], 'received_on' => $data['received_on'],
                'debit_account_id' => $asset, 'credit_account_id' => $credit, 'ledger_entry_id' => $entry, 'created_by' => $actor->id, 'created_at' => now('UTC')]);
            Audit::record('money.fee_payment_recorded', $payment, [], ['amount_cents' => $payment->amount_cents, 'method' => $payment->method, 'ledger_entry_id' => $entry]);
            Outbox::record(new FeeMoneyChanged(app(CurrentSchool::class)->id(), (int) $payment->id, $payment->amount_cents));

            return ['id' => (int) $payment->id];
        });

        return FeePayment::findOrFail($receipt['id']);
    }

    public function holders(FeeAccount $account): array
    {
        $ids = FeeAccountHolder::where('fee_account_id', $account->id)->pluck('user_id')->map(fn ($id) => (int) $id)->all();
        $names = [];
        foreach (Invoice::where('fee_account_id', $account->id)->whereNotNull('sealed_at')->orderBy('id')->get(['holders']) as $invoice) {
            foreach ($invoice->holders as $holder) {
                $ids[] = $holder['user_id'];
                $names[$holder['user_id']] = $holder['name'];
            }
        }
        foreach (User::withTrashed()->whereIn('id', array_unique($ids))->lockForUpdate()->get(['id', 'first_name', 'last_name']) as $user) {
            $names[(int) $user->id] = trim($user->first_name.' '.$user->last_name);
        }
        ksort($names);

        return $names;
    }

    public function eligibleLearners(FeeAccount $account, int $holder): array
    {
        $years = FeeAccountHolder::where('fee_account_id', $account->id)->where('user_id', $holder)->pluck('session_year_id');
        $ids = FeeAccountLearner::where('fee_account_id', $account->id)->whereIn('session_year_id', $years)->pluck('learner_id')->map(fn ($id) => (int) $id)->all();
        foreach (app(FeeStatements::class)->charges($account, $holder) as $row) {
            $ids[] = $row['learner_id'];
        }

        return array_values(array_unique($ids));
    }

    public function available(FeePayment $payment): int
    {
        $school = app(CurrentSchool::class)->id();
        $used = (int) DB::table('payment_allocations')->where('school_id', $school)->where('payment_id', $payment->id)->lockForUpdate()->sum('amount_cents');
        $used -= (int) FeeCreditHistory::releases()->where('c.payment_id', $payment->id)->sum('c.amount_cents');
        abort_unless($used >= 0 && $used <= $payment->amount_cents, 409, 'Payment allocations need reconciliation.');

        return $payment->amount_cents - $used - app(FeeRefundHistory::class)->heldForPayment((int) $payment->id);
    }

    public function verifyCredit(FeePayment $payment): void
    {
        $school = app(CurrentSchool::class)->id();
        $total = (int) FeePayment::where('credit_account_id', $payment->credit_account_id)->lockForUpdate()->sum('amount_cents');
        $used = (int) DB::table('payment_allocations as a')->join('payments as p', function ($j) {
            $j->on('p.school_id', '=', 'a.school_id')->on('p.id', '=', 'a.payment_id');
        })->where('a.school_id', $school)->where('p.credit_account_id', $payment->credit_account_id)->lockForUpdate()->sum('a.amount_cents');
        $used -= (int) FeeCreditHistory::releases()->where('p.credit_account_id', $payment->credit_account_id)->sum('c.amount_cents');
        $heldRefunds = app(FeeRefundHistory::class)->heldForCredit((int) $payment->credit_account_id);
        abort_unless($total - $used - $heldRefunds === app(LedgerService::class)->balance($payment->credit_account_id)->cents, 409, 'Held payment credits differ from the ledger. Reconcile before continuing.');
    }

    private function today(): string
    {
        return now(app(CurrentSchool::class)->school()->timezone ?? 'UTC')->toDateString();
    }

    public static function reference(string $value): string
    {
        return strtoupper(preg_replace('/ +/', ' ', trim($value)) ?? '');
    }

    public static function rules(): array
    {
        return ['operation_id' => ['required', 'uuid'], 'fee_account_id' => ['required', 'integer', 'min:1'], 'holder_user_id' => ['required', 'integer', 'min:1'],
            'pinned_learner_id' => ['nullable', 'integer', 'min:1'], 'method' => ['required', 'in:cash,eft'],
            'source_reference' => ['required', 'string', 'max:96', 'regex:/\A[A-Z0-9][A-Z0-9 ._:\\/\-]{0,95}\z/D'],
            'amount' => ['required', 'string', 'regex:/\A\d{1,10}(?:\.\d{1,2})?\z/D'], 'received_on' => ['required', 'date_format:Y-m-d'],
            'confirmed' => ['required', 'accepted']];
    }
}
