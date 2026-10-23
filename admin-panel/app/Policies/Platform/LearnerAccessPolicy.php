<?php

declare(strict_types=1);

namespace App\Policies\Platform;

use App\Models\Platform\GuardianLink;
use App\Models\Students;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class LearnerAccessPolicy
{
    public function approveAccess(User $user, Students $learner): Response
    {
        if (! $user->hasRole('Parent') || (int) $user->status !== 1 || $user->email_verified_at === null) {
            return Response::deny();
        }

        return GuardianLink::query()->where('learner_id', $learner->id)->where('guardian_user_id', $user->id)
            ->where('status', 'active')->where('can_approve', true)->exists()
            ? Response::allow() : Response::denyAsNotFound();
    }
}
