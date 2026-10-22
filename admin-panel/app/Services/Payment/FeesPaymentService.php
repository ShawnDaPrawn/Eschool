<?php

declare(strict_types=1);

namespace App\Services\Payment;

use App\Models\FeesChoiceable;
use App\Models\FeesClass;
use App\Models\FeesPaid;
use App\Models\InstallmentFee;
use App\Models\PaidInstallmentFee;
use App\Models\PaymentTransaction;
use App\Models\SessionYear;
use App\Models\StudentSessions;
use App\Services\Platform\Ledger\CentsColumns;
use App\Services\Platform\Ledger\LegacyAmount;
use App\Services\Platform\Ledger\Money;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Online fee payments, worked out on the server.
 *
 * prepare() prices a payment request from the app using the fee setup (what
 * the class owes, installments, due dates, what is already paid); the app's
 * amount is only compared against it. markPaid()/markFailed() record what a
 * gateway webhook confirmed, using only the stored transaction and the rows
 * created with it, never the webhook's metadata.
 *
 * Amounts are worked out in integer cents. An installment's share of the
 * fees (and its due charges) is kept as an exact fraction until the one
 * rounding, half up, so totals are what they always were; the float values
 * in the plan are for the v1 API and the gateway metadata only.
 */
class FeesPaymentService
{
    public const COMPULSORY = 0;

    public const INSTALLMENTS = 1;

    public const OPTIONAL = 2;

    /** Rounding slack, in cents, between the app's total and ours. */
    private const AMOUNT_TOLERANCE_CENTS = 2;

    private const DUE_NO = -1;

    private const DUE_MAYBE = 0;

    private const DUE_YES = 1;

