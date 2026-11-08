<?php

declare(strict_types=1);

namespace App\Services\Learning\Tests;

use App\Models\Academics\TeachingGroup;
use App\Models\Learning\PracticeItemVersion;
use App\Models\Learning\Tests\PracticeTest;
use App\Models\Learning\Tests\PracticeTestBlueprint;
use App\Models\Learning\Tests\PracticeTestQuestion;
use App\Models\Library\LibraryItemVersion;
use App\Models\Students;
use App\Models\User;
use App\Policies\Academics\ContentPolicy;
use App\Policies\People\ProfilePolicy;
use App\Services\Academics\ReleasedMarks;
use App\Services\Growth\MasteryRecompute;
use App\Services\Growth\TopicGraph;
use App\Services\Learning\PracticeAccess;
use App\Services\Learning\PracticeOperations;
use App\Services\Learning\Tests\Marking\TestMarkers;
use App\Services\Platform\Identity\ApprovalService;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * A learner's timed practice tests (Action Plan 3.17). The server holds the
 * deadline and the answers: before a sitting is handed in the device gets
 * each question's prompt and options only, never its accepted answers or
 * worked explanation. Answers saved after the deadline (plus a short grace
 * for the network) are refused; a late hand-in marks only the answers saved
 * in time. Auto-marked answers become mastery evidence labelled
 * practice_test. Only the learner themself, approved and in the group,
 * reaches any of this.
 */
class PracticeTests
{
    /** Seconds after the deadline in which a save or hand-in still counts, for the network. */
    public const GRACE_SECONDS = 30;

    /** Quick quizzes a learner may start per school day. */
    public const QUICK_QUIZZES_PER_DAY = 20;

    public function state(PracticeTest $test, CarbonImmutable $now): string
    {
        if ($test->submitted_at !== null) {
            return 'handed_in';
        }

        return $now->isAfter($test->deadline_at->addSeconds(self::GRACE_SECONDS)) ? 'time_up' : 'open';
    }

    /** The approved learner themself; a guardian, another learner or a teacher is not found. */
    private function owner(User $actor, int $learner, bool $write = false): Students
    {
        $fresh = app(PracticeAccess::class)->actor($actor, $write);
        $person = app(ReleasedMarks::class)->learner($fresh, $learner);
        abort_unless((int) $person->user_id === (int) $fresh->id && in_array('Student', app(ProfilePolicy::class)->roles($fresh), true), 404);
        abort_unless(app(ApprovalService::class)->approved($person), 403);

        return $person;
    }

    /** Open and coming cycle tests in the learner's groups, and their own recent quick quizzes. */
    public function index(User $actor, int $learner): array
    {
        $this->owner($actor, $learner);
        $groups = [];
        foreach (app(ContentPolicy::class)->learnerGroups($actor, $learner) as $groupId) {
            try {
                app(PracticeAccess::class)->learner($actor, $learner, $groupId);
                $groups[] = $groupId;
            } catch (HttpExceptionInterface|ModelNotFoundException) {
                continue;
            }
        }
        $now = CarbonImmutable::now('UTC');
        $sittings = PracticeTest::where('learner_id', $learner)->where('user_id', $actor->id)->whereIn('teaching_group_id', $groups)->get()->keyBy('practice_test_blueprint_id');
        $cycle = PracticeTestBlueprint::whereIn('teaching_group_id', $groups)->where('kind', TestKinds::CYCLE_TEST)
            ->where(fn ($q) => $q->where(fn ($open) => $open->where('active', true)->where('closes_at', '>', $now))->orWhereIn('id', $sittings->keys()))
            ->orderBy('opens_at')->orderBy('id')->limit(50)->get();
        $quick = PracticeTestBlueprint::whereIn('teaching_group_id', $groups)->where('kind', TestKinds::QUICK_QUIZ)->where('learner_id', $learner)
            ->orderByDesc('id')->limit(20)->get();
        $rows = [];
        foreach ($cycle->concat($quick) as $blueprint) {
            $sitting = $sittings->get($blueprint->id);
            $rows[] = ['blueprint_id' => (int) $blueprint->id, 'kind' => $blueprint->kind, 'group_id' => (int) $blueprint->teaching_group_id, 'title' => $blueprint->title,
                'question_count' => (int) $blueprint->question_count, 'time_limit_minutes' => $blueprint->time_limit_seconds === null ? null : intdiv($blueprint->time_limit_seconds, 60),
                'opens_at' => $blueprint->opens_at?->toIso8601ZuluString(), 'closes_at' => $blueprint->closes_at?->toIso8601ZuluString(),
                'window' => $this->window($blueprint, $now),
                'test' => $sitting === null ? null : ['id' => (int) $sitting->id, 'state' => $this->state($sitting, $now), 'deadline_at' => $sitting->deadline_at->toIso8601ZuluString()]];
        }
        $this->owner($actor, $learner);

        return ['learner_id' => $learner, 'server_time' => $now->toIso8601ZuluString(), 'tests' => $rows];
    }

