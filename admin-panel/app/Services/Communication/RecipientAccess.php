<?php

declare(strict_types=1);

namespace App\Services\Communication;

use App\Models\Students;
use App\Models\User;
use App\Policies\People\ProfilePolicy;
use App\Services\Academics\Transitions\LearnerAcademicAccess;
use App\Services\Platform\Identity\ApprovalService;
use App\Services\Platform\Identity\GuardianService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/** Snapshot identity plus fresh account, guardian and learner access rights. */
class RecipientAccess
{
    public function allows(User $actor, array $reason): bool
    {
        try {
            $fresh = app(CommunicationRules::class)->actor($actor);
            $roles = app(ProfilePolicy::class)->roles($fresh);
            if (($reason['staff'] ?? false) && array_diff($roles, ['Student', 'Parent']) !== []) {
                return true;
            }
            if ($reason['direct'] ?? false) {
                if (array_diff($roles, ['Student']) !== []) {
                    return true;
                }
                $learner = Students::where('user_id', $fresh->id)->first();

                return $learner !== null && $this->learner($learner);
            }
            foreach ($reason['learner_ids'] ?? [] as $id) {
                $learner = Students::where('user_id', $fresh->id)->find($id);
                if ($learner !== null && $this->learner($learner)) {
                    return true;
                }
            }
            foreach ($reason['guardian_of'] ?? [] as $id) {
                $learner = Students::find($id);
                if ($learner !== null && app(GuardianService::class)->guardianUserIds($learner)->contains((int) $fresh->id)) {
                    return true;
                }
            }

            return false;
        } catch (HttpExceptionInterface|ModelNotFoundException $error) {
            if ($error instanceof HttpExceptionInterface && ! in_array($error->getStatusCode(), [403, 404], true)) {
                throw $error;
            }

            return false;
        }
    }

    private function learner(Students $learner): bool
    {
        return app(LearnerAcademicAccess::class)->allows($learner)
            && (! config('identity.approval_enforced') || app(ApprovalService::class)->approved($learner));
    }
}
