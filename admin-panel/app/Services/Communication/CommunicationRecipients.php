<?php

declare(strict_types=1);

namespace App\Services\Communication;

use App\Models\Academics\HomeroomAllocation;
use App\Models\Academics\TeachingAllocation;
use App\Models\Academics\TeachingGroup;
use App\Models\Academics\TeachingGroupMembership;
use App\Models\Activities\ActivityGroup;
use App\Models\Activities\ActivityMembership;
use App\Models\Parents;
use App\Models\People\Enrolment;
use App\Models\Platform\GuardianLink;
use App\Models\Platform\SchoolMembership;
use App\Models\SessionYear;
use App\Models\Students;
use App\Models\User;
use App\Services\Academics\AcademicAuthority;
use App\Services\Activities\CommunityAuthority;
use App\Services\Platform\Identity\CurrentLearnerMembership;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/** Resolves people and access reasons without exposing contact addresses. */
class CommunicationRecipients
{
    public function members(): Builder
    {
        return User::where('status', 1)->whereIn('id', SchoolMembership::active()->where('school_id', app(CurrentSchool::class)->id())->select('user_id'));
    }

    private function enrolments(int $year, string $date): Builder
    {
        SessionYear::findOrFail($year);

        return Enrolment::where('session_year_id', $year)->onDate($date)
            ->whereHas('learner', fn ($learner) => $learner->whereIn('user_id', app(CurrentLearnerMembership::class)->users()->select('id')));
    }

    public function learners(array $definition, string $date): Collection
    {
        $query = $this->enrolments((int) $definition['session_year_id'], $date);
        $kind = $definition['kind'];
        $target = $definition['target_id'] ?? null;
        if ($kind === 'activity_group') {
            $group = ActivityGroup::where('active', true)->where('session_year_id', $definition['session_year_id'])->findOrFail($target);
            $ids = app(CommunityAuthority::class)->descendants($group);

            return $this->activityLearners($ids, (int) $definition['session_year_id'], $date);
        }
        if ($kind === 'grade') {
            $query->whereHas('homeroom.class', fn ($q) => $q->where('grade_level_id', $target));
        } elseif ($kind === 'homeroom') {
            $query->where('class_section_id', $target);
        } elseif ($kind === 'group') {
            $group = TeachingGroup::where('active', true)->where('session_year_id', $definition['session_year_id'])->findOrFail($target);
            $ids = TeachingGroupMembership::where('teaching_group_id', $group->id)->whereDate('starts_on', '<=', $date)->whereDate('ends_on', '>=', $date)->select('learner_id');
            $query->whereIn('learner_id', $ids)->whereHas('homeroom.class', fn ($q) => $q->where('grade_level_id', $group->grade_level_id));
            if ($group->class_section_id !== null) {
                $query->where('class_section_id', $group->class_section_id);
            }
        }

        return Students::whereIn('id', $query->select('learner_id'))->get(['id', 'user_id', 'father_id', 'mother_id', 'guardian_id']);
    }

    public function activityLearners(array $groups, int $year, string $date): Collection
    {
        $active = ActivityGroup::whereIn('id', $groups)->where('active', true)->where('session_year_id', $year)
            ->whereDate('starts_on', '<=', $date)->whereDate('ends_on', '>=', $date)->select('id');
        $ids = ActivityMembership::where('active', true)->whereIn('activity_group_id', $active)->whereDate('starts_on', '<=', $date)->whereDate('ends_on', '>=', $date)->select('learner_id');

        return Students::whereIn('id', $this->enrolments($year, $date)->select('learner_id'))->whereIn('id', $ids)->get(['id', 'user_id', 'father_id', 'mother_id', 'guardian_id']);
    }

    private function merge(array &$result, int $user, string $kind, ?int $learner = null): void
    {
        $result[$user] ??= ['direct' => false, 'staff' => false, 'learner_ids' => [], 'guardian_of' => []];
        if ($learner === null) {
            $result[$user][$kind] = true;
        } else {
            $result[$user][$kind][] = $learner;
        }
    }

    public function fromLearners(Collection $learners, bool $parents): array
    {
        $result = [];
        if (! $parents) {
            foreach ($learners as $learner) {
                $this->merge($result, (int) $learner->user_id, 'learner_ids', (int) $learner->id);
            }
        } else {
            $links = GuardianLink::whereIn('learner_id', $learners->pluck('id'))->get()->groupBy('learner_id');
            $legacyIds = $learners->flatMap(fn ($row) => [$row->father_id, $row->mother_id, $row->guardian_id])->filter()->unique();
            $legacy = Parents::whereIn('id', $legacyIds)->get(['id', 'user_id'])->keyBy('id');
            foreach ($learners as $learner) {
                $explicit = ($links->get($learner->id) ?? collect())->keyBy('guardian_user_id');
                foreach ($explicit as $link) {
                    if ($link->status === 'active') {
                        $this->merge($result, (int) $link->guardian_user_id, 'guardian_of', (int) $learner->id);
                    }
                }
                foreach ([$learner->father_id, $learner->mother_id, $learner->guardian_id] as $parent) {
                    $user = $legacy->get($parent)?->user_id;
                    if ($user !== null && ! $explicit->has($user)) {
                        $this->merge($result, (int) $user, 'guardian_of', (int) $learner->id);
                    }
                }
            }
        }
        $active = $this->members()->whereIn('id', array_keys($result))->pluck('id')->map(fn ($id) => (int) $id)->all();

        return array_intersect_key($result, array_flip($active));
    }

