<?php

declare(strict_types=1);

namespace App\Policies\Academics;

use App\Models\User;
use App\Policies\People\ProfilePolicy;

class TermPolicy
{
    public function authoriseClassSettings(User $actor): void
    {
        $authority = app(ProfilePolicy::class);
        $fresh = $authority->actor($actor);
        foreach (['term-list', 'exam-create', 'assign-elective-subjects'] as $permission) {
            if ($authority->permission($fresh, $permission)) {
                return;
            }
        }
        abort(403);
    }

    public function authorise(User $actor, ?string $operation = null): void
    {
        $authority = app(ProfilePolicy::class);
        $fresh = $authority->actor($actor);
        abort_if($operation !== null && ! $authority->permission($fresh, 'term-'.$operation), 403);
    }
}
