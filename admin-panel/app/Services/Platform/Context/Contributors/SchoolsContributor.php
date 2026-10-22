<?php

declare(strict_types=1);

namespace App\Services\Platform\Context\Contributors;

use App\Http\Resources\Platform\SchoolSummaryResource;
use App\Services\Platform\Context\ContextContributor;
use App\Services\Platform\Context\ContextScope;
use App\Services\Platform\Identity\MembershipService;
use App\Services\Platform\SchoolProfile;

/**
 * schools: every school the user is an active member of, each with the
 * user's roles there (stream T1; openapi.yaml SchoolMembership). The apps
 * offer a school switcher when there is more than one, and send the chosen
 * school's id as X-School-Id.
 */
class SchoolsContributor implements ContextContributor
{
    public function __construct(private readonly MembershipService $memberships) {}

    public function contribute(ContextScope $scope): array
    {
        $schools = [];
        foreach ($this->memberships->schools($scope->user) as ['school' => $school, 'roles' => $roles]) {
            $profile = $school->id === $scope->school->id ? $scope->profile : SchoolProfile::summary($school);
            $schools[] = ['school' => new SchoolSummaryResource($profile), 'roles' => $roles];
        }

        return ['schools' => $schools];
    }
}
