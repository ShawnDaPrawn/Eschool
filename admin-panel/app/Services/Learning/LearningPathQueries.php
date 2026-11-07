<?php

declare(strict_types=1);

namespace App\Services\Learning;

use App\Models\Academics\TeachingGroup;
use App\Models\Academics\TeachingGroupMembership;
use App\Models\Growth\MasteryTopic;
use App\Models\Learning\LearningPath;
use App\Models\Learning\LearningPathReview;
use App\Models\Students;
use App\Models\Subject;
use App\Models\User;
use App\Policies\Growth\MasteryPolicy;
use App\Policies\People\ProfilePolicy;
use App\Services\Academics\AcademicAuthority;
use App\Services\Platform\Identity\ApprovalService;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;

/**
 * Who else sees a learning path (Action Plan 3.9): a teacher of the group
 * sees which step each of its learners is on, in learner order and with no
 * scores or rankings; a guardian sees a plain summary for their own child.
 * Neither sees questions, answers or tutor conversations.
 */
class LearningPathQueries
{
    public const STEP_LABELS = ['not_started' => 'Not started yet', 'gap' => 'Choosing what to learn next', 'teach' => 'Learning something new',
        'practise' => 'Practising', 'check' => 'Checking understanding', 'review' => 'Review planned'];

    private function enabled(): void
    {
        $gate = app(ModuleGate::class);
        $gate->forget();
        abort_unless($gate->allows('mastery') && $gate->allows('daily_practice'), 403, __('The learning path is not available.'));
    }

    /** A teacher (or academic leader) who may read the group's marks, as for the group's mastery map. */
    private function staff(User $actor, int $groupId): TeachingGroup
    {
        $this->enabled();

        return app(MasteryPolicy::class)->staff($actor, $groupId);
    }

    public function group(User $actor, int $groupId, int $page = 1): array
    {
        $group = $this->staff($actor, $groupId);
        $today = app(AcademicAuthority::class)->today();
        $ids = TeachingGroupMembership::query()->where('teaching_group_id', $groupId)->whereDate('starts_on', '<=', $today)->whereDate('ends_on', '>=', $today)->select('learner_id');
        $learners = Students::query()->with('user')->whereIn('id', $ids)->orderBy('id')->paginate(100, ['*'], 'page', max(1, $page));
        $paths = LearningPath::where('teaching_group_id', $groupId)->whereIn('learner_id', $learners->getCollection()->pluck('id'))->get()->keyBy('learner_id');
        $names = MasteryTopic::query()->where('subject_id', $group->subject_id)->whereIn('id', $paths->pluck('topic_id')->merge($paths->pluck('rest_topic_id'))->filter())->pluck('name', 'id');
        $reviews = LearningPathReview::query()->whereIn('learning_path_id', $paths->pluck('id'))->where('due_on', '>=', $today)
            ->selectRaw('learning_path_id, MIN(due_on) as next_due')->groupBy('learning_path_id')->pluck('next_due', 'learning_path_id');
        $rows = $learners->getCollection()->map(function ($learner) use ($paths, $names, $reviews, $today) {
            $path = $paths->get($learner->id);
            $resting = $path?->rest_until !== null && $path->rest_until->toDateString() > $today;

            return ['learner_id' => (int) $learner->id, 'display_name' => $learner->user === null ? __('Archived learner') : trim($learner->user->first_name.' '.$learner->user->last_name),
                'step' => $path?->step ?? 'not_started', 'step_label' => __(self::STEP_LABELS[$path?->step ?? 'not_started']),
                'topic' => $path?->topic_id === null ? null : ['id' => (int) $path->topic_id, 'name' => (string) $names->get($path->topic_id)],
                'resting_topic' => $resting ? (string) $names->get($path->rest_topic_id) : null, 'needs_support' => $resting || ($path?->failed_checks ?? 0) >= 2,
                'next_review_on' => $path === null ? null : (($date = $reviews->get($path->id)) === null ? null : substr((string) $date, 0, 10)),
                'updated_at' => $path?->updated_at?->toISOString()];
        })->all();
        $this->staff($actor, $groupId);

        return ['school_id' => app(CurrentSchool::class)->id(), 'reader_id' => (int) $actor->id, 'group_id' => $groupId, 'subject_id' => (int) $group->subject_id,
            'current_page' => $learners->currentPage(), 'last_page' => $learners->lastPage(), 'total' => $learners->total(), 'learners' => $rows];
    }

    /** A guardian's plain summary of their own approved child's paths this school year. */
    public function family(User $actor, int $learnerId): array
    {
        $this->enabled();
        $fresh = app(ProfilePolicy::class)->actor($actor);
        abort_unless(in_array('Parent', app(ProfilePolicy::class)->roles($fresh), true), 404);
        $learner = app(MasteryPolicy::class)->family($fresh, $learnerId);
        abort_unless(app(ApprovalService::class)->approved($learner), 403, 'Guardian approval required.');
        $today = app(AcademicAuthority::class)->today();
        $paths = LearningPath::where('learner_id', $learnerId)->where('session_year_id', (int) schoolSetting('session_year'))->orderBy('subject_id')->limit(100)->get();
        $subjects = Subject::query()->whereIn('id', $paths->pluck('subject_id'))->pluck('name', 'id');
        $names = MasteryTopic::query()->whereIn('id', $paths->pluck('topic_id')->filter())->pluck('name', 'id');
        $reviews = LearningPathReview::query()->whereIn('learning_path_id', $paths->pluck('id'))->where('due_on', '>=', $today)
            ->selectRaw('learning_path_id, MIN(due_on) as next_due')->groupBy('learning_path_id')->pluck('next_due', 'learning_path_id');
        $rows = $paths->map(fn ($path) => ['subject_id' => (int) $path->subject_id, 'subject_name' => (string) $subjects->get($path->subject_id),
            'step' => $path->step, 'step_label' => __(self::STEP_LABELS[$path->step]),
            'topic_name' => $path->topic_id === null ? null : (string) $names->get($path->topic_id),
            'next_review_on' => ($date = $reviews->get($path->id)) === null ? null : substr((string) $date, 0, 10),
            'updated_on' => $path->updated_at?->toDateString()])->values()->all();
        $this->enabled();
        app(MasteryPolicy::class)->family($fresh, $learnerId);

        return ['school_id' => app(CurrentSchool::class)->id(), 'reader_id' => (int) $actor->id, 'learner_id' => $learnerId, 'subjects' => $rows,
            'suggestion' => __('Ask your child to show you one thing they learnt on their learning path this week.')];
    }
}
