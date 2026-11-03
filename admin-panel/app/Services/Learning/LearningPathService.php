<?php

declare(strict_types=1);

namespace App\Services\Learning;

use App\Models\Academics\TeachingGroup;
use App\Models\Growth\MasteryTopic;
use App\Models\Growth\TopicLink;
use App\Models\Learning\LearningPath;
use App\Models\Learning\LearningPathItem;
use App\Models\Learning\LearningPathReview;
use App\Models\Learning\PracticeMemory;
use App\Models\User;
use App\Services\Academics\AcademicAuthority;
use App\Services\Growth\MasteryRecompute;
use App\Services\Growth\MasteryService;
use App\Services\Growth\TopicGraph;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * The guided learning path (Action Plan 3.9): find the gap, teach, practise,
 * check, review later. One path per learner and learning group; only this
 * service moves it, and every step is decided on the server:
 *
 *   gap ──next──▶ teach ──practise──▶ practise ──check──▶ check
 *    ▲              ▲                                       │ all answered
 *    │              └──────── not yet (fewer than 3 tries) ─┤
 *    ├──────── not yet (3rd try): rest the topic a day ─────┤
 *    └──next── review (FSRS date) ◀──────── passed ─────────┘
 *
 * A due review starts straight at check. Questions come from the school's
 * approved practice for the topic, then the library; approved answers never
 * leave the server, and the learner sees the explanation only after answering.
 * engineering/specs/learning/learning-path.md.
 */
class LearningPathService
{
    public const PRACTICE_SIZE = 5;

    public const CHECK_SIZE = 3;

    /** Failed checks on one topic before the path rests it until tomorrow. */
    public const MAX_CHECKS = 3;

    public const ACTIONS = ['next', 'open_tutor', 'practise', 'check'];

    /** The learner themself, approved and enrolled, with daily practice and mastery on for them. */
    public function access(User $actor, int $learner, int $groupId, bool $write = false): TeachingGroup
    {
        $group = app(PracticeAccess::class)->learner($actor, $learner, $groupId, $write);
        $gate = app(ModuleGate::class);
        $gate->forget();
        abort_unless(($write ? $gate->writable('mastery') : $gate->allows('mastery')) && $gate->allowsStageOf('daily_practice', $actor), 403, __('The learning path is not available.'));

        return $group;
    }

    public function read(User $actor, int $learner, int $groupId): array
    {
        $school = app(CurrentSchool::class)->id();
        $group = $this->access($actor, $learner, $groupId);
        $path = LearningPath::where('learner_id', $learner)->where('teaching_group_id', $groupId)->first();
        $data = $this->present($actor, $learner, $group, $path);
        $this->access($actor, $learner, $groupId);
        abort_unless(app(CurrentSchool::class)->id() === $school, 403);

        return $data;
    }

    public function act(User $actor, int $learner, int $groupId, array $input): array
    {
        $data = Validator::make($input, ['operation_id' => ['required', 'uuid'], 'expected_revision' => ['required', 'integer', 'min:0'],
            'action' => ['required', 'string', Rule::in(self::ACTIONS)]])->validate();
        $authorize = fn () => $this->access($actor, $learner, $groupId, true);
        app(PracticeOperations::class)->run($actor, $data + ['learner_id' => $learner, 'group_id' => $groupId, 'kind' => 'learning_path_action'], $authorize,
            function () use ($actor, $learner, $groupId, $data) {
                $group = $this->access($actor, $learner, $groupId, true);
                $path = LearningPath::where('learner_id', $learner)->where('teaching_group_id', $groupId)->lockForUpdate()->first();
                abort_unless((int) ($path?->revision ?? 0) === (int) $data['expected_revision'], 409, __('Your learning path has moved on. Reload it first.'));
                $path = match ($data['action']) {
                    'next' => $this->next($actor, $learner, $group, $path),
                    'open_tutor' => $this->openTutor($actor, $learner, $group, $this->current($path, 'teach'), $data['operation_id']),
                    'practise' => $this->practise($actor, $learner, $group, $this->current($path, 'teach')),
                    'check' => $this->startCheck($actor, $learner, $group, $this->current($path, 'practise')),
                };

                // Replay keeps only the path identity; every read is authorized afresh.
                return ['path_id' => (int) $path->id];
            });

        return $this->read($actor, $learner, $groupId);
    }

