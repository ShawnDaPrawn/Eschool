<?php

declare(strict_types=1);

namespace App\Services\Learning\Tests;

use App\Models\Academics\TeachingGroup;
use App\Models\Academics\TeachingGroupMembership;
use App\Models\Learning\Tests\PracticeTest;
use App\Models\Learning\Tests\PracticeTestBlueprint;
use App\Models\Learning\Tests\PracticeTestQuestion;
use App\Models\Students;
use App\Models\User;
use App\Services\Academics\AcademicAuthority;
use App\Services\Growth\TopicGraph;
use App\Services\Learning\ExamCoachQueries;
use App\Services\Learning\PracticeAccess;
use App\Services\Learning\PracticeOperations;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * Teachers set cycle tests for a group they are allocated to today
 * (Action Plan 3.17): which topics, how many questions, how they are shared
 * between the topics (evenly or by past paper topic shares), the time limit
 * and the window. Results are shown per learner in name order: no ranking.
 */
class TestBlueprints
{
    /** What the teacher can choose from for a group. */
    public function options(User $actor, int $groupId): array
    {
        $group = app(PracticeAccess::class)->staff($actor, $groupId);
        $writable = app(ModuleGate::class)->writable('daily_practice');
        if ($writable) {
            app(TopicGraph::class)->sync((int) $group->subject_id);
        }
        $pool = app(TestQuestionPool::class);
        $topics = $pool->topics($group);
        $candidates = $pool->candidates($group, array_keys($topics));
        $rows = array_map(fn ($topic) => $topic + ['available' => count($candidates[$topic['id']] ?? [])], array_values($topics));
        $papers = [];
        $gate = app(ModuleGate::class);
        if ($gate->allows('exam_coach')) {
            try {
                foreach (app(ExamCoachQueries::class)->papers($actor, $groupId, true, 1)['papers'] as $paper) {
                    if ($paper['state'] === 'published') {
                        $papers[] = ['version_id' => $paper['version_id'], 'title' => $paper['title'], 'paper_year' => $paper['paper_year'], 'paper_code' => $paper['paper_code']];
                    }
                }
            } catch (HttpExceptionInterface|ModelNotFoundException) {
                $papers = [];
            }
        }
        app(PracticeAccess::class)->staff($actor, $groupId);
        $limits = TestKinds::limits(TestKinds::CYCLE_TEST);

        return ['group_id' => $groupId, 'topics' => $rows, 'papers' => $papers, 'weightings' => TestTopicShares::WEIGHTINGS,
            'limits' => ['topics_max' => $limits['topics'][1], 'questions_min' => $limits['questions'][0], 'questions_max' => $limits['questions'][1],
                'minutes_min' => $limits['minutes'][0], 'minutes_max' => $limits['minutes'][1], 'window_days_max' => $limits['window_days']],
            'writable' => $writable];
    }

    /** @return array<string, list<mixed>> */
    public static function rules(): array
    {
        $limits = TestKinds::limits(TestKinds::CYCLE_TEST);

        return ['operation_id' => ['required', 'uuid'], 'title' => ['required', 'string', 'min:3', 'max:120'],
            'topic_ids' => ['required', 'array', 'min:'.$limits['topics'][0], 'max:'.$limits['topics'][1]], 'topic_ids.*' => ['required', 'integer', 'min:1', 'distinct'],
            'question_count' => ['required', 'integer', 'between:'.$limits['questions'][0].','.$limits['questions'][1]],
            'time_limit_minutes' => ['required', 'integer', 'between:'.$limits['minutes'][0].','.$limits['minutes'][1]],
            'opens_at' => ['required', 'date'], 'closes_at' => ['required', 'date'], 'weighting' => ['required', Rule::in(TestTopicShares::WEIGHTINGS)],
            'paper_version_ids' => ['array', 'max:5', 'required_if:weighting,past_papers', 'prohibited_unless:weighting,past_papers'],
            'paper_version_ids.*' => ['required', 'uuid', 'distinct']];
    }

