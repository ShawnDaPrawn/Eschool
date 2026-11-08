<?php

declare(strict_types=1);

namespace App\Services\Learning\Motivation;

use App\Models\Students;
use App\Models\User;
use App\Policies\People\ProfilePolicy;
use App\Services\Academics\AcademicAuthority;
use App\Services\Learning\Digests\DigestAuthority;
use App\Services\Learning\PracticeAccess;
use App\Services\Platform\Identity\GuardianService;
use App\Services\Platform\Stages\SchoolStages;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * Who may read and change a learner's streak, goal and celebrations (Action
 * Plan 3.13). Only the learner themself, or a current guardian of a learner
 * who is current, guardian-approved and in a stage that practises. Staff and
 * other families are refused: there is no list of learners here, so nothing
 * can be ranked or compared.
 */
class MotivationAuthority
{
    /** The signed-in learner's own record. Writes need the module to be writable. */
    public function self(User $actor, int $learnerId, bool $write = false): Students
    {
        $fresh = app(PracticeAccess::class)->actor($actor, $write);
        $learner = Students::findOrFail($learnerId);
        abort_unless((int) $learner->user_id === (int) $fresh->id && in_array('Student', app(ProfilePolicy::class)->roles($fresh), true), 404);

        return $this->practising($learner);
    }

    /** The learner themself, or one of their current guardians. */
    public function reader(User $actor, int $learnerId): Students
    {
        $fresh = app(PracticeAccess::class)->actor($actor);
        $learner = Students::findOrFail($learnerId);
        $roles = app(ProfilePolicy::class)->roles($fresh);
        if ((int) $learner->user_id === (int) $fresh->id && in_array('Student', $roles, true)) {
            return $this->practising($learner);
        }
        abort_unless(in_array('Parent', $roles, true) && app(GuardianService::class)->guardianUserIds($learner)->contains((int) $fresh->id), 404);

        return $this->practising($learner);
    }

    /** Current, approved and in a stage with daily practice (early years learners do not practise on a device). */
    public function practising(Students $learner): Students
    {
        abort_unless(app(DigestAuthority::class)->currentLearner($learner) && $this->stageAllows($learner), 404);

        return $learner;
    }

    public function stageAllows(Students $learner): bool
    {
        return SchoolStages::moduleAllows('daily_practice', app(SchoolStages::class)->forLearner($learner));
    }

    /** School managers of practice nudges: the same people who manage the weekly digest. */
    public function manager(User $actor, bool $write = false): User
    {
        $fresh = app(PracticeAccess::class)->actor($actor, $write);
        $roles = app(ProfilePolicy::class)->roles($fresh);
        $academic = app(AcademicAuthority::class);
        abort_unless((array_intersect($roles, ['Admin', 'Super Admin']) !== [] && $academic->manages($fresh)) || $academic->currentLeadership($fresh, 'principal', 0), 403);

        return $fresh;
    }

    public function mayManage(?User $actor): bool
    {
        if ($actor === null) {
            return false;
        }
        try {
            $this->manager($actor);

            return true;
        } catch (HttpExceptionInterface|ModelNotFoundException) {
            return false;
        }
    }

    /**
     * The people told about a learner's own celebration: the learner and their
     * current guardians, only while the learner is current and approved.
     *
     * @return list<int>
     */
    public function family(Students $learner): array
    {
        if (! app(DigestAuthority::class)->currentLearner($learner)) {
            return [];
        }

        return array_values(array_unique([(int) $learner->user_id, ...app(GuardianService::class)->guardianUserIds($learner)->all()]));
    }
}
