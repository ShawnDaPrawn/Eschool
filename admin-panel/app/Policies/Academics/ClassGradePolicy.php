<?php

declare(strict_types=1);

namespace App\Policies\Academics;

use App\Models\User;
use App\Policies\People\ProfilePolicy;

class ClassGradePolicy
{
    public function authorise(User $actor, bool $edit = false): void
    {
        // Reuse fresh, active membership + school-scoped permission resolution.
        $authority = app(ProfilePolicy::class);
        $fresh = $authority->actor($actor);
        abort_unless($authority->permission($fresh, 'class-edit')
            || (! $edit && $authority->permission($fresh, 'class-list')), 403);
    }
}