    /**
     * Check and price a fee payment request.
     *
     * @param  array  $optionalFees  the app's optional_fees_data: [['id' => fees_type_id, ...], ...]
     * @param  array  $installments  the app's installment_data: [['id' => installment_fee_id, ...], ...]
     * @param  mixed  $clientAmount  the total the app showed the payer; it must match ours
     * @return array{
     *     student_id: int,
     *     class_id: int,
     *     session_year_id: int,
     *     type_of_fee: int,
     *     total_amount: float,
     *     total_amount_cents: int,
     *     is_fully_paid: int,
     *     due_charges: float|null,
     *     due_charges_cents: int|null,
     *     optional_fees: list<array{fees_type_id: int, amount: float}>,
     *     installments: list<array{installment_fee_id: int, amount: float, due_charges: float|null}>
     * }
     *
     * @throws ValidationException with a message for the payer when the request can't be paid
     */
    public function prepare(int $studentId, int $sessionYearId, int $typeOfFee, array $optionalFees = [], array $installments = [], $clientAmount = null): array
    {
        $studentSession = StudentSessions::where('student_id', $studentId)
            ->where('session_year_id', $sessionYearId)
            ->with('class_section')
            ->first();
        $sessionYear = SessionYear::find($sessionYearId);
        if (! $studentSession || ! $studentSession->class_section || ! $sessionYear) {
            $this->fail('No session found for this student in the current session year');
        }
        $classId = (int) $studentSession->class_section->class_id;
        $scope = ['student_id' => $studentId, 'class_id' => $classId, 'session_year_id' => $sessionYearId];

        $compulsoryFullyPaid = FeesPaid::where($scope)->where('is_fully_paid', 1)->exists();

        $plan = [
            'student_id' => $studentId,
            'class_id' => $classId,
            'session_year_id' => $sessionYearId,
            'type_of_fee' => $typeOfFee,
            'total_amount' => 0.0,
            'total_amount_cents' => 0,
            'is_fully_paid' => 0,
            'due_charges' => null,
            'due_charges_cents' => null,
            'optional_fees' => [],
            'installments' => [],
        ];

        // Each entry: [amount, due charges, due state, id], amounts in cents times
        // $denominator (an installment is an exact share of the fees). Due charges
        // whose due date falls on the boundary (the app may be a timezone ahead or
        // behind) are accepted either way, whichever matches what the app showed.
        $items = [];
        $denominator = 1;

        switch ($typeOfFee) {
            case self::COMPULSORY:
                if ($compulsoryFullyPaid) {
                    $this->fail('Compulsory fees are already paid.');
                }
                if (PaidInstallmentFee::where($scope)->where('status', 1)->exists()) {
                    $this->fail('Installments have been started; please pay the remaining installments.');
                }
                $base = $this->compulsoryTotal($classId, $sessionYearId);
                $percent = (int) $sessionYear->fee_due_charges;
                $denominator = 100;
                $due = ($percent > 0 && $sessionYear->fee_due_date) ? $base * $percent : 0;
                $items[] = [$base * 100, $due, $due > 0 ? $this->dueState($sessionYear->fee_due_date) : self::DUE_NO, null];
                $plan['is_fully_paid'] = 1;
                break;

            case self::INSTALLMENTS:
                if ($compulsoryFullyPaid) {
                    $this->fail('Compulsory fees are already paid.');
                }
                $all = InstallmentFee::where('session_year_id', $sessionYearId)->orderBy('id')->get()->keyBy('id');
                $ids = $this->ids($installments, 'Select at least one installment.');
                foreach ($ids as $id) {
                    if (! $all->has($id)) {
                        $this->fail('Invalid installment selected.');
                    }
                }
                $paidIds = PaidInstallmentFee::where($scope)->where('status', 1)->pluck('installment_fee_id')->map(fn ($id) => (int) $id)->unique();
                if ($paidIds->intersect($ids)->isNotEmpty()) {
                    $this->fail('This installment is already paid.');
                }
                // Each installment is base / count, its due charges that times percent / 100.
                $base = $this->compulsoryTotal($classId, $sessionYearId);
                $denominator = $all->count() * 100;
                foreach ($ids as $id) {
                    $installment = $all->get($id);
                    $percent = (int) $installment->due_charges;
                    $due = $percent > 0 ? $base * $percent : 0;
                    $items[] = [$base * 100, $due, $due > 0 ? $this->dueState($installment->getRawOriginal('due_date')) : self::DUE_NO, $id];
                }
                $plan['is_fully_paid'] = $paidIds->merge($ids)->unique()->count() >= $all->count() ? 1 : 0;
                break;

            case self::OPTIONAL:
                $ids = $this->ids($optionalFees, 'Select at least one optional fee.');
                $fees = FeesClass::where(['class_id' => $classId, 'choiceable' => 1, 'session_year_id' => $sessionYearId])
                    ->whereIn('fees_type_id', $ids)
                    ->get()
                    ->keyBy(fn ($fee) => (int) $fee->fees_type_id);
                foreach ($ids as $id) {
                    if (! $fees->has($id)) {
                        $this->fail('Invalid optional fee selected.');
                    }
                }
                if (FeesChoiceable::where($scope)->where('status', 1)->whereIn('fees_type_id', $ids)->exists()) {
                    $this->fail('This optional fee is already paid.');
                }
                foreach ($ids as $id) {
                    $items[] = [(int) $fees->get($id)->money('amount')?->cents, 0, self::DUE_NO, $id];
                }
                $plan['is_fully_paid'] = $compulsoryFullyPaid ? 1 : 0;
                break;

            default:
                $this->fail('Invalid fee type.');
        }

        $withDueCharges = $this->chooseDueCharges($items, $denominator, $clientAmount);

        $total = 0;
        $dueTotal = 0;
        foreach ($items as $index => $item) {
            [$amount, $due] = $item;
            $due = $withDueCharges[$index] ? $due : 0;
            $total += $amount + $due;
            $dueTotal += $due;

            $amountCents = Money::ofFraction($amount, $denominator)->cents;
            if ($typeOfFee === self::INSTALLMENTS) {
                $dueCents = $due > 0 ? Money::ofFraction($due, $denominator)->cents : null;
                $plan['installments'][] = [
                    'installment_fee_id' => $item[3],
                    'amount' => LegacyAmount::fromCents($amountCents),
                    'due_charges' => LegacyAmount::fromCents($dueCents),
                ];
            } elseif ($typeOfFee === self::OPTIONAL) {
                $plan['optional_fees'][] = ['fees_type_id' => $item[3], 'amount' => LegacyAmount::fromCents($amountCents)];
            }
        }

        $plan['total_amount_cents'] = Money::ofFraction($total, $denominator)->cents;
        $plan['total_amount'] = LegacyAmount::fromCentsOrZero($plan['total_amount_cents']);
        if ($typeOfFee === self::COMPULSORY && $dueTotal > 0) {
            $plan['due_charges_cents'] = Money::ofFraction($dueTotal, $denominator)->cents;
            $plan['due_charges'] = LegacyAmount::fromCents($plan['due_charges_cents']);
        }

        if ($plan['total_amount_cents'] <= 0) {
            $this->fail('There are no fees to pay.');
        }

        return $plan;
    }