    public function answer(User $actor, int $learner, int $groupId, int $itemId, array $input): array
    {
        $data = Validator::make($input, ['operation_id' => ['required', 'uuid'], 'answer' => ['required', 'string', 'max:100']])->validate();
        $authorize = fn () => $this->access($actor, $learner, $groupId, true);
        app(PracticeOperations::class)->run($actor, $data + ['learner_id' => $learner, 'group_id' => $groupId, 'item_id' => $itemId, 'kind' => 'learning_path_answer'], $authorize,
            function () use ($actor, $learner, $groupId, $itemId, $data) {
                $group = $this->access($actor, $learner, $groupId, true);
                $path = LearningPath::where('learner_id', $learner)->where('teaching_group_id', $groupId)->lockForUpdate()->firstOrFail();
                $row = LearningPathItem::where('learning_path_id', $path->id)->where('learner_id', $learner)->lockForUpdate()->findOrFail($itemId);
                abort_unless(in_array($path->step, ['practise', 'check'], true) && $row->cycle === $path->cycle && $row->topic_id === $path->topic_id
                    && $row->purpose === ($path->step === 'check' ? 'check' : 'practice'), 409, __('This question is not part of the step you are on.'));
                abort_if($row->answered_at !== null, 409, __('This question was already answered. Reload to see your result.'));
                $topic = $this->topic($group, $path);
                $question = app(LearningPathSources::class)->question($actor, $learner, $group, $topic, $row);
                abort_if($question === null, 404);
                try {
                    $correct = app(PracticeAnswers::class)->correct($question['definition'], trim($data['answer']));
                } catch (InvalidArgumentException $error) {
                    throw ValidationException::withMessages(['answer' => $error->getMessage()]);
                }
                $today = app(AcademicAuthority::class)->today();
                // The question's own FSRS memory moves exactly as in daily practice, so daily sets see it too.
                $memory = PracticeMemory::where('learner_id', $learner)->where($question['memory'])->lockForUpdate()->first();
                $schedule = app(FsrsScheduler::class)->review($memory?->state, $correct ? 3 : 1, $memory === null ? 0 : $this->days($memory->reviewed_on->toDateString(), $today));
                PracticeMemory::updateOrCreate(['learner_id' => $learner] + $question['memory'],
                    ['state' => $schedule, 'reviewed_on' => $today, 'due_on' => CarbonImmutable::parse($today)->addDays($schedule['interval_days'])->toDateString()]);
                $row->update(['answer_operation_id' => $data['operation_id'], 'answer' => trim($data['answer']), 'correct' => $correct, 'schedule' => $schedule, 'answered_at' => now('UTC')]);
                app(MasteryRecompute::class)->learningPath($row, $group);
                if ($row->purpose === 'check') {
                    $this->evaluate($actor, $learner, $group, $path, $topic);
                }
                $path->revision++;
                $path->save();

                return ['path_id' => (int) $path->id];
            });

        return $this->read($actor, $learner, $groupId);
    }

    private function current(?LearningPath $path, string $step): LearningPath
    {
        abort_unless($path !== null && $path->step === $step, 409, __('That is not the next step on your learning path.'));

        return $path;
    }

    /** The path's topic, still taught in the group now; otherwise the learner chooses their next step. */
    private function topic(TeachingGroup $group, LearningPath $path): MasteryTopic
    {
        $topic = $path->topic_id === null ? null : app(LearningPathSources::class)->teachable($group)->get($path->topic_id);
        abort_if($topic === null, 409, __('This topic changed at school. Choose your next step to carry on.'));

        return $topic;
    }

