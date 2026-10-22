<?php

declare(strict_types=1);

namespace App\Services\Platform\Context\Contributors;

use App\Http\Resources\Platform\ContextUserResource;
use App\Services\Platform\Context\ContextContributor;
use App\Services\Platform\Context\ContextScope;

/**
 * user: who is signed in, with their roles at the current school (stream
 * T1). The roles at every school are in schools[].roles.
 */
class UserContributor implements ContextContributor
{
    public function contribute(ContextScope $scope): array
    {
        return [
            'user' => new ContextUserResource($scope->user, $scope->user->getRoleNames()->values()->all()),
        ];
    }
}
