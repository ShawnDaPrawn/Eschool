<?php

declare(strict_types=1);

namespace App\Services\Growth;

use App\Models\User;
use App\Models\Wellbeing\CareCase;
use App\Policies\People\ProfilePolicy;
use App\Services\Platform\Tenancy\CurrentSchool;
use App\Services\Wellbeing\WellbeingAccess;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/** Ephemeral clinical context, never a shared risk observation or diagnosis. */
class CounsellorCaseContext
{
    private function authorize(User $actor, int $learner, int $year, int $grade): User
    {
        $fresh = app(RiskAuthority::class)->caseActor($actor, $learner, $year, $grade, false, 'professional');
        abort_unless(in_array('Counsellor', app(ProfilePolicy::class)->roles($fresh), true)
            && $year === (int) schoolSetting('session_year'), 404);

        return app(WellbeingAccess::class)->professional($fresh);
    }

    public function count(User $actor, int $learner, int $year, int $grade): ?int
    {
        try {
            $fresh = $this->authorize($actor, $learner, $year, $grade);
            $school = app(CurrentSchool::class)->id();
            $access = app(WellbeingAccess::class);
            // These are explicit current case assignments. Do not join reports,
            // category teams, anonymous reporters or case-entry bodies.
            $rows = CareCase::where('learner_id', $learner)->whereIn('id', $access->caseIds($fresh))
                ->orderBy('id')->limit(101)->get(['id', 'status']);
            if ($rows->isEmpty() || $rows->count() > 100) {
                return null;
            }
            $count = 0;
            foreach ($rows as $row) {
                $case = $access->case($fresh, (int) $row->id);
                abort_unless((int) $case->learner_id === $learner, 404);
                if ($case->status === 'open') {
                    $count++;
                }
            }
            // Recheck each exact grant and both scope systems after reading.
            foreach ($rows as $row) {
                $case = $access->case($fresh, (int) $row->id);
                abort_unless((int) $case->learner_id === $learner && $case->status === $row->status, 404);
            }
            $this->authorize($fresh, $learner, $year, $grade);
            abort_unless(app(CurrentSchool::class)->id() === $school, 403);

            return $count;
        } catch (ModelNotFoundException) {
            return null;
        } catch (HttpExceptionInterface $error) {
            if (! in_array($error->getStatusCode(), [403, 404], true)) {
                throw $error;
            }

            return null;
        }
    }
}