    /** Find the gap: a due review first, then the weakest prerequisite gap the group teaches (MasteryService::roots). */
    private function next(User $actor, int $learner, TeachingGroup $group, ?LearningPath $path): LearningPath
    {
        $sources = app(LearningPathSources::class);
        app(TopicGraph::class)->sync((int) $group->subject_id);
        $teachable = $sources->teachable($group);
        if ($path !== null) {
            abort_unless(in_array($path->step, ['gap', 'review'], true) || $this->stalled($actor, $learner, $group, $path, $teachable), 409, __('Finish the step you are on first.'));
        }
        $path ??= new LearningPath(['learner_id' => $learner, 'user_id' => $actor->id, 'teaching_group_id' => $group->id, 'subject_id' => $group->subject_id,
            'session_year_id' => $group->session_year_id, 'revision' => 0, 'cycle' => 0, 'failed_checks' => 0]);
        $today = app(AcademicAuthority::class)->today();
        $practisable = $sources->practisable($group, $teachable);
        $reviews = $path->exists ? LearningPathReview::where('learning_path_id', $path->id)->whereIn('topic_id', $practisable)->get() : collect();
        $fresh = ['failed_checks' => 0, 'notice' => null, 'tutor_session_id' => null];
        $due = $reviews->filter(fn ($review) => $review->due_on->toDateString() <= $today)
            ->sort(fn ($a, $b) => [$a->due_on->toDateString(), $a->id] <=> [$b->due_on->toDateString(), $b->id])->first();
        if ($due !== null) {
            $path->fill(['topic_id' => $due->topic_id, 'step' => 'check', 'mode' => 'review', 'reason' => 'review_due', 'cycle' => $path->cycle + 1] + $fresh);
            $path->revision++;
            $path->save();
            $this->issue($actor, $learner, $group, $path, $teachable->get($due->topic_id), 'check');

            return $path;
        }
        $excluded = $reviews->pluck('topic_id')->map(fn ($id) => (int) $id)->all();
        $resting = $path->rest_topic_id !== null && $path->rest_until !== null && $path->rest_until->toDateString() > $today ? $path->rest_topic_id : null;
        if ($resting !== null) {
            $excluded[] = $resting;
        }
        $eligible = array_values(array_diff($practisable, $excluded));
        $roots = [];
        if ($eligible !== []) {
            $view = app(MasteryService::class)->view($actor, $learner, (int) $group->subject_id);
            $topics = array_values(array_filter($view['topics'], fn ($topic) => in_array((int) $topic['id'], $eligible, true)));
            $links = TopicLink::query()->where('subject_id', $group->subject_id)->limit(10001)->get(['topic_id', 'prerequisite_id'])
                ->map(fn ($link) => ['topic_id' => (int) $link->topic_id, 'prerequisite_id' => (int) $link->prerequisite_id])->all();
            $roots = $topics === [] ? [] : app(MasteryService::class)->roots($topics, $links, array_column($topics, 'id'));
        }
        if ($roots === []) {
            $path->fill(['topic_id' => null, 'step' => 'gap', 'mode' => 'learn', 'reason' => null] + $fresh);
            $path->notice = $practisable === [] ? 'nothing_ready' : (in_array($resting, $practisable, true) ? 'rest' : 'caught_up');
        } else {
            $path->fill(['topic_id' => $roots[0]['topic_id'], 'step' => 'teach', 'mode' => 'learn', 'reason' => $roots[0]['reason']] + $fresh);
        }
        $path->revision++;
        $path->save();

        return $path;
    }

    private function openTutor(User $actor, int $learner, TeachingGroup $group, LearningPath $path, string $operation): LearningPath
    {
        $topic = $this->topic($group, $path);
        $tutor = $this->tutor($actor, $learner, $group, $path, $topic);
        abort_unless($tutor['available'], 409, __('The tutor is not available for this step. Use the lesson instead.'));
        if ($tutor['session_id'] === null) {
            $material = array_keys(app(LearningPathSources::class)->schoolMaterial($actor, $learner, $group, $topic));
            $session = app(TutorSessions::class)->create($actor, (int) $group->id, $material, $operation, (int) $topic->id);
            $path->tutor_session_id = $session['id'];
            $path->revision++;
            $path->save();
        }

        return $path;
    }

    private function practise(User $actor, int $learner, TeachingGroup $group, LearningPath $path): LearningPath
    {
        $topic = $this->topic($group, $path);
        $path->cycle++;
        $this->issue($actor, $learner, $group, $path, $topic, 'practice');
        $path->fill(['step' => 'practise', 'notice' => null]);
        $path->revision++;
        $path->save();

        return $path;
    }

    private function startCheck(User $actor, int $learner, TeachingGroup $group, LearningPath $path): LearningPath
    {
        $topic = $this->topic($group, $path);
        $rows = $this->available($actor, $learner, $group, $path, $topic, 'practice');
        abort_if($rows === [] || collect($rows)->contains(fn ($row) => $row[0]->answered_at === null), 409, __('Answer the practice questions first.'));
        $this->issue($actor, $learner, $group, $path, $topic, 'check');
        $path->fill(['step' => 'check', 'notice' => null]);
        $path->revision++;
        $path->save();

        return $path;
    }

