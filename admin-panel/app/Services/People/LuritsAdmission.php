<?php

declare(strict_types=1);

namespace App\Services\People;

use App\Models\Students;
use App\Models\User;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Identity\LuritsNumbers;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Validation\ValidationException;

/**
 * Matching on LURITS at admission (Action Plan 1.0c). Before the office adds a
 * learner with a LURITS number, the number is checked against every
 * NavigateEd learner:
 *
 * - nobody holds it: the learner is added and the number recorded;
 * - a learner of this school holds it, now or before: refused, naming that
 *   learner's admission number, so the office opens the existing record;
 * - someone else holds it: refused with "start a transfer request". The school
 *   learns only that a NavigateEd record exists, never where or whose; the
 *   consented transfer flow (school-transfers.md) then moves the records.
 *
 * engineering/specs/platform/lurits.md, "Matching at admission".
 */
class LuritsAdmission
{
    public const PERMISSION = 'student-create';

    public function __construct(private readonly LuritsNumbers $numbers) {}

    /**
     * The normalised number to record once the learner exists, or null when
     * none was given. Throws a validation error on the `lurits` field when the
     * learner must not be added.
     */
    public function admissible(User $actor, mixed $input): ?string
    {
        abort_unless($actor->can(self::PERMISSION), 403);
        if ($input === null || (is_string($input) && trim($input) === '')) {
            return null;
        }
        if (! is_string($input) || strlen($input) > 32) {
            throw ValidationException::withMessages(['lurits' => __('That is not a valid LURITS number.')]);
        }
        $number = LuritsNumbers::normalise($input);
        if (! LuritsNumbers::valid($number)) {
            throw ValidationException::withMessages(['lurits' => __('That is not a valid LURITS number.')]);
        }
        $holder = $this->numbers->holderUserId($number);
        if ($holder === null) {
            return $number;
        }
        // The current school's own learners only (BelongsToSchool), including past ones.
        $here = Students::withTrashed()->where('user_id', $holder)->first(['id', 'admission_no', 'deleted_at']);
        if ($here !== null) {
            Audit::record('identity.lurits_admission_matched', $here, [], ['match' => $here->trashed() ? 'former_learner' : 'learner'], app(CurrentSchool::class)->id());
            throw ValidationException::withMessages(['lurits' => $here->trashed()
                ? __('This LURITS number belongs to a former learner of your school (:number). Restore their record instead of adding them again.', ['number' => $here->admission_no])
                : __('This LURITS number belongs to :number, already a learner at your school. Open their record instead of adding them again.', ['number' => $here->admission_no])]);
        }
        // Hashes and ids only: never the number, the holder or their school.
        Audit::record('identity.lurits_admission_matched', null, [], ['match' => 'elsewhere', 'lurits_hash' => LuritsNumbers::hash($number)], app(CurrentSchool::class)->id());
        throw ValidationException::withMessages(['lurits' => __('This learner already has a NavigateEd record. Start a transfer request to bring their records to your school instead of adding them again.')]);
    }
}
