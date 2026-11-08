<?php

declare(strict_types=1);

namespace App\Services\Learning\Tests\Marking;

use InvalidArgumentException;

/**
 * Marks one answer in a practice test against the question's immutable
 * approved definition. This is the seam for richer marking: typed working
 * and photographed answers get their own marker (the subject marking
 * engine) registered in TestMarkers, and nothing else in the test domain
 * changes.
 */
interface TestAnswerMarker
{
    /** Refuse an answer the marker could never read (a kind, plain-language reason). */
    public function check(array $definition, string $answer): void;

    /** @throws InvalidArgumentException when the answer cannot be read */
    public function mark(array $definition, string $answer): TestMark;
}
