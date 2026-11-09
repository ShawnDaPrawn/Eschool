<?php

declare(strict_types=1);

namespace App\Services\Learning\Tests\Marking;

/**
 * Which marker marks which question kind. Only the kinds main supports today
 * are marked automatically and assembled into tests. When richer kinds and
 * the subject marking engine arrive, register their marker here and add the
 * kind to ASSEMBLED; every other kind waits for a marker. A marker that uses
 * the subject marking engine must mark only while the subject's accuracy
 * gate is open (SubjectProfile::learnerMarkingEnabled(), Action Plan 3.18)
 * and leave the answer waiting otherwise; MarkingAccuracyGateTest lists every
 * use of the engine.
 */
class TestMarkers
{
    /** Question kinds a test may contain today. */
    public const ASSEMBLED = ['choice', 'number'];

    public function for(string $kind): TestAnswerMarker
    {
        return match ($kind) {
            'choice', 'number' => new ChoiceAndNumberMarker,
            default => new AwaitingMarker,
        };
    }

    public function assembles(?string $kind): bool
    {
        return in_array($kind, self::ASSEMBLED, true);
    }
}
