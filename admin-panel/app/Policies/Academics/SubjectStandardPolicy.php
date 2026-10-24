<?php

declare(strict_types=1);

namespace App\Policies\Academics;

use App\Models\User;
use App\Policies\People\ProfilePolicy;

class SubjectStandardPolicy
{
    public function authorise(User $actor, ?string $operation = null): void
    {
        $authority = app(ProfilePolicy::class);
        $fresh = $authority->actor($actor);
        if ($operation !== null) {
            abort_unless($authority->permission($fresh, 'subject-'.$operation), 403);
        }
    }
}
