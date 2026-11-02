<?php

declare(strict_types=1);

namespace App\Services\Pathways;

use App\Models\Academics\TeachingAllocation;
use App\Models\Academics\TeachingGroup;
use App\Models\Academics\TeachingGroupMembership;
use App\Models\Growth\MasteryRecord;
use App\Models\People\Enrolment;
use App\Models\Students;
use App\Models\Subject;
use App\Models\User;
use App\Policies\Growth\MasteryPolicy;
use App\Policies\People\ProfilePolicy;
use App\Services\Academics\AcademicAuthority;
use App\Services\Growth\MasteryService;
use App\Services\Growth\TopicGraph;
use App\Services\Platform\Identity\CurrentLearnerMembership;
use App\Services\Platform\Modules\ModuleGate;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * The staff "what would it take" view (Action Plan 4.9): one learner, one
 * career profile, the profile's sourced subjects against the learner's current
 * subjects, and mastery on the linked topics. Read-only. Missing or unshared
 * evidence is unknown, never zero, and nothing here rates the learner.
 *
 * Who may use it: holders of pathways-manage for any current learner of the
 * school, and teachers for the learners in the teaching groups they are
 * currently allocated to. Mastery evidence keeps its own rule: it shows only
 * where MasteryPolicy::staff lets this reader see the learner's subject group.
 */
class CareerGuidance
{
    /** @return array{0: User, 1: bool} the fresh actor and whether they may see every learner */
    public function actor(User $actor): array
    {
        $fresh = app(PathwayAccess::class)->actor($actor);
        $policy = app(ProfilePolicy::class);
        abort_unless(array_diff($policy->roles($fresh), ['Parent', 'Student']) !== [], 403);
        $manager = $policy->permission($fresh, PathwayAccess::MANAGE);
        abort_unless($manager || $this->groups($fresh) !== [], 403);

        return [$fresh, $manager];
    }

    public function available(User $actor): bool
    {
        try {
            $this->actor($actor);

            return true;
        } catch (HttpExceptionInterface|ModelNotFoundException) {
            return false;
        }
    }

    /** Active teaching groups the actor currently teaches. */
    private function groups(User $fresh): array
    {
        $teachers = app(AcademicAuthority::class)->teachers($fresh);
        if ($teachers === []) {
            return [];
        }
        $today = app(AcademicAuthority::class)->today();

        return TeachingAllocation::query()->whereIn('teacher_id', $teachers)->whereDate('starts_on', '<=', $today)->whereDate('ends_on', '>=', $today)
            ->whereHas('group', fn ($q) => $q->where('active', true))->distinct()->pluck('teaching_group_id')->map(fn ($id) => (int) $id)->all();
    }

    private function memberships(): mixed
    {
        $today = app(AcademicAuthority::class)->today();

        return TeachingGroupMembership::query()->whereDate('starts_on', '<=', $today)->whereDate('ends_on', '>=', $today)
            ->whereHas('group', fn ($q) => $q->where('active', true));
    }

    /** Up to 25 current learners this reader may open, matching a name. */
    public function learners(User $actor, string $search): array
    {
        [$fresh, $manager] = $this->actor($actor);
        $search = trim($search);
        if (mb_strlen($search) < 2) {
            return [];
        }
        $like = '%'.addcslashes($search, '%_\\').'%';
        $query = Students::query()->with('user')->whereIn('user_id', app(CurrentLearnerMembership::class)->users()->select('id'))
            ->whereIn('id', Enrolment::query()->where('session_year_id', schoolSetting('session_year'))->onDate(app(AcademicAuthority::class)->today())->select('learner_id'))
            ->whereHas('user', fn ($q) => $q->where(fn ($w) => $w->where('first_name', 'like', $like)->orWhere('last_name', 'like', $like)));
        if (! $manager) {
            $query->whereIn('id', $this->memberships()->whereIn('teaching_group_id', $this->groups($fresh))->select('learner_id'));
        }

        return $query->orderBy('id')->limit(25)->get()->map(fn (Students $s) => ['id' => (int) $s->id, 'name' => (string) $s->user?->full_name])->all();
    }

    /** A current learner of this school that this reader may open; otherwise 404. */
    public function learner(User $actor, int $learnerId): Students
    {
        [$fresh, $manager] = $this->actor($actor);
        $learner = app(PathwayAccess::class)->learner($learnerId, false);
        if (! $manager) {
            abort_unless($this->memberships()->where('learner_id', $learner->id)->whereIn('teaching_group_id', $this->groups($fresh))->exists(), 404);
        }

        return $learner;
    }

