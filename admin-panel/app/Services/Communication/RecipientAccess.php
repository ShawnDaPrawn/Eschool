<?php

declare(strict_types=1);

namespace App\Services\Communication;

use App\Models\Communication\MessageRecipient;
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
            foreach (in_array('Student', $roles, true) ? ($reason['learner_ids'] ?? []) : [] as $id) {
                $learner = Students::where('user_id', $fresh->id)->find($id);
                if ($learner !== null && $this->learner($learner)) {
                    return true;
                }
            }
            foreach (in_array('Parent', $roles, true) ? ($reason['guardian_of'] ?? []) : [] as $id) {
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

    /**
     * Authorize encrypted recipient metadata before the inbox loads message
     * bodies or computes pagination. Equal reasons share one decision only
     * within this call; no role/guardian decision survives a request/job.
     *
     * @return list<int>
     */
    public function messageIds(User $actor): array
    {
        $fresh = app(CommunicationRules::class)->actor($actor);
        $decisions = [];
        $ids = [];
        foreach (MessageRecipient::where('user_id', $fresh->id)->select(['message_id', 'access_reason'])->cursor() as $recipient) {
            $reason = $recipient->access_reason;
            if (! is_array($reason)) {
                continue;
            }
            $key = hash('sha256', json_encode($reason, JSON_THROW_ON_ERROR));
            if (! array_key_exists($key, $decisions)) {
                $decisions[$key] = $this->allows($fresh, $reason);
            }
            if ($decisions[$key]) {
                $ids[] = (int) $recipient->message_id;
            }
        }

        return array_values(array_unique($ids));
    }

    private function learner(Students $learner): bool
    {
        return app(LearnerAcademicAccess::class)->allows($learner)
            && (! config('identity.approval_enforced') || app(ApprovalService::class)->approved($learner));
    }
}
