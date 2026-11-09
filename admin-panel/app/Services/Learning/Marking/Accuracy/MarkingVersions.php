<?php

declare(strict_types=1);

namespace App\Services\Learning\Marking\Accuracy;

use App\Services\Learning\Marking\MarkingEngine;
use App\Services\Learning\Marking\SubjectProfiles;

/**
 * The engine and profile versions in this build (Action Plan 3.18). A run is
 * tied to the versions it measured; the gate opens only while they are still
 * the current ones. MarkingVersionsTest pins each version to its code.
 */
class MarkingVersions
{
    public function engine(): string
    {
        return MarkingEngine::VERSION;
    }

    public function profile(string $subject): string
    {
        return SubjectProfiles::key($subject)->version();
    }
}
