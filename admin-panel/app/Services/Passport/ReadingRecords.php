<?php

declare(strict_types=1);

namespace App\Services\Passport;

use App\Models\Students;

/**
 * The passport's reading section (Action Plan 4.10). The digital library
 * (Action Plan 4.4) binds its own implementation; until then the passport
 * shows an empty reading section (NoReadingRecords).
 *
 * Called inside the learner's current school, for that school's learner row.
 * An implementation returns only books the learner finished, never reading
 * time, comprehension answers or anything a learner wrote.
 */
interface ReadingRecords
{
    /** @return list<array{title: string, author: ?string, finished_on: ?string}> */
    public function forLearner(Students $learner): array;
}
