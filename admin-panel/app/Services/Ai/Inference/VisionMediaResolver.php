<?php

declare(strict_types=1);

namespace App\Services\Ai\Inference;

use App\Models\User;

interface VisionMediaResolver
{
    /** Fresh owner/school/approval/operation checks BEFORE private bytes are read. */
    public function resolve(User $actor, AuthorizedInferenceScope $scope): ResolvedVisionInput;
}