    private function window(PracticeTestBlueprint $blueprint, CarbonImmutable $now): string
    {
        if ($blueprint->kind === TestKinds::QUICK_QUIZ) {
            return 'open';
        }
        if (! $blueprint->active || ! $now->isBefore($blueprint->closes_at)) {
            return 'closed';
        }

        return $now->isBefore($blueprint->opens_at) ? 'upcoming' : 'open';
    }

    /** The group's topics a learner may choose for a quick quiz, with how many questions each has ready for them. */
    public function quizTopics(User $actor, int $learner, int $groupId): array
    {
        $this->owner($actor, $learner);
        $group = app(PracticeAccess::class)->learner($actor, $learner, $groupId);
        $writable = app(ModuleGate::class)->writable('daily_practice');
        if ($writable) {
            // As the practice choices do: derived topic nodes (and library mirrors) are kept current while practice is writable.
            app(TopicGraph::class)->sync((int) $group->subject_id);
        }
        $pool = app(TestQuestionPool::class);
        $topics = $pool->topics($group);
        $candidates = $pool->candidates($group, array_keys($topics), $this->readable($actor, $learner));
        $rows = [];
        foreach ($topics as $topic) {
            $rows[] = $topic + ['available' => count($candidates[$topic['id']] ?? [])];
        }
        app(PracticeAccess::class)->learner($actor, $learner, $groupId);
        $limits = TestKinds::limits(TestKinds::QUICK_QUIZ);

        return ['learner_id' => $learner, 'group_id' => $groupId, 'topics' => $rows, 'topics_max' => $limits['topics'][1],
            'questions_min' => $limits['questions'][0], 'questions_max' => $limits['questions'][1], 'writable' => $writable];
    }