    /** Give the learner this cycle's questions: practice first from the top of the bank, a check from questions they have not just practised. */
    private function issue(User $actor, int $learner, TeachingGroup $group, LearningPath $path, ?MasteryTopic $topic, string $purpose): void
    {
        abort_if($topic === null, 409, __('This topic changed at school. Choose your next step to carry on.'));
        $sources = app(LearningPathSources::class);
        $candidates = $sources->candidates($actor, $learner, $group, $topic);
        abort_if($candidates === [], 409, __('Your teacher has not approved practice for this topic yet.'));
        if ($purpose === 'check') {
            $issued = LearningPathItem::where('learning_path_id', $path->id)->where('topic_id', $topic->id)->limit(500)->get();
            $practised = $issued->where('cycle', $path->cycle)->where('purpose', 'practice')->map(fn ($row) => $sources->key($row))->all();
            $checked = $issued->where('purpose', 'check')->map(fn ($row) => $sources->key($row))->all();
            $unseen = array_filter($candidates, fn ($c) => ! in_array($c['key'], $practised, true) && ! in_array($c['key'], $checked, true));
            $unpractised = array_filter($candidates, fn ($c) => ! in_array($c['key'], $practised, true));
            $chosen = collect([...$unseen, ...$unpractised, ...$candidates])->unique('key')->take(self::CHECK_SIZE)->values()->all();
        } else {
            $chosen = array_slice($candidates, 0, self::PRACTICE_SIZE);
        }
        foreach ($chosen as $index => $candidate) {
            $library = $candidate['key'][0] === 'l';
            LearningPathItem::create(['learning_path_id' => $path->id, 'learner_id' => $learner, 'topic_id' => $topic->id, 'cycle' => $path->cycle,
                'purpose' => $purpose, 'position' => $index + 1, 'practice_item_version_id' => $library ? null : $candidate['version']->id,
                'library_item_version_id' => $library ? $candidate['version']->id : null]);
        }
    }

    /**
     * This cycle's items of one purpose whose question is still approved and
     * readable, with the question.
     *
     * @return list<array{0: LearningPathItem, 1: array}>
     */
    private function available(User $actor, int $learner, TeachingGroup $group, LearningPath $path, MasteryTopic $topic, string $purpose): array
    {
        $rows = [];
        foreach (LearningPathItem::where('learning_path_id', $path->id)->where('cycle', $path->cycle)->where('purpose', $purpose)->orderBy('position')->limit(20)->get() as $row) {
            $question = app(LearningPathSources::class)->question($actor, $learner, $group, $topic, $row);
            if ($question !== null) {
                $rows[] = [$row, $question];
            }
        }

        return $rows;
    }

    /** Server-graded check: pass with two thirds right; passing schedules the topic's review with FSRS. */
    private function evaluate(User $actor, int $learner, TeachingGroup $group, LearningPath $path, MasteryTopic $topic): void
    {
        $rows = array_column($this->available($actor, $learner, $group, $path, $topic, 'check'), 0);
        if ($rows === [] || collect($rows)->contains(fn ($row) => $row->answered_at === null)) {
            return;
        }
        $total = count($rows);
        $correct = count(array_filter($rows, fn ($row) => $row->correct));
        $today = app(AcademicAuthority::class)->today();
        $review = LearningPathReview::where('learning_path_id', $path->id)->where('topic_id', $topic->id)->lockForUpdate()->first();
        $elapsed = $review === null ? 0 : $this->days($review->reviewed_on->toDateString(), $today);
        $passed = $correct >= self::needed($total);
        if ($passed || $review !== null) {
            // A pass is good (all right) or hard (some wrong); a failed review is a lapse.
            $schedule = app(FsrsScheduler::class)->review($review?->state, $passed ? ($correct === $total ? 3 : 2) : 1, $elapsed);
            LearningPathReview::updateOrCreate(['learning_path_id' => $path->id, 'topic_id' => $topic->id],
                ['state' => $schedule, 'reviewed_on' => $today, 'due_on' => CarbonImmutable::parse($today)->addDays($schedule['interval_days'])->toDateString()]);
        }
        if ($passed) {
            $path->fill(['step' => 'review', 'notice' => 'passed', 'failed_checks' => 0, 'rest_topic_id' => null, 'rest_until' => null]);

            return;
        }
        $failed = $path->failed_checks + 1;
        if ($failed >= self::MAX_CHECKS) {
            $path->fill(['step' => 'gap', 'topic_id' => null, 'mode' => 'learn', 'reason' => null, 'notice' => 'rest', 'failed_checks' => $failed,
                'rest_topic_id' => $topic->id, 'rest_until' => CarbonImmutable::parse($today)->addDay()->toDateString(), 'tutor_session_id' => null]);

            return;
        }
        $path->fill(['step' => 'teach', 'mode' => 'learn', 'notice' => 'not_yet', 'failed_checks' => $failed]);
    }

