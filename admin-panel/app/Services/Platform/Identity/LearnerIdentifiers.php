<?php

declare(strict_types=1);

namespace App\Services\Platform\Identity;

use App\Models\Platform\GuardianLink;
use App\Models\Students;
use App\Models\User;
use App\Policies\People\ProfilePolicy;
use App\Services\Platform\Tenancy\CurrentSchool;

/**
 * Who may see a learner's headline number (LURITS, or the temporary NavigateEd
 * number). Specification: engineering/specs/platform/lurits.md, "Headline
 * number"; ADR 0007.
 *
 * The number is shown to the learner, to guardians who may approve for the
 * learner (an active guardian link with can_approve), and to staff with
 * profile permission (profile-person-view or profile-person-edit) at the
 * learner's school. Everyone else, including a guardian without approval
 * rights, a teacher without profile permission and Navigate Ed staff during
 * impersonation, gets null.
 */
class LearnerIdentifiers
{
    public function __construct(private readonly LuritsNumbers $numbers) {}

    public function mayView(User $viewer, Students $learner): bool
    {
        $schools = app(CurrentSchool::class);
        if ($schools->isImpersonating() || (int) $learner->school_id !== $schools->id()) {
            return false;
        }
        $fresh = User::query()->where('status', 1)->find($viewer->id);
        if ($fresh === null) {
            return false;
        }
        if ((int) $fresh->id === (int) $learner->user_id) {
            return true;
        }

        return $this->staff($fresh)
            || GuardianLink::query()->where('learner_id', $learner->id)->where('guardian_user_id', $fresh->id)
                ->where('status', 'active')->where('can_approve', true)->exists();
    }

    /**
     * The learner's headline identifier for this viewer, or null.
     *
     * @return array{type: 'lurits'|'navigateed', value: string}|null
     */
    public function forLearner(User $viewer, ?Students $learner): ?array
    {
        if ($learner === null || ! $this->mayView($viewer, $learner)) {
            return null;
        }

        return $this->numbers->primary($learner);
    }

    /**
     * For a profile screen: only a learner profile has a headline number.
     *
     * @return array{type: 'lurits'|'navigateed', value: string}|null
     */
    public function forProfile(User $viewer, User $person, string $profile): ?array
    {
        if ($profile !== 'learner') {
            return null;
        }

        return $this->forLearner($viewer, Students::query()->where('user_id', $person->id)->first());
    }

    /**
     * For a staff list of this school's learners: every value, or every null
     * when the viewer has no profile permission.
     *
     * @param  iterable<Students>  $learners
     * @return array<int, array{type: 'lurits'|'navigateed', value: string}|null> by learner id
     */
    public function forStaffList(User $viewer, iterable $learners): array
    {
        $rows = [];
        foreach ($learners as $learner) {
            $rows[(int) $learner->id] = $learner;
        }
        $schools = app(CurrentSchool::class);
        $fresh = User::query()->where('status', 1)->find($viewer->id);
        $allowed = ! $schools->isImpersonating() && $fresh !== null && $this->staff($fresh);
        $mine = array_filter($rows, fn (Students $learner) => (int) $learner->school_id === $schools->id());
        $numbers = $allowed ? $this->numbers->primaryForUsers(array_map(fn (Students $learner) => (int) $learner->user_id, $mine)) : [];

        return array_map(fn (Students $learner) => isset($mine[(int) $learner->id]) ? ($numbers[(int) $learner->user_id] ?? null) : null, $rows);
    }

    private function staff(User $fresh): bool
    {
        $policy = app(ProfilePolicy::class);

        return $policy->permission($fresh, 'profile-person-view') || $policy->permission($fresh, 'profile-person-edit');
    }
}
