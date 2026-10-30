<?php

declare(strict_types=1);

namespace App\Policies\Platform;

use App\Models\Platform\GuardianLink;
use App\Models\Students;
use App\Models\User;
use App\Services\People\Leavers\LeaverState;
use Illuminate\Auth\Access\Response;

class LearnerAccessPolicy
{
    public function viewEnrolments(User $user, Students $learner): bool
    {
        return $user->hasRole('Super Admin') || (! $user->hasAnyRole(['Student', 'Parent', 'Teacher']) && $user->can('student-list'));
    }

    public function manageEnrolments(User $user, Students $learner): bool
    {
        return $user->hasRole('Super Admin') || (! $user->hasAnyRole(['Student', 'Parent', 'Teacher']) && $user->can('student-edit'));
    }

    /** The school office adds, changes and ends a learner's guardians. */
    public function manageGuardians(User $user, Students $learner): bool
    {
        return $user->hasRole('Super Admin') || (! $user->hasAnyRole(['Student', 'Parent', 'Teacher']) && $user->can('student-edit'));
    }

    public function approveAccess(User $user, Students $learner): Response
    {
        if (! $user->hasRole('Parent') || (int) $user->status !== 1 || $user->email_verified_at === null) {
            return Response::deny();
        }
        // Approving or choosing a handle is a write, not for a leaver (Action Plan 1.21);
        // withdrawing an approval or rejecting a proposal only takes access away and stays open.
        if (LeaverState::writing() && app(LeaverState::class)->isLeaver((int) $learner->id)) {
            return Response::deny(trans('leaver_read_only'));
        }

        return GuardianLink::query()->where('learner_id', $learner->id)->where('guardian_user_id', $user->id)
            ->where('status', 'active')->where('can_approve', true)->exists()
            ? Response::allow() : Response::denyAsNotFound();
    }
}
