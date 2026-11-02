<?php

declare(strict_types=1);

namespace App\Services\PeerTutoring;

use App\Models\Academics\TeachingGroupMembership;
use App\Models\Activities\ActivityGroup;
use App\Models\Activities\ActivityMembership;
use App\Models\PeerTutoring\PeerTutoringSession;
use App\Models\People\Enrolment;
use App\Models\Students;
use App\Models\User;
use App\Services\Academics\AcademicAuthority;
use App\Services\Growth\MasteryService;
use App\Services\Platform\Modules\ModuleGate;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * Supervised peer matching. Eligibility is unchanged: a current, approved
 * member of the supervised teaching group with level 3 or 4 evidence in one of
 * the tutee's root gaps. Action Plan 4.7 adds two things. Peers are offered
 * fairest first: fewest scheduled or completed sessions as tutor this term,
 * then by learner number, so the order is deterministic and explainable. And
 * on the panel a peer's captaincy this school year is offered as an activity
 * skill to teach, under the same supervision.
 */
class PeerMatches
{
    public const LIMIT = 50;

    /** The fields of a match on the v2 API (PeerMatch in openapi.yaml). */
    private const API_FIELDS = ['tutor_learner_id', 'tutor_name', 'tutee_learner_id', 'topic_id', 'topic_name', 'reason'];

    public function participant(User $actor, int $group, int $learner, ?string $on = null): Students
    {
        $scope = app(PeerAccess::class);
        $currentGroup = $scope->group($actor, $group);
        $student = $scope->learner($learner);
        $date = $on ?? app(AcademicAuthority::class)->today();
        abort_unless(TeachingGroupMembership::where('teaching_group_id', $group)->where('learner_id', $learner)
            ->whereDate('starts_on', '<=', $date)->whereDate('ends_on', '>=', $date)->exists(), 404);

        abort_unless(Enrolment::where('learner_id', $learner)->where('session_year_id', $currentGroup->session_year_id)->onDate($date)->exists(), 404);

        return $student;
    }

    /** Mastery-topic matches in the v2 API shape, fairest first. */
    public function forLearner(User $actor, int $groupId, int $learner): array
    {
        return array_map(fn (array $match) => array_intersect_key($match, array_flip(self::API_FIELDS)), $this->candidates($actor, $groupId, $learner, false));
    }

    /**
     * Panel matches: mastery topics and activity skills, fairest first, each
     * with kind, activity_group_id, tutored_this_term and the term's label.
     */
    public function ranked(User $actor, int $groupId, int $learner): array
    {
        return $this->candidates($actor, $groupId, $learner, true);
    }

    public function selected(User $actor, int $group, int $tutor, int $tutee, int $topic): array
    {
        abort_if($tutor === $tutee, 422, 'Choose two different learners.');
        foreach ($this->candidates($actor, $group, $tutee, false, $tutor) as $match) {
            if ($match['topic_id'] === $topic) {
                return $match;
            }
        }
        abort(409, 'The mastery match changed. Refresh the available pairs.');
    }

    public function selectedSkill(User $actor, int $group, int $tutor, int $tutee, int $activityGroup): array
    {
        abort_if($tutor === $tutee, 422, 'Choose two different learners.');
        foreach ($this->candidates($actor, $group, $tutee, true, $tutor) as $match) {
            if ($match['kind'] === 'activity_skill' && $match['activity_group_id'] === $activityGroup) {
                return $match;
            }
        }
        abort(409, 'The activity skill match changed. Refresh the available pairs.');
    }