    public function create(User $actor, int $groupId, array $input): array
    {
        $data = Validator::make($input, self::rules())->validate();
        $data['topic_ids'] = array_map(intval(...), $data['topic_ids']);
        sort($data['topic_ids']);
        $data['paper_version_ids'] ??= [];
        sort($data['paper_version_ids']);
        $zone = app(CurrentSchool::class)->school()->timezone;
        $opens = CarbonImmutable::parse($data['opens_at'], $zone)->utc()->startOfMinute();
        $closes = CarbonImmutable::parse($data['closes_at'], $zone)->utc()->startOfMinute();
        $this->check($closes->isAfter($opens) && $closes->isFuture(), 'closes_at', __('Choose a closing time after the opening time and in the future.'));
        $this->check($opens->diffInDays($closes) <= TestKinds::limits(TestKinds::CYCLE_TEST)['window_days'], 'closes_at', __('Keep a cycle test open for at most 31 days.'));
        $data['opens_at'] = $opens->toIso8601ZuluString();
        $data['closes_at'] = $closes->toIso8601ZuluString();
        $authorize = fn () => app(PracticeAccess::class)->staff($actor, $groupId, true);

        return app(PracticeOperations::class)->run($actor, $data + ['group_id' => $groupId, 'kind' => TestKinds::CYCLE_TEST], $authorize, function () use ($actor, $groupId, $data, $opens, $closes) {
            $group = app(PracticeAccess::class)->staff($actor, $groupId, true);
            app(TopicGraph::class)->sync((int) $group->subject_id);
            $shares = $this->shares($actor, $group, $data['topic_ids'], (int) $data['question_count'], $data['weighting'], $data['paper_version_ids']);
            $row = PracticeTestBlueprint::create(['teaching_group_id' => $group->id, 'subject_id' => $group->subject_id, 'grade_level_id' => $group->grade_level_id,
                'session_year_id' => $group->session_year_id, 'kind' => TestKinds::CYCLE_TEST, 'created_by' => $actor->id, 'learner_id' => null,
                'title' => trim($data['title']), 'topic_ids' => $data['topic_ids'], 'weighting' => $data['weighting'], 'paper_version_ids' => $data['paper_version_ids'],
                'topic_shares' => $shares, 'question_count' => (int) $data['question_count'], 'time_limit_seconds' => (int) $data['time_limit_minutes'] * 60,
                'opens_at' => $opens, 'closes_at' => $closes, 'active' => true, 'revision' => 1]);
            Audit::record('learning.practice_test_set', $row, [], ['kind' => $row->kind, 'group_id' => (int) $group->id, 'question_count' => $row->question_count,
                'weighting' => $row->weighting, 'topic_count' => count($data['topic_ids'])]);

            return ['id' => (int) $row->id, 'revision' => 1, 'active' => true];
        });
    }

    /**
     * Topic shares for a blueprint: weights, then how many questions each
     * topic gets given what the group's bank and library hold.
     *
     * @return list<array{topic_id: int, weight: int, questions: int}>
     */
    public function shares(User $actor, TeachingGroup $group, array $topicIds, int $count, string $weighting, array $paperVersionIds): array
    {
        $pool = app(TestQuestionPool::class);
        $topics = $pool->topics($group);
        $this->check(array_diff($topicIds, array_keys($topics)) === [], 'topic_ids', __('Choose topics from this group\'s published curriculum.'));
        $available = array_map('count', $pool->candidates($group, $topicIds));
        $weights = app(TestTopicShares::class)->weights($actor, (int) $group->id, $topicIds, $weighting, $paperVersionIds);
        $allocation = app(TestTopicShares::class)->allocate($count, $weights, $available);
        $ready = array_sum($allocation);
        $this->check($ready >= $count, 'question_count', trans_choice('Only :count question is ready for these topics. Choose fewer questions or more topics.|Only :count questions are ready for these topics. Choose fewer questions or more topics.', $ready, ['count' => $ready]));

        return array_values(array_map(fn ($topic) => ['topic_id' => (int) $topic, 'weight' => (int) $weights[$topic], 'questions' => (int) $allocation[$topic]],
            array_keys(array_filter($allocation, fn ($questions) => $questions > 0))));
    }

    public function withdraw(User $actor, int $groupId, int $blueprintId, array $input): array
    {
        $data = Validator::make($input, ['operation_id' => ['required', 'uuid'], 'expected_revision' => ['required', 'integer', 'min:1']])->validate();
        $authorize = function () use ($actor, $groupId, $blueprintId) {
            app(PracticeAccess::class)->staff($actor, $groupId, true);
            $this->row($groupId, $blueprintId);
        };

        return app(PracticeOperations::class)->run($actor, $data + ['group_id' => $groupId, 'blueprint_id' => $blueprintId, 'action' => 'withdraw'], $authorize, function () use ($groupId, $blueprintId, $data) {
            $row = PracticeTestBlueprint::where('teaching_group_id', $groupId)->where('kind', TestKinds::CYCLE_TEST)->lockForUpdate()->findOrFail($blueprintId);
            abort_unless($row->revision === (int) $data['expected_revision'], 409, __('This test changed. Reload it first.'));
            abort_unless($row->active, 409, __('This test was already withdrawn.'));
            $row->update(['active' => false, 'revision' => $row->revision + 1]);
            Audit::record('learning.practice_test_withdrawn', $row, ['active' => true], ['active' => false]);

            return ['id' => (int) $row->id, 'revision' => (int) $row->revision, 'active' => false];
        });
    }

