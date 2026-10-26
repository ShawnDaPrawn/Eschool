<?php

declare(strict_types=1);

namespace App\Services\Ai\Inference;

use App\Models\User;

/** Optional, server-allowlisted exception for one domain's exact staff scope. */
interface StaffInferenceActorPolicy
{
    public function authorizeStaffActor(User $actor, AuthorizedInferenceScope $scope): void;
}
