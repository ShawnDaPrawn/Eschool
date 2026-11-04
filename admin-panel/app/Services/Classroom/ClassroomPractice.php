<?php

declare(strict_types=1);

namespace App\Services\Classroom;

use App\Models\Classroom\ClassroomLease;
use App\Models\Learning\PracticeDailySet;
use App\Models\User;
use App\Services\Academics\AcademicAuthority;
use App\Services\Learning\DailyPractice;
use App\Services\Learning\PracticeAnswers;
use App\Services\Learning\PracticeContract;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** No request-supplied actor, learner, group or Teacher token reaches practice. */
class ClassroomPractice
{
    public function run(string $device, string $credential, string $action, array $input = [], ?int $set = null, ?int $entry = null, int $contract = PracticeContract::ANSWER_FREE): array
    {
        $sessions = app(ClassroomSessions::class);
        $initial = $sessions->authenticate($device, $credential);
        $lease = ClassroomLease::findOrFail($initial->classroom_lease_id);
        try {
            return DB::transaction(function () use ($device, $credential, $action, $input, $set, $entry, $initial, $lease, $sessions, $contract) {
                app(ClassroomLocks::class)->acquire($lease, $initial->card_id);
                $row = $sessions->authenticate($device, $credential);
                $person = User::findOrFail($row->user_id);
                $learner = $sessions->data($row)['learner_id'];
                if ($set !== null) {
                    PracticeDailySet::where('learner_id', $learner)->where('teaching_group_id', $lease->teaching_group_id)->findOrFail($set);
                }
                $practice = app(DailyPractice::class);
                $result = match ($action) {
                    // The shared tablet offers one-answer questions only; other kinds are answered in the learner's own app (3.11).
                    'issue' => $practice->issue($person, $learner, (int) $lease->teaching_group_id,
                        app(ClassroomAuthority::class)->input($input, ['operation_id' => ['required', 'uuid']]) + ['practice_on' => app(AcademicAuthority::class)->today()],
                        array_values(array_diff(PracticeAnswers::KINDS, PracticeAnswers::SINGLE)), $contract),
                    'read' => $practice->read($person, $learner, (int) $set, $contract),
                    'review' => $practice->review($person, $learner, (int) $set, (int) $entry,
                        app(ClassroomAuthority::class)->input($input, ['operation_id' => ['required', 'uuid'], 'answer' => ['required', 'string', 'max:100'], 'rating' => ['required', 'integer', 'between:2,4']]), null, $contract),
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