    /** The group's cycle tests, newest window first. */
    public function index(User $actor, int $groupId, int $page = 1): array
    {
        abort_unless($page >= 1 && $page <= 1000, 422);
        app(PracticeAccess::class)->staff($actor, $groupId);
        $rows = PracticeTestBlueprint::where('teaching_group_id', $groupId)->where('kind', TestKinds::CYCLE_TEST)
            ->orderByDesc('opens_at')->orderByDesc('id')->offset(($page - 1) * 25)->limit(26)->get();
        $handedIn = PracticeTest::whereIn('practice_test_blueprint_id', $rows->pluck('id'))->whereNotNull('submitted_at')
            ->selectRaw('practice_test_blueprint_id, COUNT(*) AS total')->groupBy('practice_test_blueprint_id')->pluck('total', 'practice_test_blueprint_id');
        $started = PracticeTest::whereIn('practice_test_blueprint_id', $rows->pluck('id'))
            ->selectRaw('practice_test_blueprint_id, COUNT(*) AS total')->groupBy('practice_test_blueprint_id')->pluck('total', 'practice_test_blueprint_id');
        $items = $rows->take(25)->map(fn ($row) => $this->summary($row) + ['started' => (int) ($started[$row->id] ?? 0), 'handed_in' => (int) ($handedIn[$row->id] ?? 0)])->values()->all();
        app(PracticeAccess::class)->staff($actor, $groupId);

        return ['group_id' => $groupId, 'tests' => $items, 'page' => $page, 'has_more' => $rows->count() > 25];
    }

    /**
     * Each current learner of the group, in name order, with where they are
     * and how many they got right; and how the group did per topic.
     */
    public function results(User $actor, int $groupId, int $blueprintId): array
    {
        $group = app(PracticeAccess::class)->staff($actor, $groupId);
        $row = $this->row($groupId, $blueprintId);
        $today = app(AcademicAuthority::class)->today();
        $members = TeachingGroupMembership::where('teaching_group_id', $groupId)->whereDate('starts_on', '<=', $today)->whereDate('ends_on', '>=', $today)
            ->limit(501)->pluck('learner_id')->map(fn ($id) => (int) $id)->unique()->values();
        abort_if($members->count() > 500, 422);
        $sittings = PracticeTest::where('practice_test_blueprint_id', $row->id)->get()->keyBy('learner_id');
        $ids = $members->merge($sittings->keys())->unique()->values();
        $people = Students::with('user:id,first_name,last_name')->whereIn('id', $ids)->get(['id', 'user_id'])->keyBy('id');
        $now = CarbonImmutable::now('UTC');
        $learners = $ids->map(function ($id) use ($people, $sittings, $now) {
            $sitting = $sittings->get($id);
            $user = $people->get($id)?->user;

            return ['learner_id' => (int) $id, 'name' => trim(($user?->first_name ?? '').' '.($user?->last_name ?? '')), 'sort' => [$user?->last_name ?? '', $user?->first_name ?? '', $id],
                'state' => $sitting === null ? 'not_started' : app(PracticeTests::class)->state($sitting, $now),
                'correct_count' => $sitting?->correct_count, 'marked_count' => $sitting?->marked_count, 'closed_by' => $sitting?->closed_by];
        })->sortBy('sort')->map(fn ($learner) => array_diff_key($learner, ['sort' => true]))->values()->all();
        $topics = app(TestQuestionPool::class)->topics($group);
        $summary = [];
        $marked = PracticeTestQuestion::whereIn('practice_test_id', $sittings->filter(fn ($s) => $s->submitted_at !== null)->pluck('id'))->where('marking', 'auto')
            ->selectRaw('topic_id, SUM(correct) AS correct, COUNT(*) AS answered')->groupBy('topic_id')->get();
        foreach ($marked as $topic) {
            $summary[] = ['topic_id' => (int) $topic->topic_id, 'name' => $topics[(int) $topic->topic_id]['name'] ?? __('Earlier topic'),
                'correct' => (int) $topic->correct, 'answered' => (int) $topic->answered];
        }
        usort($summary, fn ($a, $b) => $a['topic_id'] <=> $b['topic_id']);
        app(PracticeAccess::class)->staff($actor, $groupId);

        return $this->summary($row) + ['learners' => $learners, 'topics' => $summary];
    }

    /** A choice the teacher can fix: a validation error on that field, shown in the app and the panel. */
    private function check(bool $valid, string $field, string $message): void
    {
        if (! $valid) {
            throw ValidationException::withMessages([$field => $message]);
        }
    }

    private function row(int $groupId, int $blueprintId): PracticeTestBlueprint
    {
        return PracticeTestBlueprint::where('teaching_group_id', $groupId)->where('kind', TestKinds::CYCLE_TEST)->findOrFail($blueprintId);
    }

    /** The public shape of a cycle test blueprint, for staff and learners alike. */
    public function summary(PracticeTestBlueprint $row): array
    {
        return ['id' => (int) $row->id, 'kind' => $row->kind, 'group_id' => (int) $row->teaching_group_id, 'title' => (string) $row->title,
            'question_count' => (int) $row->question_count, 'time_limit_minutes' => intdiv((int) $row->time_limit_seconds, 60),
            'opens_at' => $row->opens_at?->toIso8601ZuluString(), 'closes_at' => $row->closes_at?->toIso8601ZuluString(),
            'weighting' => $row->weighting, 'topic_shares' => $row->topic_shares, 'active' => $row->active, 'revision' => (int) $row->revision];
    }
}
