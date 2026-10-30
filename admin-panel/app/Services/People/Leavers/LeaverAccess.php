<?php

declare(strict_types=1);

namespace App\Services\People\Leavers;

use App\Models\Platform\SchoolMembership;
use App\Models\Students;
use App\Models\User;
use App\Services\Platform\Identity\MembershipService;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Read-only for leavers (Action Plan 1.21), checked by the school.context
 * middleware once it has chosen the school, on every signed-in panel, v1 and
 * v2 request. It applies to the role being used, not to the whole person:
 *
 * - a person whose every membership at the school is a leaver one (a leaver,
 *   or a guardian whose children there all left) may read and may not write;
 * - on a family route (the parent and learner APIs, v1 and v2) a write is
 *   refused when the person's membership for that route's role is a leaver
 *   one, even if they are also staff (a teacher whose only child left);
 * - on a family route, or for someone who holds only family roles, a write
 *   that names a leaver (route learner or person, learner_id, child_id or
 *   student_id) is refused.
 *
 * This is the outer check. The helpers that authorise a learner or guardian
 * for one learner refuse family writes for a leaver themselves, however the
 * request names the learner (LeaverState::guardFamilyWrite). Reads go on
 * through the usual checks until leaver_until.
 */
class LeaverAccess
{
    /** Route parameters and request fields that name a learner (students.id). */
    private const LEARNER_FIELDS = ['learner', 'learner_id', 'child_id', 'student_id'];

    /** Family routes and the role each one acts with. */
    private const FAMILY_ROUTES = [
        'Parent' => ['api/parent/*', 'api/v2/parent/*', 'api/v2/guardian/*'],
        'Student' => ['api/student/*', 'api/v2/student/*'],
    ];

    public function __construct(private readonly MembershipService $memberships) {}

    public function assertWritable(Request $request, User $user, int $schoolId): void
    {
        // These exact operations only retreat from a privacy grant. Their
        // domain services still recheck the live family relationship and
        // operation/revision; no general leaver write permission is granted.
        $privacyRetreat = $request->isMethod('POST') && (
            $request->is('api/v2/gallery/tags/*/remove')
            || ($request->is('api/v2/family-forms/consents/*/learners/*/response') && $request->input('decision') === 'no')
        );
        if (! LeaverState::writing() || $privacyRetreat) {
            return;
        }
        if ($this->memberships->readOnlyAt($user, $schoolId)) {
            throw new AccessDeniedHttpException(trans('leaver_read_only'));
        }
        $role = null;
        foreach (self::FAMILY_ROUTES as $name => $patterns) {
            if ($request->is(...$patterns)) {
                $role = $name;
            }
        }
        $here = $this->memberships->active($user)->where('school_id', $schoolId);
        if ($role !== null && $here->where('role', $role)->isNotEmpty() && $here->where('role', $role)->every(fn ($m) => $m->status === SchoolMembership::LEAVER)) {
            throw new AccessDeniedHttpException(trans('leaver_read_only'));
        }
        if ($role === null && ! $this->memberships->familyOnlyAt($user, $schoolId)) {
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
