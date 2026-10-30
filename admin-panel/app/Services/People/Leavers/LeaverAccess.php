<?php

declare(strict_types=1);

namespace App\Services\People\Leavers;

use App\Models\Students;
use App\Models\User;
use App\Services\Platform\Identity\MembershipService;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Read-only for leavers (Action Plan 1.21), checked by the school.context
 * middleware once it has chosen the school, on every signed-in panel, v1 and
 * v2 request:
 *
 * - a person whose every membership at the school is a leaver one (a leaver,
 *   or a guardian whose children there all left) may read and may not write;
 * - a learner or guardian with a current membership (a guardian with another
 *   child still at the school) may not write for a leaver: a write that names
 *   one (the learner, person, child_id, learner_id or student_id of the
 *   route or request) is refused. Staff act as staff and are not affected.
 *
 * Reads go on through the usual checks, which give a leaver's records to the
 * leaver and their guardians until leaver_until (see LeaverState).
 */
class LeaverAccess
{
    /** Route parameters and request fields that name a learner (students.id). */
    private const LEARNER_FIELDS = ['learner', 'learner_id', 'child_id', 'student_id'];

    public function __construct(private readonly MembershipService $memberships) {}

    public function assertWritable(Request $request, User $user, int $schoolId): void
    {
        if ($request->isMethodSafe() || $request->is(...(array) config('leavers.read_only.allow', []))) {
            return;
        }
        if ($this->memberships->readOnlyAt($user, $schoolId)) {
            throw new AccessDeniedHttpException(trans('leaver_read_only'));
        }
        if (! $this->memberships->familyOnlyAt($user, $schoolId)) {
            return;
        }
        $state = app(LeaverState::class);
        foreach ($this->namedLearners($request) as $learnerId) {
            if ($state->isLeaver($learnerId)) {
                throw new AccessDeniedHttpException(trans('leaver_read_only'));
            }
        }
    }

    /** @return list<int> */
    private function namedLearners(Request $request): array
    {
        $ids = [];
        foreach (self::LEARNER_FIELDS as $field) {
            $value = $request->route($field) ?? $request->input($field);
            // Read ids the way the controllers downstream do (FILTER_VALIDATE_INT, as in CheckChild),
            // so "+123", " 123" or a list of ids cannot slip a leaver past this check.
            foreach (is_array($value) ? $value : [$value] as $one) {
                $id = self::id($one);
                if ($id !== null) {
                    $ids[] = $id;
                }
            }
        }
        $person = self::id($request->route('person'));
        if ($person !== null) {
            $learner = Students::query()->where('user_id', $person)->value('id');
            if ($learner !== null) {
                $ids[] = (int) $learner;
            }
        }

        return array_values(array_unique($ids));
    }

    private static function id(mixed $value): ?int
    {
        if (is_int($value)) {
            return $value > 0 ? $value : null;
        }
        if (! is_string($value) && ! is_float($value)) {
            return null;
        }
        $id = filter_var(is_string($value) ? trim($value) : $value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        return $id === false ? null : $id;
    }
}
