<?php

declare(strict_types=1);

namespace App\Services\Money;

use App\Models\Money\FeeLifecyclePolicy;
use App\Services\Platform\Ledger\CentsRatio;
use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;

/** Inclusive enrolment dates; touched calendar months are charged in full. */
final class FeeProration
{
    public function charge(int $annual, string $yearStart, string $yearEnd, string $starts, ?string $ends, ?FeeLifecyclePolicy $policy): array
    {
        $a = $this->date($yearStart);
        $b = $this->date($yearEnd);
        $start = max($this->date($starts), $a);
        $end = min($this->date($ends ?? $yearEnd), $b);
        if ($annual < 0 || $annual > FeePlans::MAX_CENTS || $b < $a || $end < $start || $start > $b || $end < $a) {
            throw new InvalidArgumentException('Use a supported annual amount and confirmed overlapping enrolment dates.');
        }
        if ($annual === 0) {
            return ['amount_cents' => 0, 'period_start' => $start->format('Y-m-d'), 'period_end' => $end->format('Y-m-d'), 'proration' => null];
        }
        if ($start == $a && $end == $b) {
            return ['amount_cents' => $annual, 'period_start' => $yearStart, 'period_end' => $yearEnd, 'proration' => null];
        }
        abort_unless($policy !== null, 422, 'Configure an explicit join and leaver fee rule before billing partial enrolments.');
        $days = $a->diff($b)->days + 1;
        $months = $this->months($a, $b) + 1;
        $denominator = $days * $months;
        $before = match ($policy->join_basis) {
            'daily' => $a->diff($start)->days * $months,'calendar_month' => $this->months($a, $start) * $days,'full_year' => 0,default => throw new InvalidArgumentException('Unknown join rule.')
        };
        $after = match ($policy->leaver_basis) {
            'daily' => $end->diff($b)->days * $months,'calendar_month' => $this->months($end, $b) * $days,'no_credit' => 0,default => throw new InvalidArgumentException('Unknown leaver rule.')
        };
        $units = $denominator - $before - $after;
        abort_unless($units >= 0 && $units <= $denominator, 422, 'The configured enrolment rule needs review.');
        $amount = CentsRatio::portion($annual, $units, $denominator);
        $periodStart = match ($policy->join_basis) {
            'daily' => $start,'calendar_month' => max($a, $start->modify('first day of this month')),'full_year' => $a
        };
        $periodEnd = match ($policy->leaver_basis) {
            'daily' => $end,'calendar_month' => min($b, $end->modify('last day of this month')),'no_credit' => $b
        };

        return ['amount_cents' => $amount, 'period_start' => $periodStart->format('Y-m-d'), 'period_end' => $periodEnd->format('Y-m-d'), 'proration' => ['annual_cents' => $annual, 'year_start' => $yearStart, 'year_end' => $yearEnd, 'enrolment_start' => $starts, 'enrolment_end' => $ends, 'join_basis' => $policy->join_basis, 'leaver_basis' => $policy->leaver_basis, 'policy_revision' => $policy->revision, 'charge_units' => $units, 'total_units' => $denominator, 'amount_cents' => $amount]];
    }

    private function date(string $value): DateTimeImmutable
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value, new DateTimeZone('UTC'));
        if ($date === false || $date->format('Y-m-d') !== $value) {
            throw new InvalidArgumentException('Use a real date in YYYY-MM-DD format.');
        }

        return $date;
    }

    private function months(DateTimeImmutable $a, DateTimeImmutable $b): int
    {
        return ((int) $b->format('Y') - (int) $a->format('Y')) * 12 + (int) $b->format('n') - (int) $a->format('n');
    }
}