    public function standard(array $definition, string $date): array
    {
        $result = $this->fromLearners($this->learners($definition, $date), (bool) $definition['parents']);
        if ($definition['kind'] === 'school' && ! $definition['parents']) {
            $staff = SchoolMembership::active()->where('school_id', app(CurrentSchool::class)->id())->whereNotIn('role', ['Student', 'Parent'])->select('user_id');
            foreach ($this->members()->whereIn('id', $staff)->pluck('id') as $user) {
                $this->merge($result, (int) $user, 'staff');
            }
        }
        foreach ($result as &$reason) {
            $reason['learner_ids'] = array_values(array_unique($reason['learner_ids']));
            $reason['guardian_of'] = array_values(array_unique($reason['guardian_of']));
        }
        unset($reason);
        ksort($result);

        return $result;
    }

    /** Current teacher/homeroom/grade-head contacts for a restricted author. */
    public function allowed(User $actor, int $year, string $date, bool $review = false): array
    {
        $rules = app(CommunicationRules::class);
        $effective = $rules->effective($actor);
        $restricted = app(CommunityAuthority::class)->facilitatorOnly($actor);
        if (! $restricted && ((! $review && $effective['allow_school']) || $rules->manages($actor) || $rules->principal($actor))) {
            $result = [];
            foreach ($this->members()->pluck('id') as $user) {
                $this->merge($result, (int) $user, 'direct');
            }

            return $result;
        }
        $teachers = ($review || $restricted) ? [] : app(AcademicAuthority::class)->teachers($actor);
        $groups = TeachingAllocation::whereIn('teacher_id', $teachers)->whereDate('starts_on', '<=', $date)->whereDate('ends_on', '>=', $date)->select('teaching_group_id');
        $homes = HomeroomAllocation::whereIn('teacher_id', $teachers)->where('session_year_id', $year)->whereDate('starts_on', '<=', $date)->whereDate('ends_on', '>=', $date)->select('class_section_id');
        $gradeIds = $restricted ? [] : $effective['head_grades'];
        $enrolments = $this->enrolments($year, $date)->where(function ($query) use ($groups, $homes, $gradeIds, $date, $year) {
            $query->whereIn('class_section_id', $homes)->orWhereHas('homeroom.class', fn ($q) => $q->whereIn('grade_level_id', $gradeIds))
                ->orWhereHas('homeroom.class', function ($class) use ($groups, $date, $year) {
                    $class->whereExists(function ($membership) use ($groups, $date, $year) {
                        $school = app(CurrentSchool::class)->id();
                        $membership->selectRaw('1')->from('teaching_group_memberships as comm_member')
                            ->join('teaching_groups as comm_group', 'comm_group.id', '=', 'comm_member.teaching_group_id')
                            ->where('comm_member.school_id', $school)->where('comm_group.school_id', $school)
                            ->whereNull('comm_member.deleted_at')->whereNull('comm_group.deleted_at')->where('comm_group.active', true)
                            ->where('comm_group.session_year_id', $year)->whereIn('comm_group.id', $groups)
                            ->whereColumn('comm_member.learner_id', 'enrolments.learner_id')
                            ->whereColumn('comm_group.grade_level_id', 'classes.grade_level_id')
                            ->whereDate('comm_member.starts_on', '<=', $date)->whereDate('comm_member.ends_on', '>=', $date)
                            ->where(fn ($home) => $home->whereNull('comm_group.class_section_id')->orWhereColumn('comm_group.class_section_id', 'enrolments.class_section_id'));
                    });
                });
        });
        $learners = Students::whereIn('id', $enrolments->select('learner_id'))->get(['id', 'user_id', 'father_id', 'mother_id', 'guardian_id']);
        $result = $this->fromLearners($learners, false);
        foreach ($this->fromLearners($learners, true) as $id => $reason) {
            if (! isset($result[$id])) {
                $result[$id] = $reason;
            } else {
                $result[$id]['guardian_of'] = $reason['guardian_of'];
            }
        }

        if (app(ModuleGate::class)->allows('groups')) {
            $authority = app(CommunityAuthority::class);
            $groupIds = [];
            try {
                $groupIds = array_map(fn ($group) => (int) $group->id, $authority->visible($actor, $year, true));
            } catch (HttpExceptionInterface $error) {
                if (! in_array($error->getStatusCode(), [403, 404], true)) {
                    throw $error;
                }
            }
            $activity = $this->activityLearners($groupIds, $year, $date);
            foreach ([false, true] as $parents) {
                foreach ($this->fromLearners($activity, $parents) as $id => $reason) {
                    if (! isset($result[$id])) {
                        $result[$id] = $reason;
                    } else {
                        foreach (['learner_ids', 'guardian_of'] as $key) {
                            $result[$id][$key] = array_values(array_unique([...$result[$id][$key], ...$reason[$key]]));
                        }
                    }
                }
            }
        }

        return $result;
    }
}
