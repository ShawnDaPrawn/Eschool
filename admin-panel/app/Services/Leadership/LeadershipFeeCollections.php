<?php

declare(strict_types=1);

namespace App\Services\Leadership;

use App\Models\FeesChoiceable;
use App\Models\FeesPaid;
use App\Models\PaidInstallmentFee;
use App\Models\PaymentTransaction;
use App\Models\People\Enrolment;
use App\Models\User;
use App\Services\Platform\Modules\ModuleGate;
use Illuminate\Support\Facades\DB;

/** Current recorded fee receipts, reconciled in cents; never a bank/ledger balance. */
class LeadershipFeeCollections
{
    private const MAX_CENTS = 9007199254740991;

    public function read(User $actor, int $year, string $start, string $end): array
    {
        $authority = app(LeadershipAuthority::class);
        $authority->principal($actor, $year);
        $gate = app(ModuleGate::class);
        $gate->forget();
        if (! $gate->allows('fees')) {
            return ['enabled' => false, 'events' => [], 'unavailable' => []];
        }

        // All three sources see one committed snapshot. Never add the running
        // fees_paids balance to individual transactions: that counts twice.
        $snapshot = DB::transaction(function () use ($year, $start, $end): array {
            $enrolments = Enrolment::with('homeroom.class')->where('session_year_id', $year)
                ->whereDate('starts_on', '<=', $end)
                ->where(fn ($q) => $q->whereNull('ends_on')->orWhereDate('ends_on', '>=', $start))->get();
            $scopes = $enrolments->filter(fn ($e) => $e->homeroom?->class?->grade_level_id !== null)
                ->groupBy(fn ($e) => $e->learner_id.':'.$e->homeroom->class_id);
            $byLearner = $enrolments->groupBy('learner_id');
            $learners = $enrolments->pluck('learner_id')->unique()->all();
            $receipts = PaymentTransaction::where('session_year_id', $year)->whereIn('student_id', $learners)
                ->where('payment_status', 1)->select(['id', 'student_id', 'class_id', 'session_year_id', 'type_of_fee', 'date', 'total_amount_cents'])->get()
                ->groupBy(fn ($r) => $r->student_id.':'.$r->class_id);
            $balances = FeesPaid::where('session_year_id', $year)->whereIn('student_id', $learners)
                ->select(['id', 'student_id', 'class_id', 'total_amount_cents'])->get()
                ->groupBy(fn ($r) => $r->student_id.':'.$r->class_id);
            $events = [];
            $unavailable = [];
            foreach ($scopes as $key => $roster) {
                $rows = $receipts->get($key, collect());
                $balance = $balances->get($key, collect());
                $total = 0;
                $valid = $balance->count() <= 1;
                foreach ($rows as $row) {
                    $cents = $this->cents($row->getRawOriginal('total_amount_cents'));
                    if ($cents === null || $cents > self::MAX_CENTS - $total) {
                        $valid = false;
                        break;
                    }
                    $total += $cents;
                }
                $expected = $balance->isEmpty() ? ($rows->isEmpty() ? 0 : null) : $this->cents($balance->first()->getRawOriginal('total_amount_cents'));
                if (! $valid || $expected === null || $total !== $expected) {
                    foreach ($roster as $e) {
                        $unavailable[(int) $e->homeroom->class->grade_level_id] = true;
                    }

                    continue;
                }
                foreach ($rows as $row) {
                    $date = $this->receiptDate($row);
                    if ($date === null) {
                        foreach ($roster as $e) {
                            $unavailable[(int) $e->homeroom->class->grade_level_id] = true;
                        }

                        continue;
                    }
                    if ($date < $start || $date > $end) {
                        continue;
                    }
                    $dated = $byLearner->get($row->student_id, collect())->filter(fn ($e) => $e->starts_on->toDateString() <= $date && ($e->ends_on === null || $e->ends_on->toDateString() >= $date));
                    if ($dated->count() > 1) {
                        foreach ($dated as $e) {
                            if ($e->homeroom?->class?->grade_level_id !== null) {
                                $unavailable[(int) $e->homeroom->class->grade_level_id] = true;
                            }
                        }
                    } elseif ($dated->count() === 1 && (int) $dated->sole()->homeroom?->class_id === (int) $row->class_id) {
                        $events[] = ['date' => $date, 'grade_id' => (int) $dated->sole()->homeroom->class->grade_level_id,
                            'cents' => $this->cents($row->getRawOriginal('total_amount_cents'))];
                    }
                }
            }

            return ['enabled' => true, 'events' => $events, 'unavailable' => array_keys($unavailable)];
        });
        $authority->principal($actor, $year);
        $gate->forget();

        return $gate->allows('fees') ? $snapshot : ['enabled' => false, 'events' => [], 'unavailable' => []];
    }

