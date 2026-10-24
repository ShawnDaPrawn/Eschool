<?php

declare(strict_types=1);

namespace App\Services\Academics;

use App\Models\Term;

/** A school-wide break exists only when no grade has an active term. */
class TermBreaks
{
    public function forYear(int $yearId): array
    {
        $terms = Term::query()->where('session_year_id', $yearId)->orderBy('start_date')->orderBy('end_date')->get();
        $end = null;
        $breaks = [];
        foreach ($terms as $term) {
            if (! $term->start_date || ! $term->end_date || $term->start_date->gt($term->end_date)) {
                continue;
            }
            if ($end !== null && $term->start_date->gt($end->addDay())) {
                $breaks[] = ['start' => $end->addDay()->toDateString(), 'end' => $term->start_date->subDay()->toDateString()];
            }
            if ($end === null || $term->end_date->gt($end)) {
                $end = $term->end_date->toImmutable();
            }
        }

        return $breaks;
    }
}