    /** A short quiz on one to three of the group's topics, started at once. */
    public function quickQuiz(User $actor, int $learner, int $groupId, array $input): array
    {
        $limits = TestKinds::limits(TestKinds::QUICK_QUIZ);
        $data = Validator::make($input, ['operation_id' => ['required', 'uuid'], 'topic_ids' => ['required', 'array', 'min:'.$limits['topics'][0], 'max:'.$limits['topics'][1]],
            'topic_ids.*' => ['required', 'integer', 'min:1', 'distinct'], 'question_count' => ['required', 'integer', 'between:'.$limits['questions'][0].','.$limits['questions'][1]]])->validate();
        $data['topic_ids'] = array_map(intval(...), $data['topic_ids']);
        sort($data['topic_ids']);
        $authorize = function () use ($actor, $learner, $groupId) {
            $this->owner($actor, $learner, true);
            app(PracticeAccess::class)->learner($actor, $learner, $groupId, true);
        };
        $receipt = app(PracticeOperations::class)->run($actor, $data + ['learner_id' => $learner, 'group_id' => $groupId, 'kind' => TestKinds::QUICK_QUIZ], $authorize,
            function () use ($actor, $learner, $groupId, $data) {
                $group = app(PracticeAccess::class)->learner($actor, $learner, $groupId, true);
                $now = CarbonImmutable::now('UTC');
                $open = PracticeTest::where('learner_id', $learner)->where('teaching_group_id', $groupId)->whereNull('submitted_at')
                    ->where('deadline_at', '>', $now->subSeconds(self::GRACE_SECONDS))->whereHas('blueprint', fn ($q) => $q->where('kind', TestKinds::QUICK_QUIZ))->exists();
                abort_if($open, 409, __('You have a quick quiz in progress. Finish it or hand it in first.'));
                $day = $now->setTimezone(app(CurrentSchool::class)->school()->timezone)->startOfDay()->utc();
                $today = PracticeTest::where('learner_id', $learner)->where('started_at', '>=', $day)->whereHas('blueprint', fn ($q) => $q->where('kind', TestKinds::QUICK_QUIZ))->count();
                if ($today >= self::QUICK_QUIZZES_PER_DAY) {
                    throw ValidationException::withMessages(['quick_quiz' => __('That is plenty of quizzes for today. Come back tomorrow for more.')]);
                }
                app(TopicGraph::class)->sync((int) $group->subject_id);
                $pool = app(TestQuestionPool::class);
                abort_if(array_diff($data['topic_ids'], array_keys($pool->topics($group))) !== [], 404);
                $candidates = $pool->candidates($group, $data['topic_ids'], $this->readable($actor, $learner));
                $allocation = app(TestTopicShares::class)->allocate((int) $data['question_count'], array_fill_keys($data['topic_ids'], 1), array_map('count', $candidates));
                if (array_sum($allocation) === 0) {
                    throw ValidationException::withMessages(['topic_ids' => __('There are no questions ready for these topics yet. Try another topic.')]);
                }
                $blueprint = PracticeTestBlueprint::create(['teaching_group_id' => $group->id, 'subject_id' => $group->subject_id, 'grade_level_id' => $group->grade_level_id,
                    'session_year_id' => $group->session_year_id, 'kind' => TestKinds::QUICK_QUIZ, 'created_by' => $actor->id, 'learner_id' => $learner, 'title' => null,
                    'topic_ids' => $data['topic_ids'], 'weighting' => 'even', 'paper_version_ids' => null,
                    'topic_shares' => array_values(array_map(fn ($topic, $questions) => ['topic_id' => (int) $topic, 'weight' => 1, 'questions' => (int) $questions], array_keys($allocation), $allocation)),
                    'question_count' => array_sum($allocation), 'time_limit_seconds' => null, 'opens_at' => null, 'closes_at' => null, 'active' => true, 'revision' => 1]);

                return ['test_id' => $this->sit($actor, $learner, $group, $blueprint, $allocation, $candidates)];
            });

        return $this->read($actor, $learner, $receipt['test_id']);
    }

