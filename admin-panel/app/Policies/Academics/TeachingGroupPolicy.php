<?php

declare(strict_types=1);

namespace App\Policies\Academics;

use App\Models\Academics\TeachingGroup;
use App\Models\User;
use App\Services\Academics\AcademicAuthority;

class TeachingGroupPolicy
{
    public function authorise(User $actor, string $operation, ?int $group = null): void
    {
        $authority = app(AcademicAuthority::class);
        if ($operation !== 'read') {
            $authority->manage($actor);
            if ($group !== null) {
                TeachingGroup::query()->findOrFail($group);
            }
        } elseif ($group !== null) {
            $authority->group($actor, $group);
        } else {
            $authority->staff($actor);
        }
    }
}
