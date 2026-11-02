<?php

declare(strict_types=1);

namespace App\Services\People\Leavers;

use App\Models\Platform\SchoolMembership;
use App\Models\Students;

/**
 * The alumni invitation hook until Phase 4 builds the alumni network: there
 * is no alumni consent flag to record, so it records and sends nothing.
 */
final class NoAlumniInvitations implements AlumniInvitations
{
    public function leaverStarted(Students $learner, SchoolMembership $membership): void
    {
        // Intentionally empty: the Phase 4 extension point (see AlumniInvitations).
    }
}