    /**
     * A path that cannot go on where it is: its topic is no longer taught in
     * the group, or every question of its current step was withdrawn (or, in a
     * check, every one left is answered). The learner may then choose again.
     */
    private function stalled(User $actor, int $learner, TeachingGroup $group, LearningPath $path, Collection $teachable): bool
    {
        if ($path->topic_id === null || ! in_array($path->step, ['teach', 'practise', 'check'], true)) {
            return false;
        }
        $topic = $teachable->get($path->topic_id);
        if ($topic === null) {
            return true;
        }
        if ($path->step === 'teach') {
            return false;
        }
        $rows = $this->available($actor, $learner, $group, $path, $topic, $path->step === 'check' ? 'check' : 'practice');

        return $rows === [] || ($path->step === 'check' && ! collect($rows)->contains(fn ($row) => $row[0]->answered_at === null));
    }

    public static function needed(int $total): int
    {
        return max(1, (int) ceil($total * 2 / 3));
    }

    private function days(string $from, string $to): int
    {
        return max(0, (int) CarbonImmutable::parse($from, 'UTC')->diffInDays(CarbonImmutable::parse($to, 'UTC')));
    }

    /**
     * Whether the tutor may lead the teach step: the AI is switched on, the
     * tutor module and the learner's stage allow it, the learner may use the
     * tutor in this group, and the school has material on the topic for it.
     */
    private function tutor(User $actor, int $learner, TeachingGroup $group, LearningPath $path, MasteryTopic $topic): array
    {
        $off = ['available' => false, 'session_id' => null];
        if (config('ai.inference.enabled') !== true || config('ai.inference.features.tutor.enabled') !== true) {
            return $off + ['reason' => 'not_ready'];
        }
        try {
            abort_unless(app(ModuleGate::class)->allowsStageOf('tutor', $actor), 403);
            app(TutorAccess::class)->group($actor, (int) $group->id);
        } catch (HttpExceptionInterface|ModelNotFoundException) {
            return $off + ['reason' => 'not_available'];
        }
        if (app(LearningPathSources::class)->schoolMaterial($actor, $learner, $group, $topic) === []) {
            return $off + ['reason' => 'no_school_material'];
        }
        $session = null;
        if ($path->tutor_session_id !== null) {
            try {
                $row = app(TutorAccess::class)->session($actor, $path->tutor_session_id);
                $session = $row->state === 'open' && (int) $row->topic_id === (int) $topic->id ? (string) $row->id : null;
            } catch (HttpExceptionInterface|ModelNotFoundException) {
                $session = null;
            }
        }

        return ['available' => true, 'reason' => null, 'session_id' => $session];
    }