    private function candidates(User $actor, int $groupId, int $learner, bool $skills, ?int $only = null): array
    {
        $group = app(PeerAccess::class)->group($actor, $groupId);
        $this->participant($actor, $groupId, $learner);
        $mastery = app(MasteryService::class);
        $view = $mastery->view($actor, $learner, (int) $group->subject_id, $groupId);
        $gaps = array_column($view['priorities'], null, 'topic_id');
        $skills = $skills && app(ModuleGate::class)->allows('groups');
        if ($gaps === [] && ! $skills) {
            return [];
        }
        $today = app(AcademicAuthority::class)->today();
        $peers = TeachingGroupMembership::where('teaching_group_id', $groupId)->where('learner_id', '!=', $learner)
            ->whereDate('starts_on', '<=', $today)->whereDate('ends_on', '>=', $today)->orderBy('learner_id')->limit(201)->pluck('learner_id')
            ->map(fn ($id) => (int) $id)->unique()->values();
        abort_if($peers->count() > 200, 422, 'Use a teaching group of at most 200 learners for peer matching.');
        if ($only !== null) {
            $peers = $peers->filter(fn (int $id) => $id === $only)->values();
        }
        $window = app(PeerTerms::class)->window($group->grade_level_id === null ? null : (int) $group->grade_level_id);
        $counts = $this->tutoredThisTerm($peers->all(), $window);
        // Fairest first: fewest sessions as tutor this term, then learner number.
        $order = $peers->sortBy(fn (int $id) => sprintf('%010d-%010d', $counts[$id] ?? 0, $id))->values();
        $captaincies = $skills ? $this->captaincies($order->all(), $learner) : [];
        $matches = [];
        foreach ($order as $id) {
            try {
                $peer = $this->participant($actor, $groupId, $id);
            } catch (ModelNotFoundException|HttpExceptionInterface) {
                continue;
            }
            $why = ['tutored_this_term' => $counts[$id] ?? 0, 'term' => $window['label']];
            if ($gaps !== []) {
                $evidence = $mastery->view($actor, $id, (int) $group->subject_id, $groupId);
                foreach ($evidence['topics'] as $topic) {
                    if (isset($gaps[$topic['id']]) && ($topic['level'] ?? -1) >= 3 && $topic['evidence_count'] > 0) {
                        $matches[] = ['tutor_learner_id' => (int) $peer->id, 'tutor_name' => $peer->user->full_name,
                            'tutee_learner_id' => $learner, 'topic_id' => $topic['id'], 'topic_name' => $topic['name'], 'reason' => $gaps[$topic['id']]['reason'],
                            'kind' => 'topic', 'activity_group_id' => null] + $why;
                    }
                    if (count($matches) >= self::LIMIT) {
                        break 2;
                    }
                }
            }
            foreach ($captaincies[$id] ?? [] as $activity) {
                $matches[] = ['tutor_learner_id' => (int) $peer->id, 'tutor_name' => $peer->user->full_name,
                    'tutee_learner_id' => $learner, 'topic_id' => null, 'topic_name' => $activity['name'], 'reason' => 'activity_skill',
                    'kind' => 'activity_skill', 'activity_group_id' => $activity['id']] + $why;
                if (count($matches) >= self::LIMIT) {
                    break 2;
                }
            }
        }
        app(PeerAccess::class)->group($actor, $groupId);
        $this->participant($actor, $groupId, $learner);

        return $matches;
    }

    /**
     * Scheduled or completed sessions as tutor in the term window, anywhere in
     * this school. Cancelled and corrected sessions do not count.
     *
     * @param  list<int>  $learners
     * @return array<int, int>
     */
    public function tutoredThisTerm(array $learners, array $window, array $statuses = ['scheduled', 'completed']): array
    {
        if ($learners === []) {
            return [];
        }

        return PeerTutoringSession::query()->whereIn('tutor_learner_id', $learners)->whereIn('status', $statuses)
            ->where('starts_at', '>=', $window['starts'])->where('starts_at', '<=', $window['ends'])
            ->selectRaw('tutor_learner_id, count(*) as total')->groupBy('tutor_learner_id')->pluck('total', 'tutor_learner_id')
            ->mapWithKeys(fn ($total, $id) => [(int) $id => (int) $total])->all();
    }

    /**
     * Recognised activity skills: a captaincy held this school year (begun,
     * not withdrawn) in an active group of the current year. A group the tutee
     * captains too is left out.
     *
     * @param  list<int>  $peers
     * @return array<int, list<array{id: int, name: string}>>
     */
    private function captaincies(array $peers, int $tutee): array
    {
        if ($peers === []) {
            return [];
        }
        $rows = ActivityMembership::query()->where('role', 'captain')->where('active', true)
            ->whereDate('starts_on', '<=', app(AcademicAuthority::class)->today())->whereIn('learner_id', [...$peers, $tutee])
            ->whereIn('activity_group_id', ActivityGroup::query()->where('session_year_id', (int) schoolSetting('session_year'))->where('active', true)->select('id'))
            ->orderBy('activity_group_id')->get(['learner_id', 'activity_group_id']);
        $own = $rows->where('learner_id', $tutee)->pluck('activity_group_id')->map(fn ($id) => (int) $id)->all();
        $names = ActivityGroup::query()->whereIn('id', $rows->pluck('activity_group_id')->unique())->pluck('name', 'id');
        $result = [];
        foreach ($rows as $row) {
            $learner = (int) $row->learner_id;
            $group = (int) $row->activity_group_id;
            if ($learner === $tutee || in_array($group, $own, true) || isset($result[$learner][$group]) || ! $names->has($group)) {
                continue;
            }
            $result[$learner][$group] = ['id' => $group, 'name' => (string) $names[$group]];
        }

        return array_map('array_values', $result);
    }
}
