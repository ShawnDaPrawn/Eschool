<?php

declare(strict_types=1);

namespace App\Services\People;

use App\Models\People\Enrolment;
use App\Models\Platform\SchoolMembership;
use App\Models\Students;
use App\Models\User;
use App\Services\Platform\Identity\AppTokens;
use App\Services\Platform\Identity\MembershipService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** The legacy cleanup action cannot erase a global identity or dated history. */
class RemoveLearner
{
    public function permanently(Students $learner): void
    {
        DB::transaction(function () use ($learner) {
            // Match identity allocation's user lock, including other schools.
            $user = User::acrossSchools()->withTrashed()->lockForUpdate()->findOrFail($learner->user_id);
            $learner = Students::query()->lockForUpdate()->findOrFail($learner->id);
            if (Enrolment::query()->where('learner_id', $learner->id)->exists()) {
                throw ValidationException::withMessages(['learner' => 'This learner has enrolment history. Archive the learner instead; retained records require the retention process.']);
            }
            $learner->forceDelete();
            $user->removeRole('Student');
            app(MembershipService::class)->end($user, (int) $learner->school_id, 'Student');

            // Other school accounts and other roles keep their global user.
            // Keep the identifier even when the final account is archived:
            // its number must never be allocated to a different human.
            if (! Students::acrossSchools()->withTrashed()->where('user_id', $user->id)->exists()
                && ! SchoolMembership::query()->where('user_id', $user->id)->where('status', '!=', SchoolMembership::ENDED)->exists()) {
                AppTokens::revokeAll($user);
                DB::table('sessions')->where('user_id', $user->id)->delete();
                $user->delete();
            }
        });
    }
}