    /**
     * Create the pending transaction and the rows it pays for.
     *
     * @return array{transaction: PaymentTransaction, optional_fee_ids: list<int>, installment_ids: list<int>}
     */
    public function createPendingTransaction(array $plan, $parentId, int $paymentMethod): array
    {
        $transaction = new PaymentTransaction;
        $transaction->student_id = $plan['student_id'];
        $transaction->parent_id = $parentId;
        $transaction->class_id = $plan['class_id'];
        $transaction->mode = 2;
        $transaction->type_of_fee = $plan['type_of_fee'];
        $transaction->payment_gateway = $paymentMethod;
        $transaction->payment_status = 2;
        $transaction->total_amount_cents = $plan['total_amount_cents'];
        $transaction->date = date('Y-m-d H:i:s');
        $transaction->session_year_id = $plan['session_year_id'];
        $transaction->save();

        $optionalFeeIds = [];
        foreach ($plan['optional_fees'] as $fee) {
            $optionalFeeIds[] = FeesChoiceable::insertGetId([
                'student_id' => $plan['student_id'],
                'class_id' => $plan['class_id'],
                'fees_type_id' => $fee['fees_type_id'],
                'is_due_charges' => 0,
                'total_amount' => $fee['amount'],
                'total_amount_cents' => LegacyAmount::toCents($fee['amount']),
                'session_year_id' => $plan['session_year_id'],
                'date' => date('Y-m-d'),
                'payment_transaction_id' => $transaction->id,
                'status' => 0,
            ]);
        }

        $installmentIds = [];
        foreach ($plan['installments'] as $installment) {
            $installmentIds[] = PaidInstallmentFee::insertGetId([
                'class_id' => $plan['class_id'],
                'student_id' => $plan['student_id'],
                'parent_id' => $parentId,
                'installment_fee_id' => $installment['installment_fee_id'],
                'session_year_id' => $plan['session_year_id'],
                'amount' => $installment['amount'],
                'amount_cents' => LegacyAmount::toCents($installment['amount']),
                'due_charges' => $installment['due_charges'],
                'due_charges_cents' => LegacyAmount::toCents($installment['due_charges']),
                'date' => date('Y-m-d'),
                'payment_transaction_id' => $transaction->id,
                'status' => 0,
            ]);
        }

        return ['transaction' => $transaction, 'optional_fee_ids' => $optionalFeeIds, 'installment_ids' => $installmentIds];
    }

    /**
     * The metadata sent to the gateway with a payment (informational only:
     * webhooks don't trust it).
     *
     * @param  array{email?: mixed, name?: mixed, mobile?: mixed}  $payer
     */
    public function gatewayMetadata(array $plan, array $pending, $parentId, array $payer): array
    {
        return [
            'student_id' => $plan['student_id'],
            'parent_id' => $parentId,
            'class_id' => $plan['class_id'],
            'email' => $payer['email'] ?? null,
            'name' => $payer['name'] ?? null,
            'mobile' => $payer['mobile'] ?? '',
            'session_year_id' => $plan['session_year_id'],
            'payment_transaction_id' => $pending['transaction']->id,
            'is_fully_paid' => $plan['is_fully_paid'],
            'type_of_fee' => $plan['type_of_fee'],
            'is_due_charges' => $plan['due_charges'] ? 1 : 0,
            'due_charges' => $plan['due_charges'],
            'optional_fees_paid' => json_encode($pending['optional_fee_ids']),
            'installment_fees_paid' => json_encode($pending['installment_ids']),
        ];
    }