    public function view(User $actor, int $learnerId, string $profileId): array
    {
        $learner = $this->learner($actor, $learnerId);
        $profile = app(CareerProfiles::class)->data(app(CareerProfiles::class)->current($profileId));
        $groups = TeachingGroup::query()->whereIn('id', $this->memberships()->where('learner_id', $learner->id)->select('teaching_group_id'))->get();
        $current = $groups->pluck('subject_id')->map(fn ($id) => (int) $id)->unique()->values()->all();
        $wanted = array_merge(...array_column($profile['details']['subjects'], 'subject_ids'));
        $names = Subject::withTrashed()->whereIn('id', array_merge($current, $wanted))->pluck('name', 'id');
        $subjects = [];
        foreach ($profile['details']['subjects'] as $row) {
            $taking = array_values(array_intersect($row['subject_ids'], $current));
            $subjects[] = $row + ['names' => array_map(fn ($id) => (string) $names->get($id), $row['subject_ids']), 'taking' => $taking !== [],
                'taking_names' => array_map(fn ($id) => (string) $names->get($id), $taking)];
        }
        $topics = [];
        $linked = $profile['details']['topic_ids'];
        foreach ($linked === [] ? [] : array_unique($wanted) as $subjectId) {
            $section = $this->topics($actor, (int) $learner->id, (int) $subjectId, $linked, $groups->where('subject_id', $subjectId)->pluck('id')->all());
            if ($section['topics'] !== []) {
                $topics[] = $section + ['subject_id' => (int) $subjectId, 'subject_name' => (string) $names->get($subjectId)];
            }
        }
        $routes = [];
        foreach ($profile['details']['routes'] as $route) {
            $programme = null;
            if ($route['programme_id'] !== null) {
                try {
                    $row = app(PathwayReferences::class)->current($route['programme_id']);
                    $programme = ['id' => $row->id, 'title' => app(PathwayReferences::class)->data($row)['title']];
                } catch (HttpExceptionInterface|ModelNotFoundException) {
                    // A withdrawn or expired programme source is not offered.
                }
            }
            $routes[] = $route + ['programme' => $programme];
        }
        // A delayed read cannot outlive the reader's authority.
        $this->learner($actor, (int) $learner->id);

        return ['learner' => ['id' => (int) $learner->id, 'name' => (string) $learner->user?->full_name], 'profile' => $profile,
            'current_subjects' => array_map(fn ($id) => ['id' => $id, 'name' => (string) $names->get($id)], $current),
            'subjects' => $subjects, 'topics' => $topics, 'routes' => $routes];
    }

    /**
     * The linked topics of one subject with this reader's view of the learner's
     * mastery, and up to three starting points from MasteryService::roots.
     */
    private function topics(User $actor, int $learnerId, int $subjectId, array $linked, array $groupIds): array
    {
        // Published career revisions retain their requirements after a subject retires.
        // Its active graph is unavailable; keep the other requirements and routes readable.
        if (! Subject::query()->whereKey($subjectId)->exists()) {
            return ['evidence' => 'none', 'topics' => [], 'starting_points' => []];
        }
        $graph = app(TopicGraph::class)->data($subjectId);
        $targets = array_values(array_intersect(array_column($graph['nodes'], 'id'), $linked));
        if ($targets === []) {
            return ['evidence' => 'none', 'topics' => [], 'starting_points' => []];
        }
        $evidence = $groupIds === [] ? 'not_taking' : 'not_shared';
        $gate = app(ModuleGate::class);
        $gate->forget();
        if (! $gate->allows('mastery')) {
            $evidence = 'mastery_off';
        } else {
            foreach ($groupIds as $groupId) {
                try {
                    app(MasteryPolicy::class)->staff($actor, (int) $groupId, $learnerId);
                    $evidence = 'shared';
                    break;
                } catch (HttpExceptionInterface|ModelNotFoundException) {
                    // This reader does not see that group's evidence.
                }
            }
        }
        $prerequisites = [];
        foreach ($graph['links'] as $link) {
            $prerequisites[$link['topic_id']][] = $link['prerequisite_id'];
        }
        $visible = array_fill_keys($targets, true);
        $pending = $targets;
        while ($pending !== []) {
            foreach ($prerequisites[array_pop($pending)] ?? [] as $next) {
                if (! isset($visible[$next])) {
                    $visible[$next] = true;
                    $pending[] = $next;
                }
            }
        }
        $nodes = array_values(array_filter($graph['nodes'], fn ($node) => isset($visible[$node['id']])));
        $records = $evidence === 'shared' ? MasteryRecord::query()->where('learner_id', $learnerId)->where('subject_id', $subjectId)->where('audience', 'staff')
            ->whereIn('topic_id', array_column($nodes, 'id'))->get()->keyBy('topic_id') : collect();
        $rows = [];
        foreach ($nodes as $node) {
            $record = $records->get($node['id']);
            $rows[] = $node + ['level' => $record?->level, 'confidence_bp' => $record?->confidence_bp ?? 0, 'evidence_count' => $record?->evidence_count ?? 0];
        }

        return ['evidence' => $evidence, 'topics' => array_values(array_filter($rows, fn ($row) => in_array($row['id'], $targets, true))),
            'starting_points' => $evidence === 'shared' ? app(MasteryService::class)->roots($rows, $graph['links'], $targets) : []];
    }
}
