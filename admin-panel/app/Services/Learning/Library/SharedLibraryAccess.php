<?php

declare(strict_types=1);

namespace App\Services\Learning\Library;

use App\Models\User;
use App\Policies\Academics\ContentPolicy;
use App\Policies\People\ProfilePolicy;
use App\Services\Learning\PracticeAccess;
use App\Services\Platform\Stages\SchoolStages;

/**
 * Who reaches the shared library at a school (Action Plan 5.4), checked by
 * every service method after the route and the FormRequest. Always inside
 * the person's own school, never while impersonating or reading across
 * schools, never for platform staff, and only with daily practice on
 * (PracticeAccess::actor).
 *
 * - A teacher (the Teacher role at this school) browses, rates and copies
 *   shared items, and shares and withdraws their own questions.
 * - A school leader (a Super Admin or office staff who manage the school's
 *   settings) sets the school's sharing switch and whether a leader approves
 *   each contribution.
 * - A leader, or a content lead of the question's grade (academic managers,
 *   the grade head and the principal: ContentPolicy::leads), approves,
 *   declines or withdraws a contribution.
 *
 * Learners, parents and other staff are refused (403).
 */
class SharedLibraryAccess
{
    public function teacher(User $actor, bool $write = false): User
    {
        $fresh = app(PracticeAccess::class)->actor($actor, $write);
        abort_unless($this->isTeacher($fresh), 403, __('Only teachers use the shared library.'));

        return $fresh;
    }

    /** Any school staff member (not a learner or parent): the contribution decisions check the grade they lead. */
    public function staff(User $actor, bool $write = false): User
    {
        $fresh = app(PracticeAccess::class)->actor($actor, $write);
        abort_if(array_diff(app(ProfilePolicy::class)->roles($fresh), ['Parent', 'Student']) === [], 403);

        return $fresh;
    }

    /** A teacher or a leader: who may open the shared library pages at all. */
    public function member(User $actor): User
    {
        $fresh = app(PracticeAccess::class)->actor($actor);
        abort_unless($this->isTeacher($fresh) || $this->isLeader($fresh), 403);

        return $fresh;
    }

    public function leader(User $actor, bool $write = false): User
    {
        $fresh = app(PracticeAccess::class)->actor($actor, $write);
        abort_unless($this->isLeader($fresh), 403, __('Only a school leader changes how the school shares.'));

        return $fresh;
    }

    public function isTeacher(User $fresh): bool
    {
        return in_array('Teacher', app(ProfilePolicy::class)->roles($fresh), true);
    }

    public function isLeader(User $fresh): bool
    {
        return SchoolStages::mayManage($fresh);
    }

    /** Whether this person may approve, decline or withdraw a contribution of a question of this grade. */
    public function leadsGrade(User $fresh, int $gradeId): bool
    {
        if ($this->isLeader($fresh)) {
            return true;
        }
        $roles = app(ProfilePolicy::class)->roles($fresh);

        return array_diff($roles, ['Parent', 'Student']) !== [] && app(ContentPolicy::class)->leads($fresh, $gradeId);
    }
}
