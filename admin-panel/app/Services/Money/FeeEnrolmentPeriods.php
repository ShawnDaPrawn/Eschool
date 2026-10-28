<?php

declare(strict_types=1);

namespace App\Services\Money;

use App\Models\People\Enrolment;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Support\Collection;

/** Homeroom moves retain continuous school attendance; gaps need an explicit review. */
final class FeeEnrolmentPeriods
{
    /** @param Collection<int,Enrolment> $rows */
    public function continuous(Collection $rows): array
    {
        $ordered = $rows->sortBy(fn (Enrolment $row) => $row->starts_on->toDateString().':'.str_pad((string) $row->id, 20, '0', STR_PAD_LEFT))->values();
        $first = $ordered->first();
        abort_unless($first !== null, 422, 'Confirm dated school enrolment before calculating charges.');
        $issues = [];
        $previous = null;
        $segments = [];
        foreach ($ordered as $row) {
            $start = $row->starts_on->toDateString();
            $end = $row->ends_on?->toDateString();
            if ($row->legacy_dates) {
                $issues[] = 'Confirm imported enrolment dates';
            }
            if ($end !== null && $end < $start) {
                $issues[] = 'Confirm school enrolment dates';
            }
            if ($previous !== null) {
                if ($previous['end'] === null || $previous['end'] >= $start) {
                    $issues[] = 'Overlapping enrolments';
                } elseif ((new DateTimeImmutable($previous['end'], new DateTimeZone('UTC')))->modify('+1 day')->format('Y-m-d') !== $start) {
                    $issues[] = 'Multiple school attendance periods need a reviewed charge';
                }
            }
            $segments[] = ['id' => (int) $row->id, 'starts_on' => $start, 'ends_on' => $end, 'class_section_id' => (int) $row->class_section_id, 'legacy_dates' => $row->legacy_dates];
            $previous = ['end' => $end];
        }

        return ['starts_on' => $first->starts_on->toDateString(), 'ends_on' => $ordered->last()->ends_on?->toDateString(), 'segments' => $segments, 'issues' => array_values(array_unique($issues))];
    }
}
