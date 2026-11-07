<?php

declare(strict_types=1);

namespace App\Services\Passport;

use App\Models\Students;

/** Until the reading library arrives, there is nothing to show. */
class NoReadingRecords implements ReadingRecords
{
    public function forLearner(Students $learner): array
    {
        return [];
    }
}