    /** Start (or resume) a cycle test the learner's teacher set. */
    public function start(User $actor, int $learner, int $blueprintId, array $input): array
    {
        $data = Validator::make($input, ['operation_id' => ['required', 'uuid']])->validate();
        $authorize = function () use ($actor, $learner, $blueprintId) {
            $this->owner($actor, $learner, true);
            $blueprint = PracticeTestBlueprint::where('kind', TestKinds::CYCLE_TEST)->findOrFail($blueprintId);
            app(PracticeAccess::class)->learner($actor, $learner, (int) $blueprint->teaching_group_id, true);
        };
        $receipt = app(PracticeOperations::class)->run($actor, $data + ['learner_id' => $learner, 'blueprint_id' => $blueprintId, 'action' => 'start'], $authorize,
            function () use ($actor, $learner, $blueprintId) {
                $blueprint = PracticeTestBlueprint::where('kind', TestKinds::CYCLE_TEST)->lockForUpdate()->findOrFail($blueprintId);
                $group = app(PracticeAccess::class)->learner($actor, $learner, (int) $blueprint->teaching_group_id, true);
                $existing = PracticeTest::where('practice_test_blueprint_id', $blueprint->id)->where('learner_id', $learner)->first();
                if ($existing !== null) {
                    return ['test_id' => (int) $existing->id];
                }
                $now = CarbonImmutable::now('UTC');
                abort_unless($blueprint->active, 404);
                abort_if($now->isBefore($blueprint->opens_at), 409, __('This test is not open yet. It opens at :time.', ['time' => $blueprint->opens_at->setTimezone(app(CurrentSchool::class)->school()->timezone)->format('Y-m-d H:i')]));
                abort_unless($now->isBefore($blueprint->closes_at), 409, __('This test has closed.'));
                app(TopicGraph::class)->sync((int) $group->subject_id);
                $topics = array_column($blueprint->topic_shares, 'topic_id');
                $candidates = app(TestQuestionPool::class)->candidates($group, $topics, $this->readable($actor, $learner));
                $weights = array_column($blueprint->topic_shares, 'questions', 'topic_id');
                $allocation = app(TestTopicShares::class)->allocate((int) $blueprint->question_count, $weights, array_map('count', $candidates));
                abort_if(array_sum($allocation) === 0, 409, __('This test has no questions ready for you yet. Please tell your teacher.'));

                return ['test_id' => $this->sit($actor, $learner, $group, $blueprint, $allocation, $candidates)];
            });

        return $this->read($actor, $learner, $receipt['test_id']);
    }

