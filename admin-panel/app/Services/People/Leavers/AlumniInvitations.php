<?php

declare(strict_types=1);

namespace App\Services\People\Leavers;

use App\Models\Platform\SchoolMembership;
use App\Models\Students;

/**
 * Extension point for the alumni network (Phase 4, FUTURE-IDEAS Idea 10):
 * called once for each learner who enters the leaver state at the year
 * switch, inside the switch's transaction, with the school chosen.
 *
 * Nothing records an alumni consent flag yet, so the bound implementation is
 * NoAlumniInvitations, which does nothing. Phase 4 binds its own
 * implementation in AppServiceProvider to record the leaver's consent (where
 * they gave one) and invite them after the switch commits. It must not
 * contact anyone inside the transaction and must not change the leaver's
 * access. engineering/specs/academics/new-year-structures.md, "Leaver state".
 */
interface AlumniInvitations
{
    public function leaverStarted(Students $learner, SchoolMembership $membership): void;
}
