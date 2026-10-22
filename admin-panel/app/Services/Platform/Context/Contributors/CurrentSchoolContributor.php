<?php

declare(strict_types=1);

namespace App\Services\Platform\Context\Contributors;

use App\Http\Resources\Platform\CurrentSchoolResource;
use App\Models\SessionYear;
use App\Services\Platform\Context\ContextContributor;
use App\Services\Platform\Context\ContextScope;

/**
 * current_school: the school the request works in, with its name, crest,
 * colours, timezone and current year with its terms (stream T1; openapi.yaml
 * CurrentSchool). Stream T3 adds the school's branding inside it.
 */
class CurrentSchoolContributor implements ContextContributor
{
    public function contribute(ContextScope $scope): array
    {
        $sessionYear = $scope->profile->sessionYearId === null ? null : SessionYear::query()
            ->with(['semesters' => fn ($query) => $query->orderBy('start_date')->orderBy('id')])
            ->find($scope->profile->sessionYearId);

        return [
            'current_school' => new CurrentSchoolResource($scope->profile, $sessionYear),
        ];
    }
}