    /** @return list<int> */
    private function readable(User $actor, int $learner): array
    {
        return app(ContentPolicy::class)->readableVersions($actor, $learner)->limit(5001)->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    /**
     * Choose the questions per topic and their order. The choice is stable
     * per learner and test, so classmates usually get different questions
     * and order from the same blueprint.
     */
    private function sit(User $actor, int $learner, TeachingGroup $group, PracticeTestBlueprint $blueprint, array $allocation, array $candidates): int
    {
        $salt = $blueprint->id.'|'.$learner;
        $chosen = [];
        foreach ($allocation as $topic => $count) {
            $options = $candidates[$topic] ?? [];
            usort($options, fn ($a, $b) => hash('sha256', $salt.'|'.$a['key']) <=> hash('sha256', $salt.'|'.$b['key']));
            foreach (array_slice($options, 0, $count) as $option) {
                $chosen[] = $option + ['topic_id' => (int) $topic];
            }
        }
        usort($chosen, fn ($a, $b) => hash('sha256', $salt.'|order|'.$a['key']) <=> hash('sha256', $salt.'|order|'.$b['key']));
        $seconds = $blueprint->time_limit_seconds ?? max(120, array_sum(array_column($chosen, 'seconds')));
        $now = CarbonImmutable::now('UTC')->startOfSecond();
        $deadline = $now->addSeconds($seconds);
        if ($blueprint->closes_at !== null && $deadline->isAfter($blueprint->closes_at)) {
            $deadline = $blueprint->closes_at;
        }
        $test = PracticeTest::create(['practice_test_blueprint_id' => $blueprint->id, 'learner_id' => $learner, 'user_id' => $actor->id, 'teaching_group_id' => $group->id,
            'time_limit_seconds' => $seconds, 'started_at' => $now, 'deadline_at' => $deadline]);
        foreach ($chosen as $i => $question) {
            PracticeTestQuestion::create(['practice_test_id' => $test->id, 'position' => $i + 1, 'topic_id' => $question['topic_id']]
                + ($question['source'] === 'library' ? ['library_item_version_id' => $question['version']->id] : ['practice_item_version_id' => $question['version']->id]));
        }

        return (int) $test->id;
    }

    /** @return array{0: PracticeTest, 1: TeachingGroup} */
    private function sitting(User $actor, int $learner, int $testId, bool $write = false): array
    {
        $this->owner($actor, $learner, $write);
        $test = PracticeTest::where('learner_id', $learner)->where('user_id', $actor->id)->findOrFail($testId);
        $group = app(PracticeAccess::class)->learner($actor, $learner, (int) $test->teaching_group_id, $write);

        return [$test, $group];
    }

    public function read(User $actor, int $learner, int $testId): array
    {
        $school = app(CurrentSchool::class)->id();
        [$test, $group] = $this->sitting($actor, $learner, $testId);
        $blueprint = PracticeTestBlueprint::findOrFail($test->practice_test_blueprint_id);
        $now = CarbonImmutable::now('UTC');
        $state = $this->state($test, $now);
        $memo = $state === 'handed_in' && ($blueprint->kind === TestKinds::QUICK_QUIZ || ! $blueprint->active || ! $now->isBefore($blueprint->closes_at));
        $questions = PracticeTestQuestion::where('practice_test_id', $test->id)->orderBy('position')->get();
        $definitions = $this->definitions($questions);
        $rows = $questions->map(function ($question) use ($definitions, $state, $memo) {
            $definition = $definitions[$question->id];
            // Before hand-in: the prompt and options only, never the accepted answers or the worked explanation.
            $row = ['id' => (int) $question->id, 'position' => $question->position, 'topic_id' => $question->topic_id, 'kind' => $definition['kind'],
                'prompt' => $definition['prompt'], 'options' => $definition['options'], 'answer' => $question->answer];
            if ($state === 'handed_in') {
                $row += ['marking' => $question->marking, 'correct' => $question->correct];
            }
            if ($memo) {
                $row += ['accepted_answers' => $definition['answers'], 'explanation' => $definition['explanation']];
            }

            return $row;
        })->all();
        $result = $state === 'handed_in' ? $this->result($actor, $learner, $group, $blueprint, $test, $questions, $memo) : null;
        $this->sitting($actor, $learner, $testId);
        abort_unless(app(CurrentSchool::class)->id() === $school, 403);

        return ['id' => (int) $test->id, 'learner_id' => $learner, 'group_id' => (int) $group->id, 'blueprint_id' => (int) $blueprint->id, 'kind' => $blueprint->kind,
            'title' => $blueprint->title, 'state' => $state, 'server_time' => $now->toIso8601ZuluString(), 'started_at' => $test->started_at->toIso8601ZuluString(),
            'deadline_at' => $test->deadline_at->toIso8601ZuluString(), 'seconds_left' => $state === 'open' ? max(0, $test->deadline_at->getTimestamp() - $now->getTimestamp()) : 0,
            'time_limit_seconds' => (int) $test->time_limit_seconds, 'submitted_at' => $test->submitted_at?->toIso8601ZuluString(), 'closed_by' => $test->closed_by,
            'questions' => $rows, 'result' => $result];
    }

    /** Save answers while the sitting is open; nothing is marked or revealed. */
    public function save(User $actor, int $learner, int $testId, array $input): array
    {
        $data = Validator::make($input, self::answerRules(true))->validate();

        return DB::transaction(function () use ($actor, $learner, $testId, $data) {
            [$test] = $this->sitting($actor, $learner, $testId, true);
            $test = PracticeTest::lockForUpdate()->findOrFail($test->id);
            $now = CarbonImmutable::now('UTC');
            abort_if($test->submitted_at !== null, 409, __('This test was already handed in.'));
            abort_if($this->state($test, $now) === 'time_up', 409, __('Time is up, so answers can no longer be changed. Hand the test in to see how you did.'));
            $saved = $this->apply($test, $data['answers'], $now);

            return ['id' => (int) $test->id, 'saved' => $saved, 'server_time' => $now->toIso8601ZuluString(), 'deadline_at' => $test->deadline_at->toIso8601ZuluString()];
        });
    }

    /** @return array<string, list<mixed>> */
    public static function answerRules(bool $required): array
    {
        return ['answers' => [$required ? 'required' : 'sometimes', 'array', 'max:40'], 'answers.*' => ['required', 'array:question_id,answer'],
            'answers.*.question_id' => ['required', 'integer', 'min:1', 'distinct'], 'answers.*.answer' => ['present', 'nullable', 'string', 'max:4000']];
    }

    /** Hand the sitting in and mark it. Late: only the answers saved in time count. */
    public function submit(User $actor, int $learner, int $testId, array $input): array
    {
        $data = Validator::make($input, ['operation_id' => ['required', 'uuid']] + self::answerRules(false))->validate();
        $authorize = fn () => $this->sitting($actor, $learner, $testId, true);
        $receipt = app(PracticeOperations::class)->run($actor, $data + ['learner_id' => $learner, 'test_id' => $testId, 'action' => 'submit'], $authorize,
            function () use ($actor, $learner, $testId, $data) {
                [$test, $group] = $this->sitting($actor, $learner, $testId, true);
                $test = PracticeTest::lockForUpdate()->findOrFail($test->id);
                abort_if($test->submitted_at !== null, 409, __('This test was already handed in. Open it to see how you did.'));
                $now = CarbonImmutable::now('UTC');
                $inTime = $this->state($test, $now) === 'open';
                if ($inTime) {
                    $this->apply($test, $data['answers'] ?? [], $now);
                }
                $questions = PracticeTestQuestion::where('practice_test_id', $test->id)->lockForUpdate()->orderBy('position')->get();
                $definitions = $this->definitions($questions);
                $correct = 0;
                $marked = 0;
                foreach ($questions as $question) {
                    if ($question->answer === null) {
                        continue;
                    }
                    $definition = $definitions[$question->id];
                    $mark = app(TestMarkers::class)->for($definition['kind'])->mark($definition, $question->answer);
                    $question->update(['marking' => $mark->marking, 'correct' => $mark->correct]);
                    if ($mark->correct !== null) {
                        $marked++;
                        $correct += $mark->correct ? 1 : 0;
                    }
                }
                $test->update(['submitted_at' => $now, 'closed_by' => $inTime ? 'learner' : 'time', 'correct_count' => $correct, 'marked_count' => $marked]);
                app(MasteryRecompute::class)->practiceTest($test, $group);

                return ['test_id' => (int) $test->id];
            });

        return $this->read($actor, $learner, $receipt['test_id']);
    }

    /** Store each answer after its marker has checked it can be read. */
    private function apply(PracticeTest $test, array $answers, CarbonImmutable $now): int
    {
        if ($answers === []) {
            return 0;
        }
        $questions = PracticeTestQuestion::where('practice_test_id', $test->id)->whereIn('id', array_column($answers, 'question_id'))->get()->keyBy('id');
        abort_unless($questions->count() === count($answers), 404);
        $definitions = $this->definitions($questions);
        foreach ($answers as $index => $row) {
            $question = $questions[(int) $row['question_id']];
            $answer = $row['answer'] === null ? null : trim($row['answer']);
            if ($answer === '') {
                $answer = null;
            }
            if ($answer !== null) {
                $definition = $definitions[$question->id];
                try {
                    app(TestMarkers::class)->for($definition['kind'])->check($definition, $answer);
                } catch (InvalidArgumentException $error) {
                    throw ValidationException::withMessages(["answers.$index.answer" => $error->getMessage()]);
                }
            }
            $question->update(['answer' => $answer, 'answered_at' => $answer === null ? null : $now]);
        }

        return count($answers);
    }

    /**
     * The immutable approved definitions behind a sitting's questions.
     *
     * @return array<int, array{kind: string, prompt: string, explanation: string, options: list<array{id: string, text: string}>, answers: list<string>}>
     */
    private function definitions($questions): array
    {
        $school = PracticeItemVersion::whereIn('id', $questions->pluck('practice_item_version_id')->filter())->get()->keyBy('id');
        $library = LibraryItemVersion::whereIn('id', $questions->pluck('library_item_version_id')->filter())->get()->keyBy('id');
        $definitions = [];
        foreach ($questions as $question) {
            $definition = $question->library_item_version_id !== null ? $library[$question->library_item_version_id]->practiceDefinition() : $school[$question->practice_item_version_id]->definition;
            $definitions[$question->id] = ['kind' => $definition['kind'], 'prompt' => $definition['prompt'], 'explanation' => $definition['explanation'],
                'options' => array_values($definition['options'] ?? []), 'answers' => array_values($definition['answers'])];
        }

        return $definitions;
    }

    /**
     * How it went, per topic, and what to look at next: the topics with the
     * most to practise first, and where the tutor can pick up a review.
     * Unanswered questions are shown as not answered, not as wrong, and are
     * not mastery evidence.
     */
    private function result(User $actor, int $learner, TeachingGroup $group, PracticeTestBlueprint $blueprint, PracticeTest $test, $questions, bool $memo): array
    {
        $names = app(TestQuestionPool::class)->topics($group);
        $topics = [];
        foreach ($questions as $question) {
            $topic = $topics[$question->topic_id] ??= ['topic_id' => $question->topic_id, 'name' => $names[$question->topic_id]['name'] ?? __('Earlier topic'),
                'correct' => 0, 'answered' => 0, 'total' => 0];
            $topic['total']++;
            if ($question->marking === 'auto') {
                $topic['answered']++;
                $topic['correct'] += $question->correct ? 1 : 0;
            }
            $topics[$question->topic_id] = $topic;
        }
        ksort($topics);
        $review = array_values(array_filter($topics, fn ($topic) => $topic['correct'] < $topic['total']));
        usort($review, fn ($a, $b) => [$a['correct'] * $b['total'], $a['topic_id']] <=> [$b['correct'] * $a['total'], $b['topic_id']]);
        $reviewIds = array_column($review, 'topic_id');

        return ['correct_count' => (int) $test->correct_count, 'marked_count' => (int) $test->marked_count, 'question_count' => $questions->count(),
            'unanswered_count' => $questions->whereNull('marking')->count(), 'awaiting_marker_count' => $questions->where('marking', 'awaiting_marker')->count(),
            'memo_available' => $memo, 'memo_available_at' => $blueprint->kind === TestKinds::QUICK_QUIZ ? $test->submitted_at?->toIso8601ZuluString() : $blueprint->closes_at?->toIso8601ZuluString(),
            'topics' => array_values($topics), 'review' => ['topic_ids' => $reviewIds, 'tutor' => $this->tutor($actor, $learner, $group, $reviewIds, $names, $questions)]];
    }

    /**
     * The inputs for opening a tutor session on the first topic to review
     * (POST /tutor/sessions takes the group, topic and up to three source
     * versions): the published material behind the school questions there,
     * missed ones first. Library topics and a school without the tutor get
     * no hand-over.
     */
    private function tutor(User $actor, int $learner, TeachingGroup $group, array $reviewIds, array $names, $questions): ?array
    {
        $topic = $reviewIds[0] ?? null;
        $gate = app(ModuleGate::class);
        $gate->forget();
        if ($topic === null || ($names[$topic]['source'] ?? null) !== 'school' || ! $gate->allows('tutor')) {
            return null;
        }
        $inTopic = $questions->where('topic_id', $topic)->whereNotNull('practice_item_version_id')->sortBy(fn ($q) => [$q->correct === true ? 1 : 0, $q->position]);
        $versions = PracticeItemVersion::whereIn('id', $inTopic->pluck('practice_item_version_id'))->pluck('content_version_id', 'id');
        $readable = $this->readable($actor, $learner);
        $sources = [];
        foreach ($inTopic as $question) {
            $source = (int) $versions[$question->practice_item_version_id];
            if (in_array($source, $readable, true) && ! in_array($source, $sources, true)) {
                $sources[] = $source;
            }
        }
        $sources = array_slice($sources, 0, 3);
        sort($sources);

        return $sources === [] ? null : ['group_id' => (int) $group->id, 'topic_id' => (int) $topic, 'source_version_ids' => $sources];
    }
}