    public function amount(array $snapshot, int $grade, string $start, string $end): ?int
    {
        if (! $snapshot['enabled'] || in_array($grade, $snapshot['unavailable'], true)) {
            return null;
        }
        $sum = 0;
        foreach ($snapshot['events'] as $event) {
            if ($event['grade_id'] === $grade && $event['date'] >= $start && $event['date'] <= $end) {
                if ($event['cents'] > self::MAX_CENTS - $sum) {
                    return null;
                }
                $sum += $event['cents'];
            }
        }

        return $sum;
    }

    private function cents(mixed $raw): ?int
    {
        if ((! is_int($raw) && ! is_string($raw)) || ! preg_match('/^(0|[1-9][0-9]*)$/D', (string) $raw)) {
            return null;
        }
        $value = filter_var($raw, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => self::MAX_CENTS]]);

        return $value === false ? null : $value;
    }

    private function day(mixed $raw): ?string
    {
        if (! is_string($raw) || ! preg_match('/^([0-9]{4})-([0-9]{2})-([0-9]{2})(?: [0-9]{2}:[0-9]{2}:[0-9]{2})?$/D', $raw, $m)) {
            return null;
        }

        return checkdate((int) $m[2], (int) $m[3], (int) $m[1]) ? substr($raw, 0, 10) : null;
    }

    private function receiptDate(PaymentTransaction $receipt): ?string
    {
        if (! in_array((string) $receipt->getRawOriginal('type_of_fee'), ['0', '1', '2'], true)) {
            return null;
        }
        if ($receipt->getRawOriginal('date') !== null) {
            return $this->day($receipt->getRawOriginal('date'));
        }
        // Older optional office receipts omitted the transaction date. Their
        // exact settled components may supply it, never created_at or a guess.
        $type = (int) $receipt->type_of_fee;
        if (! in_array($type, [1, 2], true)) {
            return null;
        }
        $model = $type === 2 ? FeesChoiceable::class : PaidInstallmentFee::class;
        $column = $type === 2 ? 'total_amount_cents' : 'amount_cents';
        $parts = $model::where('payment_transaction_id', $receipt->id)->where('student_id', $receipt->student_id)
            ->where('class_id', $receipt->class_id)->where('session_year_id', $receipt->session_year_id)->where('status', 1)->get();
        $dates = [];
        $sum = 0;
        foreach ($parts as $part) {
            $date = $this->day($part->getRawOriginal('date'));
            $amount = $this->cents($part->getRawOriginal($column));
            $due = $type === 1 ? $this->cents($part->getRawOriginal('due_charges_cents') ?? 0) : 0;
            if ($date === null || $amount === null || $due === null || $amount > self::MAX_CENTS - $sum || $due > self::MAX_CENTS - $sum - $amount) {
                return null;
            }
            $sum += $amount + $due;
            $dates[$date] = true;
        }

        return count($dates) === 1 && $sum === $this->cents($receipt->getRawOriginal('total_amount_cents')) ? array_key_first($dates) : null;
    }
}