    private function present(User $actor, int $learner, TeachingGroup $group, ?LearningPath $path): array
    {
        $sources = app(LearningPathSources::class);
        $teachable = $sources->teachable($group);
        $topic = $path?->topic_id === null ? null : $teachable->get($path->topic_id);
        $withdrawn = $path !== null && $this->stalled($actor, $learner, $group, $path, $teachable);
        $step = $path === null ? 'gap' : $path->step;
        $items = [];
        $teach = null;
        if ($path !== null && $topic !== null && ! $withdrawn && in_array($step, ['practise', 'check'], true)) {
            foreach ($this->available($actor, $learner, $group, $path, $topic, $step === 'check' ? 'check' : 'practice') as [$row, $question]) {
                $items[] = $this->item($row, $question['definition']);
            }
        }
        if ($path !== null && $topic !== null && ! $withdrawn && $step === 'teach') {
            $teach = ['lessons' => $sources->lessons($actor, $learner, $group, $topic), 'tutor' => $this->tutor($actor, $learner, $group, $path, $topic)];
        }
        $reviews = $path === null ? collect() : LearningPathReview::where('learning_path_id', $path->id)->whereIn('topic_id', $teachable->keys())->orderBy('due_on')->orderBy('id')->limit(5)->get();
        $topicReview = $topic === null ? null : LearningPathReview::where('learning_path_id', $path->id)->where('topic_id', $topic->id)->first();
        $actions = [];
        if ($path === null || in_array($step, ['gap', 'review'], true) || $withdrawn) {
            $actions[] = 'next';
        } elseif ($step === 'teach') {
            $actions = $teach['tutor']['available'] && $teach['tutor']['session_id'] === null ? ['open_tutor', 'practise'] : ['practise'];
        } elseif ($items !== [] && collect($items)->contains(fn ($item) => ! $item['answered'])) {
            $actions[] = 'answer';
        } elseif ($step === 'practise' && $items !== []) {
            $actions[] = 'check';
        }
        $notice = $withdrawn ? 'withdrawn' : $path?->notice;
        $restTopic = $path?->rest_topic_id !== null && $path->rest_until !== null && $path->rest_until->toDateString() > app(AcademicAuthority::class)->today()
            ? $teachable->get($path->rest_topic_id) : null;

        return ['school_id' => app(CurrentSchool::class)->id(), 'reader_id' => (int) $actor->id, 'learner_id' => $learner, 'group_id' => (int) $group->id,
            'subject_id' => (int) $group->subject_id, 'path_id' => $path === null ? null : (int) $path->id, 'revision' => (int) ($path?->revision ?? 0),
            'step' => $withdrawn ? 'gap' : $step, 'mode' => $path === null ? 'learn' : $path->mode, 'reason' => $path?->reason,
            'topic' => $topic === null ? null : ['id' => (int) $topic->id, 'name' => $topic->name],
            'attempt' => $path === null ? 0 : $path->failed_checks + 1, 'max_attempts' => self::MAX_CHECKS,
            'notice' => $notice, 'message' => $this->message($notice, $topicReview?->due_on?->toDateString(), $restTopic?->name),
            'teach' => $teach, 'items' => $items, 'check' => $this->lastCheck($path, $step), 'review_due_on' => $step === 'review' ? $topicReview?->due_on?->toDateString() : null,
            'upcoming_reviews' => $reviews->map(fn ($review) => ['topic_id' => (int) $review->topic_id, 'name' => (string) $teachable->get($review->topic_id)?->name,
                'due_on' => $review->due_on->toDateString()])->all(),
            'actions' => $actions, 'server_time' => now()->utc()->toISOString()];
    }

    /** The learner's own question: never the approved answer; the explanation once they have answered. */
    private function item(LearningPathItem $row, array $definition): array
    {
        $answered = $row->answered_at !== null;

        return ['id' => (int) $row->id, 'purpose' => $row->purpose, 'position' => $row->position,
            'question' => ['kind' => (string) ($definition['kind'] ?? ''), 'prompt' => (string) ($definition['prompt'] ?? ''), 'options' => array_values((array) ($definition['options'] ?? []))],
            'answered' => $answered, 'correct' => $answered ? (bool) $row->correct : null, 'explanation' => $answered ? (string) ($definition['explanation'] ?? '') : null];
    }

    /** The result of the latest check once it is complete: how many were right, never which answer was expected. */
    private function lastCheck(?LearningPath $path, string $step): ?array
    {
        if ($path === null || $path->cycle === 0 || $step === 'check' || $step === 'practise') {
            return null;
        }
        $rows = LearningPathItem::where('learning_path_id', $path->id)->where('cycle', $path->cycle)->where('purpose', 'check')->get(['answered_at', 'correct']);
        if ($rows->isEmpty() || $rows->contains(fn ($row) => $row->answered_at === null)) {
            return null;
        }
        $correct = $rows->where('correct', true)->count();

        return ['correct' => $correct, 'total' => $rows->count(), 'needed' => self::needed($rows->count()), 'passed' => $correct >= self::needed($rows->count())];
    }

    private function message(?string $notice, ?string $reviewOn, ?string $restTopic): ?string
    {
        return match ($notice) {
            'passed' => __('Well done! You showed you understand this. We will come back to it on :date to help it stick.', ['date' => (string) $reviewOn]),
            'not_yet' => __('Not quite yet, and that is fine. Let us look at it again, then try a few more questions.'),
            'rest' => __('You worked hard on :topic today. Let us rest it until tomorrow. You could also ask your teacher about it.', ['topic' => (string) $restTopic]),
            'caught_up' => __('You are up to date in this subject. Come back later for your next step.'),
            'nothing_ready' => __('Nothing is ready to learn here yet. Your teacher is still adding practice for this subject.'),
            'withdrawn' => __('This topic changed at school. Choose your next step to carry on.'),
            default => null,
        };
    }
}