    /**
     * Record a payment the gateway confirmed. Safe to call again for the same
     * transaction (retried or duplicate webhooks): only the first call counts.
     *
     * @param  array<string, mixed>  $attributes  gateway ids to store on the transaction (e.g. payment_id)
     * @return PaymentTransaction|null the transaction when it was marked paid now, null if unknown or already paid
     */
    public function markPaid(int $transactionId, array $attributes = []): ?PaymentTransaction
    {
        return DB::transaction(function () use ($transactionId, $attributes) {
            $transaction = PaymentTransaction::lockForUpdate()->find($transactionId);
            if (! $transaction || (int) $transaction->payment_status === 1) {
                return null;
            }

            foreach ($attributes as $key => $value) {
                $transaction->{$key} = $value;
            }
            $transaction->payment_status = 1;
            $transaction->save();

            // The rows created with the transaction, including any a failed
            // attempt on the same order removed.
            $optionalFees = FeesChoiceable::withTrashed()
                ->where('payment_transaction_id', $transaction->id)
                ->where('is_due_charges', 0)
                ->get();
            $installments = PaidInstallmentFee::withTrashed()
                ->where('payment_transaction_id', $transaction->id)
                ->get();
            foreach ($optionalFees->concat($installments) as $row) {
                if ($row->trashed()) {
                    $row->restore();
                }
                $row->status = 1;
                $row->save();
            }

            $scope = [
                'student_id' => $transaction->student_id,
                'class_id' => $transaction->class_id,
                'session_year_id' => $transaction->session_year_id,
            ];

            $dueCharges = null;
            if ((int) $transaction->type_of_fee === self::COMPULSORY) {
                $dueCharges = $this->compulsoryDueCharges($transaction);
                if ($dueCharges !== null) {
                    FeesChoiceable::create($scope + [
                        'is_due_charges' => 1,
                        'total_amount_cents' => $dueCharges,
                        'date' => date('Y-m-d'),
                        'payment_transaction_id' => $transaction->id,
                        'status' => 1,
                    ]);
                }
            }

            $feesPaid = FeesPaid::where($scope)->lockForUpdate()->first();
            $isFullyPaid = $this->isFullyPaidAfter($transaction, $feesPaid);
            $paid = $transaction->money('total_amount') ?? Money::zero();
            if ($feesPaid) {
                $feesPaid->total_amount_cents = ($feesPaid->money('total_amount') ?? Money::zero())->plus($paid);
                $feesPaid->is_fully_paid = $isFullyPaid;
                $feesPaid->save();
            } else {
                $feesPaid = new FeesPaid;
                $feesPaid->parent_id = $transaction->parent_id;
                $feesPaid->student_id = $transaction->student_id;
                $feesPaid->class_id = $transaction->class_id;
                $feesPaid->payment_transaction_id = $transaction->id;
                $feesPaid->mode = 2;
                $feesPaid->total_amount_cents = $paid;
                $feesPaid->date = date('Y-m-d');
                $feesPaid->session_year_id = $transaction->session_year_id;
                $feesPaid->is_fully_paid = $isFullyPaid;
                $feesPaid->due_charges_cents = $dueCharges;
                $feesPaid->save();
            }

            return $transaction;
        });
    }

    /**
     * Record a failed payment. A transaction that is already paid is left alone
     * (a late failure event for an earlier attempt must not undo a payment).
     *
     * @return PaymentTransaction|null the transaction when it was pending until now
     */
    public function markFailed(int $transactionId, array $attributes = []): ?PaymentTransaction
    {
        return DB::transaction(function () use ($transactionId, $attributes) {
            $transaction = PaymentTransaction::lockForUpdate()->find($transactionId);
            if (! $transaction || (int) $transaction->payment_status === 1) {
                return null;
            }
            $wasPending = (int) $transaction->payment_status === 2;

            foreach ($attributes as $key => $value) {
                $transaction->{$key} = $value;
            }
            $transaction->payment_status = 0;
            $transaction->save();

            FeesChoiceable::where('payment_transaction_id', $transaction->id)->where('status', 0)->delete();
            PaidInstallmentFee::where('payment_transaction_id', $transaction->id)->where('status', 0)->delete();

            return $wasPending ? $transaction : null;
        });
    }

    /** The class's compulsory fees for the year, in cents. */
    private function compulsoryTotal(int $classId, int $sessionYearId): int
    {
        $total = (int) FeesClass::where(['class_id' => $classId, 'choiceable' => 0, 'session_year_id' => $sessionYearId])->sum(DB::raw(CentsColumns::sql('amount', 'amount_cents')));
        if ($total <= 0) {
            $this->fail('There are no compulsory fees to pay.');
        }

        return $total;
    }

