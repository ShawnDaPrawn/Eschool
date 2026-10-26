<?php

declare(strict_types=1);

namespace App\Services\Classroom;

use App\Models\Classroom\ClassroomLease;
use App\Models\Learning\PracticeDailySet;
use App\Models\User;
use App\Services\Academics\AcademicAuthority;
use App\Services\Learning\DailyPractice;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** No request-supplied actor, learner, group or Teacher token reaches practice. */
class ClassroomPractice
{
    public function run(string $device, string $credential, string $action, array $input = [], ?int $set = null, ?int $entry = null): array
    {
        $sessions = app(ClassroomSessions::class);
        $initial = $sessions->authenticate($device, $credential);
        $lease = ClassroomLease::findOrFail($initial->classroom_lease_id);
        try {
            return DB::transaction(function () use ($device, $credential, $action, $input, $set, $entry, $initial, $lease, $sessions) {
                app(ClassroomLocks::class)->acquire($lease, $initial->card_id);
                $row = $sessions->authenticate($device, $credential);
                $person = User::findOrFail($row->user_id);
                $learner = $sessions->data($row)['learner_id'];
                if ($set !== null) {
                    PracticeDailySet::where('learner_id', $learner)->where('teaching_group_id', $lease->teaching_group_id)->findOrFail($set);
                }
                $practice = app(DailyPractice::class);
                $result = match ($action) {
                    'issue' => $practice->issue($person, $learner, (int) $lease->teaching_group_id,
                        app(ClassroomAuthority::class)->input($input, ['operation_id' => ['required', 'uuid']]) + ['practice_on' => app(AcademicAuthority::class)->today()]),
                    'read' => $practice->read($person, $learner, (int) $set),
                    'review' => $practice->review($person, $learner, (int) $set, (int) $entry,
                        app(ClassroomAuthority::class)->input($input, ['operation_id' => ['required', 'uuid'], 'answer' => ['required', 'string', 'max:100'], 'rating' => ['required', 'integer', 'between:2,4']])),
                    default => abort(404),
                };
                $sessions->authenticate($device, $credential);

                return $result;
            }, 3);
        } catch (\InvalidArgumentException $error) {
            throw ValidationException::withMessages(['practice' => $error->getMessage()]);
        }
    }
}
