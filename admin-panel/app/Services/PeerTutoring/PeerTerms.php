<?php

declare(strict_types=1);

namespace App\Services\PeerTutoring;

use App\Models\Academics\GradeLevel;
use App\Models\SessionYear;
use App\Models\Term;
use App\Services\Academics\AcademicAuthority;
use App\Services\Platform\Tenancy\CurrentSchool;
use Carbon\CarbonImmutable;

/**
 * "This term" for a grade (Action Plan 4.7), used both to favour learners who
 * have not tutored yet and for the school measure. It is the term covering
 * today for the grade. Between terms it is the term that began most recently,
 * running on to the end of the school year. Before any term has begun it is
 * the whole school year. Dates are school-local; the bounds are returned in UTC
 * for comparing with session start times.
 */
class PeerTerms
{
    /** @return array{label: string, from: string, to: string, starts: CarbonImmutable, ends: CarbonImmutable} */
    public function window(?int $gradeLevelId): array
    {
        $today = app(AcademicAuthority::class)->today();
        $year = SessionYear::query()->findOrFail((int) schoolSetting('session_year'));
        $grade = $gradeLevelId === null ? null : GradeLevel::query()->whereKey($gradeLevelId)->value('sort_order');
        $terms = Term::query()->where('session_year_id', $year->id)->forGrade($grade === null ? null : (int) $grade)
            ->whereDate('start_date', '<=', $today)->orderByDesc('start_date')->orderByDesc('id')->get();
        $yearEnd = substr((string) $year->end_date, 0, 10);
        $current = $terms->first(fn (Term $term) => $term->end_date !== null && $term->end_date->toDateString() >= $today);
        $latest = $current ?? $terms->first();
        [$label, $from, $to] = $latest === null
            ? [__('this school year'), substr((string) $year->start_date, 0, 10), $yearEnd]
            : [$latest->name, $latest->start_date->toDateString(), $current !== null ? $current->end_date->toDateString() : $yearEnd];
        $zone = app(CurrentSchool::class)->school()->timezone;

        return ['label' => (string) $label, 'from' => $from, 'to' => $to,
            'starts' => CarbonImmutable::parse($from, $zone)->startOfDay()->utc(),
            'ends' => CarbonImmutable::parse($to, $zone)->endOfDay()->utc()];
    }
}