    /**
     * Due charges paid with a full compulsory payment, in cents: what the
     * transaction charged above the class's compulsory fees.
     */
    private function compulsoryDueCharges(PaymentTransaction $transaction): ?int
    {
        $base = (int) FeesClass::where([
            'class_id' => $transaction->class_id,
            'choiceable' => 0,
            'session_year_id' => $transaction->session_year_id,
        ])->sum(DB::raw(CentsColumns::sql('amount', 'amount_cents')));

        $due = (int) $transaction->money('total_amount')?->cents - $base;

        return ($base > 0 && $due > 0) ? $due : null;
    }

    private function isFullyPaidAfter(PaymentTransaction $transaction, ?FeesPaid $feesPaid): int
    {
        $current = (int) ($feesPaid->is_fully_paid ?? 0);

        switch ((int) $transaction->type_of_fee) {
            case self::COMPULSORY:
                return 1;
            case self::INSTALLMENTS:
                $total = InstallmentFee::where('session_year_id', $transaction->session_year_id)->count();
                $paid = PaidInstallmentFee::where([
                    'student_id' => $transaction->student_id,
                    'class_id' => $transaction->class_id,
                    'session_year_id' => $transaction->session_year_id,
                    'status' => 1,
                ])->distinct()->count('installment_fee_id');

                return ($current === 1 || ($total > 0 && $paid >= $total)) ? 1 : 0;
            default:
                return $current;
        }
    }

    /**
     * Whether a due date has passed. The app decides this in the payer's
     * timezone, so the day before and the day of the due date can go either way.
     */
    private function dueState(?string $dueDate): int
    {
        if (empty($dueDate)) {
            return self::DUE_NO;
        }
        $days = (int) round(Carbon::parse($dueDate)->startOfDay()->diffInDays(Carbon::now()->startOfDay(), false));

        if ($days >= 1) {
            return self::DUE_YES;
        }

        return $days <= -2 ? self::DUE_NO : self::DUE_MAYBE;
    }

    /**
     * Which items carry their due charges: definitely-due ones always; the
     * boundary ones only if that is the total the app showed.
     *
     * @return array<int, bool>
     */
    private function chooseDueCharges(array $items, int $denominator, $clientAmount): array
    {
        if (! is_numeric($clientAmount)) {
            $this->fail('Invalid amount.');
        }
        $clientCents = (int) LegacyAmount::toCents($clientAmount);

        $options = [false];
        foreach ($items as $item) {
            if ($item[2] === self::DUE_MAYBE) {
                $options = [false, true];
                break;
            }
        }

        foreach ($options as $includeBoundary) {
            $charged = [];
            $total = 0;
            foreach ($items as $index => $item) {
                $charged[$index] = $item[2] === self::DUE_YES || ($includeBoundary && $item[2] === self::DUE_MAYBE);
                $total += $item[0] + ($charged[$index] ? $item[1] : 0);
            }
            if (abs(Money::ofFraction($total, $denominator)->cents - $clientCents) <= self::AMOUNT_TOLERANCE_CENTS) {
                return $charged;
            }
        }

        $this->fail('The fee amount has changed. Please refresh and try again.');
    }

    /**
     * Distinct ids from the app's list of selected fees.
     *
     * @return list<int>
     */
    private function ids(array $rows, string $emptyMessage): array
    {
        // Keyed by id so the duplicate check stays linear for long lists.
        $ids = [];
        foreach ($rows as $row) {
            $id = is_array($row) ? ($row['id'] ?? null) : $row;
            if (! is_numeric($id) || (int) $id <= 0 || (string) (int) $id !== (string) $id) {
                $this->fail('Invalid fee selected.');
            }
            if (isset($ids[(int) $id])) {
                $this->fail('The same fee was selected twice.');
            }
            $ids[(int) $id] = true;
        }
        if ($ids === []) {
            $this->fail($emptyMessage);
        }

        return array_keys($ids);
    }

    /**
     * @throws ValidationException
     */
    private function fail(string $message): never
    {
        throw ValidationException::withMessages(['fees' => $message]);
    }
}
