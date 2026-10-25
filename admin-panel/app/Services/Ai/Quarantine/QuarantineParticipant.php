<?php

declare(strict_types=1);

namespace App\Services\Ai\Quarantine;

use App\Models\Ai\AiUsage;

interface QuarantineParticipant
{
    /**
     * Erase only feature-owned derived copies for this exact immutable usage
     * identity, including operation/feature/user/school matches created before
     * usage linkage. Must be idempotent, work after role/module withdrawal and
     * leave approved curriculum originals and the compute ledger intact.
     */
    public function purgeForUsage(AiUsage $usage): void;
}
