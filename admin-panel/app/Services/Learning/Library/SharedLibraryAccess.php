<?php

declare(strict_types=1);

namespace App\Services\Learning\Library;

use App\Models\Academics\GradeLevel;
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
 *   the grade head and the principal: ContentPolicy::leads), sees exactly
 *   what a contribution would share, approves, declines or withdraws it. A
 *   content lead opens the page even without the Teacher role or the
 *   settings permission, and sees only their grades' contributions.
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

    /**
     * Who may open the shared library page at all: a teacher, a leader, or a
     * content lead of any grade (who then sees only their grades' contributions).
     */
    public function member(User $actor): User
    {
        $fresh = app(PracticeAccess::class)->actor($actor);
        abort_unless($this->isTeacher($fresh) || $this->isLeader($fresh) || $this->leadsAnyGrade($fresh), 403);

        return $fresh;
    }

    /** Whether this person leads the learning content of at least one grade (ContentPolicy::leads). */
    public function leadsAnyGrade(User $fresh): bool
    {
        if (array_diff(app(ProfilePolicy::class)->roles($fresh), ['Parent', 'Student']) === []) {
            return false;
        }
        foreach (GradeLevel::query()->orderBy('sort_order')->pluck('id') as $grade) {
            if (app(ContentPolicy::class)->leads($fresh, (int) $grade)) {
                return true;
            }
        }

        return false;
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
