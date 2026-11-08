<?php

declare(strict_types=1);

namespace App\Services\Learning\Motivation;

use App\Models\Holiday;
use App\Models\Term;
use Carbon\CarbonImmutable;

/**
 * Which calendar days are school days at the current school (Action Plan
 * 3.13). A school day is a Monday to Friday inside one of the school's terms
 * that is not one of its recorded holidays or closures. A school that has not
 * dated any term yet counts every weekday that is not a holiday. Only this
 * school's terms and holidays are read.
 */
class SchoolDays
{
    /** @return array<string, bool> Y-m-d => whether it is a school day, for every day from $from to $to */
    public function between(string $from, string $to): array
    {
        $terms = Term::query()->whereNotNull('start_date')->whereNotNull('end_date')
            ->whereDate('start_date', '<=', $to)->whereDate('end_date', '>=', $from)->get(['start_date', 'end_date'])
            ->filter(fn (Term $t) => $t->start_date->lte($t->end_date));
        $dated = $terms->isNotEmpty() || Term::query()->whereNotNull('start_date')->whereNotNull('end_date')->exists();
        $holidays = Holiday::query()->whereDate('date', '>=', $from)->whereDate('date', '<=', $to)->pluck('date')
            ->map(fn ($date) => substr((string) $date, 0, 10))->flip();
        $days = [];
        $end = CarbonImmutable::parse($to);
        for ($day = CarbonImmutable::parse($from); $day->lte($end); $day = $day->addDay()) {
            $date = $day->toDateString();
            $inTerm = ! $dated || $terms->contains(fn (Term $t) => $t->start_date->toDateString() <= $date && $t->end_date->toDateString() >= $date);
            $days[$date] = ! $day->isWeekend() && $inTerm && ! isset($holidays[$date]);
        }

        return $days;
    }
}
