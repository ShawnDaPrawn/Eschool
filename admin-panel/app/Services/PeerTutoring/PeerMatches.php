<?php

declare(strict_types=1);

namespace App\Services\PeerTutoring;

use App\Models\Academics\TeachingGroupMembership;
use App\Models\People\Enrolment;
use App\Models\Students;
use App\Models\User;
use App\Services\Academics\AcademicAuthority;
use App\Services\Growth\MasteryService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class PeerMatches
{
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

    public function forLearner(User $actor, int $groupId, int $learner): array
    {
        $group = app(PeerAccess::class)->group($actor, $groupId);
        $this->participant($actor, $groupId, $learner);
        $mastery = app(MasteryService::class);
        $view = $mastery->view($actor, $learner, (int) $group->subject_id, $groupId);
        $gaps = array_column($view['priorities'], null, 'topic_id');
        if ($gaps === []) {
            return [];
        }
        $today = app(AcademicAuthority::class)->today();
        $peers = TeachingGroupMembership::where('teaching_group_id', $groupId)->where('learner_id', '!=', $learner)
            ->whereDate('starts_on', '<=', $today)->whereDate('ends_on', '>=', $today)->orderBy('learner_id')->limit(201)->pluck('learner_id')->unique();
        abort_if($peers->count() > 200, 422, 'Use a teaching group of at most 200 learners for peer matching.');
        $matches = [];
        foreach ($peers as $id) {
            try {
                $peer = $this->participant($actor, $groupId, (int) $id);
            } catch (ModelNotFoundException|HttpExceptionInterface) {
                continue;
            }
            $evidence = $mastery->view($actor, (int) $id, (int) $group->subject_id, $groupId);
            foreach ($evidence['topics'] as $topic) {
                if (isset($gaps[$topic['id']]) && ($topic['level'] ?? -1) >= 3 && $topic['evidence_count'] > 0) {
                    $matches[] = ['tutor_learner_id' => (int) $peer->id, 'tutor_name' => $peer->user->full_name,
                        'tutee_learner_id' => $learner, 'topic_id' => $topic['id'], 'topic_name' => $topic['name'], 'reason' => $gaps[$topic['id']]['reason']];
                }
                if (count($matches) >= 50) {
                    break 2;
                }
            }
        }
        app(PeerAccess::class)->group($actor, $groupId);
        $this->participant($actor, $groupId, $learner);

        return $matches;
    }

    public function selected(User $actor, int $group, int $tutor, int $tutee, int $topic): array
    {
        abort_if($tutor === $tutee, 422, 'Choose two different learners.');
        foreach ($this->forLearner($actor, $group, $tutee) as $match) {
            if ($match['tutor_learner_id'] === $tutor && $match['topic_id'] === $topic) {
                return $match;
            }
        }
        abort(409, 'The mastery match changed. Refresh the available pairs.');
    }
}
