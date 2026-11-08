<?php

declare(strict_types=1);

namespace App\Services\Learning;

use App\Models\Academics\TeachingGroup;
use App\Models\Academics\TeachingGroupMembership;
use App\Models\Growth\MasteryTopic;
use App\Models\Learning\DiagnosticAttempt;
use App\Models\Learning\DiagnosticAttemptItem;
use App\Models\Library\LibraryItem;
use App\Models\Library\LibraryItemVersion;
use App\Models\Library\LibraryTopic;
use App\Models\Library\LibraryTopicPrerequisite;
use App\Models\Students;
use App\Models\User;
use App\Policies\Academics\ContentPolicy;
use App\Policies\Growth\MasteryPolicy;
use App\Services\Academics\AcademicAuthority;
use App\Services\Growth\MasteryRecompute;
use App\Services\Growth\TopicGraph;
use App\Services\Learning\Library\LibraryCatalogue;
use App\Services\Learning\Library\LibraryPractice;
use App\Services\Learning\Library\LibrarySchools;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Stages\SchoolStages;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

/**
 * The diagnostic start (Action Plan 3.7). A learner whose school has the
 * library switched on takes the library's published diagnostic for their
 * group's framework, subject and grade once a school year: a short check,
 * one question at a time, marked here on the server. Each answer becomes
 * mastery evidence of its own provenance (`diagnostic`) on the school's
 * mirror node of the question's library topic, so the learner starts with a
 * filled map. The device never receives an answer or an explanation, and the
 * learner is not told right or wrong: it is a starting point, not a test.
 *
 * Short by design: at most three questions per topic, and a topic stops as
 * soon as two answers agree (both right or both not yet); at most
 * MAX_QUESTIONS in all. Library rows are only read; nothing about a learner
 * is written to the library.
 *
 * Specification: engineering/specs/learning/diagnostic-start.md.
 */
class DiagnosticService
{
    public const MAX_QUESTIONS = 20;

    /** How a topic came out, from the diagnostic's own answers. */
    public const BANDS = ['secure' => 'Secure', 'developing' => 'Partly there', 'gap' => 'Start here', 'not_reached' => 'Not reached yet'];

    /**
     * The published library diagnostic for this group's framework, subject
     * code and grade, or null. The earliest published one is used, so every
     * learner of a group takes the same check.
     *
     * @return array{0: LibraryItem, 1: LibraryItemVersion}|null
     */
    public function forGroup(TeachingGroup $group): ?array
    {
        if (! app(LibrarySchools::class)->enabled()) {
            return null;
        }
        $scopes = app(LibraryPractice::class)->scopes($group);
        if ($scopes === []) {
            return null;
        }
        $items = LibraryItem::query()->where('kind', 'diagnostic')->whereNotNull('published_version_id')
            ->whereHas('topic', fn ($topic) => $topic->where('grade_level_id', $group->grade_level_id)->where(function ($any) use ($scopes) {
                foreach ($scopes as [$framework, $code]) {
                    $any->orWhere(fn ($q) => $q->where('subject_code', $code)->whereHas('framework', fn ($f) => $f->where('code', $framework)));
                }
            }))->orderBy('id')->limit(20)->get();
        foreach ($items as $item) {
            $version = LibraryItemVersion::query()->where('state', 'published')->where('library_item_id', $item->id)->find($item->published_version_id);
            if ($version !== null && is_array($version->content['item_ids'] ?? null)) {
                return [$item, $version];
            }
        }

        return null;
    }

    /**
     * What the learner, or a guardian, sees: for each current group with a
     * diagnostic, whether it is done and a plain starting-point message.
     * Never a score, a level or anything to compare.
     */
    public function summary(User $actor, int $learnerId): array
    {
        $actor = app(PracticeAccess::class)->actor($actor);
        $this->mastery(false);
        $learner = app(MasteryPolicy::class)->family($actor, $learnerId);
        $self = (int) $learner->user_id === (int) $actor->id;
        $rows = [];
        // Early-years learners do not use practice (4.1): nothing is offered to them or about them.
        if (app(LibrarySchools::class)->enabled() && SchoolStages::moduleAllows('daily_practice', app(SchoolStages::class)->forLearner($learner))) {
            $groups = TeachingGroup::query()->whereIn('id', app(ContentPolicy::class)->learnerGroups($actor, $learnerId))->whereHas('subject')->with('subject')
                ->orderBy('name')->orderBy('id')->limit(50)->get();
            foreach ($groups as $group) {
                $found = $this->forGroup($group);
                if ($found === null) {
                    continue;
                }
                $attempt = DiagnosticAttempt::query()->where('learner_id', $learnerId)->where('library_item_id', $found[0]->id)
                    ->where('session_year_id', $group->session_year_id)->first();
                $status = $this->status($attempt);
                $subject = (string) $group->subject->name;
                $rows[] = ['group_id' => (int) $group->id, 'subject_name' => $subject, 'status' => $status,
                    'attempt_id' => $self && $attempt !== null ? (int) $attempt->id : null, 'can_start' => $self && $attempt === null,
                    'message' => $this->message($status, $self, $subject),
                    'focus_topics' => $status === 'completed' ? $this->focus($attempt) : []];
            }
        }

        return ['learner_id' => $learnerId, 'diagnostics' => $rows];
    }

    /** Start the group's diagnostic, or resume the one already started this school year. */
    public function start(User $actor, int $learnerId, int $groupId): array
    {
        $group = $this->learnerGroup($actor, $learnerId, $groupId, true);
        $found = $this->forGroup($group);
        abort_if($found === null, 404, 'There is no starting check for this subject yet.');
        [$diagnostic, $version] = $found;
        $attemptId = DB::transaction(function () use ($actor, $learnerId, $group, $diagnostic, $version): int {
            Students::query()->whereKey($learnerId)->lockForUpdate()->firstOrFail();
            $existing = DiagnosticAttempt::query()->where('learner_id', $learnerId)->where('library_item_id', $diagnostic->id)
                ->where('session_year_id', $group->session_year_id)->first();
            if ($existing !== null) {
                abort_unless($existing->teaching_group_id === (int) $group->id, 409, 'This starting check was started in another class. Ask your teacher.');

                return (int) $existing->id;
            }
            $plan = $this->plan($version, LibraryTopic::query()->findOrFail($diagnostic->library_topic_id));
            abort_if($plan === [], 409, 'This starting check has no questions ready yet.');
            $attempt = DiagnosticAttempt::create(['learner_id' => $learnerId, 'teaching_group_id' => $group->id, 'subject_id' => $group->subject_id,
                'grade_level_id' => $group->grade_level_id, 'session_year_id' => $group->session_year_id, 'library_item_id' => $diagnostic->id,
                'library_item_version_id' => $version->id, 'started_by' => $actor->id]);
            $mirrors = [];
            foreach ($plan as $position => [$item, $itemVersion, $topic]) {
                $mirrors[$topic->id] ??= app(TopicGraph::class)->mirror((int) $group->subject_id, $topic);
                DiagnosticAttemptItem::create(['diagnostic_attempt_id' => $attempt->id, 'position' => $position + 1, 'library_item_id' => $item->id,
                    'library_item_version_id' => $itemVersion->id, 'library_topic_id' => $topic->id, 'topic_id' => $mirrors[$topic->id]->id,
                    'state' => DiagnosticAttemptItem::WAITING]);
            }

            return (int) $attempt->id;
        });

        return $this->read($actor, $learnerId, $attemptId);
    }

    /** The learner's own attempt: progress and the next question, without its answer or explanation. */
    public function read(User $actor, int $learnerId, int $attemptId): array
    {
        [$attempt, $group] = $this->attempt($actor, $learnerId, $attemptId, false);

        return $this->payload($attempt, $group);
    }

    /**
     * Answer the next question. Marked here; the result is kept for the
     * teacher and the mastery map and is not sent back. Sending the same
     * answer again changes nothing; a different one is refused.
     */
    public function answer(User $actor, int $learnerId, int $attemptId, int $itemId, array $input): array
    {
        $data = Validator::make($input, ['answer' => ['required', 'string', 'max:100']])->validate();
        [, $group] = $this->attempt($actor, $learnerId, $attemptId, true);
        DB::transaction(function () use ($learnerId, $attemptId, $itemId, $data, $group) {
            $attempt = DiagnosticAttempt::query()->where('learner_id', $learnerId)->lockForUpdate()->findOrFail($attemptId);
            $row = DiagnosticAttemptItem::query()->where('diagnostic_attempt_id', $attempt->id)->findOrFail($itemId);
            $definition = $this->definition($row);
            if ($row->state === DiagnosticAttemptItem::ANSWERED) {
                abort_unless($this->same($definition, (string) $row->answer, $data['answer']), 409, 'This question was already answered.');

                return;
            }
            abort_if($row->state === DiagnosticAttemptItem::SKIPPED, 409, 'This question is no longer needed. Carry on with the next one.');
            $next = DiagnosticAttemptItem::query()->where('diagnostic_attempt_id', $attempt->id)->where('state', DiagnosticAttemptItem::WAITING)->orderBy('position')->first();
            abort_unless($next !== null && (int) $next->id === (int) $row->id, 409, 'Answer the questions in order.');
            try {
                $correct = app(PracticeAnswers::class)->correct($definition, $data['answer']);
            } catch (InvalidArgumentException $error) {
                throw ValidationException::withMessages(['answer' => $error->getMessage()]);
            }
            $row->update(['state' => DiagnosticAttemptItem::ANSWERED, 'answer' => trim($data['answer']), 'correct' => $correct, 'answered_at' => now()->utc()]);
            $this->stop($attempt, $row);
            if (! DiagnosticAttemptItem::query()->where('diagnostic_attempt_id', $attempt->id)->where('state', DiagnosticAttemptItem::WAITING)->exists()) {
                $attempt->update(['completed_at' => now()->utc()]);
            }
            app(MasteryRecompute::class)->diagnostic($row, $group);
        });

        return $this->read($actor, $learnerId, $attemptId);
    }

    /**
     * The teacher's view of one of their groups: who has finished, and the
     * starting map per topic. Learners are listed by name, never ranked, and
     * no total is given per learner.
     */
    public function group(User $actor, int $groupId): array
    {
        $group = app(PracticeAccess::class)->staff($actor, $groupId);
        $this->mastery(false);
        $base = ['group_id' => (int) $group->id, 'group_name' => (string) $group->name, 'subject_name' => (string) $group->subject?->name];
        $found = $this->forGroup($group);
        if ($found === null) {
            return $base + ['available' => false, 'title' => null, 'topics' => [], 'learners' => []];
        }
        [$diagnostic, $version] = $found;
        $today = app(AcademicAuthority::class)->today();
        $memberIds = TeachingGroupMembership::query()->where('teaching_group_id', $group->id)->whereDate('starts_on', '<=', $today)->whereDate('ends_on', '>=', $today)
            ->pluck('learner_id')->map(fn ($id) => (int) $id)->unique()->values();
        $learners = Students::query()->with('user:id,first_name,last_name')->whereIn('id', $memberIds)->limit(1000)->get()
            ->sortBy(fn (Students $s) => mb_strtolower(trim(($s->user->first_name ?? '').' '.($s->user->last_name ?? ''))).'|'.str_pad((string) $s->id, 12, '0', STR_PAD_LEFT))->values();
        $attempts = DiagnosticAttempt::query()->where('teaching_group_id', $group->id)->where('library_item_id', $diagnostic->id)
            ->where('session_year_id', $group->session_year_id)->whereIn('learner_id', $memberIds)->get()->keyBy('learner_id');
        $items = DiagnosticAttemptItem::query()->whereIn('diagnostic_attempt_id', $attempts->pluck('id'))->orderBy('position')->get()->groupBy('diagnostic_attempt_id');
        // Topics in the order the check asks them; then any an earlier version of the check asked.
        $order = [];
        foreach ($this->plan($version, LibraryTopic::query()->findOrFail($diagnostic->library_topic_id)) as [, , $topic]) {
            $order[$topic->id] = $topic->name;
        }
        $mirrorNames = MasteryTopic::query()->where('subject_id', $group->subject_id)->whereIn('id', $items->flatten()->pluck('topic_id')->unique())->pluck('name', 'library_topic_id');
        foreach ($items->flatten() as $row) {
            $order[$row->library_topic_id] ??= (string) ($mirrorNames[$row->library_topic_id] ?? '');
        }
        $counts = array_fill_keys(array_keys($order), array_fill_keys(array_keys(self::BANDS), 0));
        $rows = [];
        foreach ($learners as $learner) {
            $attempt = $attempts->get($learner->id);
            $bands = $attempt === null ? [] : $this->bands($items->get($attempt->id, collect()));
            $topics = [];
            foreach ($bands as $topicId => $band) {
                $counts[$topicId][$band]++;
                $topics[] = ['library_topic_id' => (int) $topicId, 'band' => $band];
            }
            $rows[] = ['learner_id' => (int) $learner->id, 'name' => trim(($learner->user->first_name ?? '').' '.($learner->user->last_name ?? '')),
                'status' => $this->status($attempt), 'topics' => $topics];
        }

        return $base + ['available' => true, 'title' => (string) $diagnostic->title,
            'topics' => array_map(fn ($id, $name) => ['library_topic_id' => (int) $id, 'name' => $name] + $counts[$id], array_keys($order), array_values($order)),
            'learners' => $rows];
    }

    /**
     * The questions to ask: the diagnostic's published practice questions of
     * its framework and subject, by topic (prerequisites first, then in the
     * order the diagnostic lists them), a few per topic, MAX_QUESTIONS in all.
     *
     * @return list<array{0: LibraryItem, 1: LibraryItemVersion, 2: LibraryTopic}>
     */
    private function plan(LibraryItemVersion $version, LibraryTopic $home): array
    {
        $ids = array_values(array_unique(array_map('intval', (array) ($version->content['item_ids'] ?? []))));
        $items = LibraryItem::query()->whereIn('id', $ids)->where('kind', 'practice_question')->whereNotNull('published_version_id')
            ->whereHas('topic', fn ($q) => $q->where('library_framework_id', $home->library_framework_id)->where('subject_code', $home->subject_code))
            ->with('topic')->limit(100)->get()->keyBy('id');
        $versions = LibraryItemVersion::query()->whereIn('id', $items->pluck('published_version_id'))->where('state', 'published')->get()->keyBy('id');
        $byTopic = [];
        $topics = [];
        foreach ($ids as $id) {
            $item = $items->get($id);
            $itemVersion = $item === null ? null : $versions->get($item->published_version_id);
            $definition = $itemVersion?->practiceDefinition();
            if ($itemVersion === null || (int) $itemVersion->library_item_id !== (int) $item->id || ! is_array($definition)
                || ! in_array($definition['kind'] ?? null, PracticeAnswers::SINGLE, true)) {
                continue;
            }
            $byTopic[$item->library_topic_id][] = [$item, $itemVersion, $item->topic];
            $topics[$item->library_topic_id] = true;
        }
        if ($byTopic === []) {
            return [];
        }
        $topicIds = array_map('intval', array_keys($topics));
        // Depths over the whole framework-and-subject graph, so a prerequisite
        // chain through a topic the check has no question for still orders the
        // topics it does ask (A ← B ← C asks A before C without a B question).
        $subjectTopics = LibraryTopic::query()->where('library_framework_id', $home->library_framework_id)->where('subject_code', $home->subject_code)
            ->limit(5001)->pluck('id')->map(fn ($id) => (int) $id)->all();
        abort_if(count($subjectTopics) > 5000, 422, 'The library subject exceeds its bounded catalogue.');
        $edges = array_fill_keys(array_values(array_unique([...$subjectTopics, ...$topicIds])), []);
        foreach (LibraryTopicPrerequisite::query()->whereIn('library_topic_id', $subjectTopics)->whereIn('prerequisite_id', $subjectTopics)->get() as $edge) {
            $edges[(int) $edge->library_topic_id][(int) $edge->prerequisite_id] = true;
        }
        $depth = app(LibraryCatalogue::class)->depths($edges);
        $first = array_flip($topicIds);
        usort($topicIds, fn ($a, $b) => [$depth[$a] ?? 1, $first[$a]] <=> [$depth[$b] ?? 1, $first[$b]]);
        $topicIds = array_slice($topicIds, 0, self::MAX_QUESTIONS);
        $perTopic = count($topicIds) <= 6 ? 3 : (count($topicIds) <= 10 ? 2 : 1);
        $plan = [];
        foreach ($topicIds as $topicId) {
            foreach (array_slice($byTopic[$topicId], 0, $perTopic) as $entry) {
                $plan[] = $entry;
            }
        }

        return array_slice($plan, 0, self::MAX_QUESTIONS);
    }

    /** A topic is clear once two of its answers agree: its other questions are skipped. */
    private function stop(DiagnosticAttempt $attempt, DiagnosticAttemptItem $row): void
    {
        $answers = DiagnosticAttemptItem::query()->where('diagnostic_attempt_id', $attempt->id)->where('library_topic_id', $row->library_topic_id)
            ->where('state', DiagnosticAttemptItem::ANSWERED)->pluck('correct')->map(fn ($c) => (bool) $c);
        if ($answers->count() >= 2 && $answers->unique()->count() === 1) {
            DiagnosticAttemptItem::query()->where('diagnostic_attempt_id', $attempt->id)->where('library_topic_id', $row->library_topic_id)
                ->where('state', DiagnosticAttemptItem::WAITING)->update(['state' => DiagnosticAttemptItem::SKIPPED, 'updated_at' => now()]);
        }
    }

    /**
     * Authorize the learner's attempt afresh: the learner themself, approved,
     * still in the attempt's group this year, both modules, the library switch.
     *
     * @return array{0: DiagnosticAttempt, 1: TeachingGroup}
     */
    private function attempt(User $actor, int $learnerId, int $attemptId, bool $write): array
    {
        app(PracticeAccess::class)->actor($actor, $write);
        $attempt = DiagnosticAttempt::query()->where('learner_id', $learnerId)->findOrFail($attemptId);
        $group = $this->learnerGroup($actor, $learnerId, $attempt->teaching_group_id, $write);
        abort_unless((int) $group->subject_id === $attempt->subject_id && (int) $group->grade_level_id === $attempt->grade_level_id
            && (int) $group->session_year_id === $attempt->session_year_id, 404);

        return [$attempt, $group];
    }

    private function learnerGroup(User $actor, int $learnerId, int $groupId, bool $write): TeachingGroup
    {
        $group = app(PracticeAccess::class)->learner($actor, $learnerId, $groupId, $write);
        // Early-years learners do not use practice (4.1), whoever is signed in to this request.
        abort_unless(app(ModuleGate::class)->allowsStageOf('daily_practice', $actor), 403);
        $this->mastery($write);
        // Switching the library off withdraws the check, as it withdraws library practice.
        abort_unless(app(LibrarySchools::class)->enabled(), 404);

        return $group;
    }

    private function mastery(bool $write): void
    {
        $gate = app(ModuleGate::class);
        $gate->forget();
        abort_unless($write ? $gate->writable('mastery') : $gate->allows('mastery'), 403, 'The mastery module is disabled.');
    }

    private function payload(DiagnosticAttempt $attempt, TeachingGroup $group): array
    {
        $school = app(CurrentSchool::class)->id();
        $items = DiagnosticAttemptItem::query()->where('diagnostic_attempt_id', $attempt->id)->orderBy('position')->get();
        $next = $items->firstWhere('state', DiagnosticAttemptItem::WAITING);
        $question = null;
        if ($next !== null) {
            $definition = $this->definition($next);
            // Only what is needed to answer: never the accepted answers or the explanation.
            $question = ['id' => (int) $next->id, 'position' => (int) $next->position, 'kind' => (string) $definition['kind'], 'prompt' => (string) $definition['prompt'],
                'options' => array_map(fn ($o) => ['id' => (string) $o['id'], 'text' => (string) $o['text']], (array) ($definition['options'] ?? []))];
        }
        $diagnostic = LibraryItemVersion::query()->where('state', 'published')->where('library_item_id', $attempt->library_item_id)->findOrFail($attempt->library_item_version_id);
        $status = $this->status($attempt);
        abort_unless(app(CurrentSchool::class)->id() === $school, 403);

        return ['id' => (int) $attempt->id, 'learner_id' => $attempt->learner_id, 'group_id' => (int) $group->id, 'subject_name' => (string) $group->subject?->name,
            'instructions' => (string) ($diagnostic->content['instructions'] ?? ''), 'status' => $status,
            'answered' => $items->where('state', DiagnosticAttemptItem::ANSWERED)->count(), 'remaining' => $items->where('state', DiagnosticAttemptItem::WAITING)->count(),
            'question' => $question, 'message' => $this->message($status, true, (string) $group->subject?->name),
            'focus_topics' => $status === 'completed' ? $this->focus($attempt, $items) : []];
    }

    /** The published library question of a planned row, checked afresh. */
    private function definition(DiagnosticAttemptItem $row): array
    {
        $version = LibraryItemVersion::query()->where('state', 'published')->where('library_item_id', $row->library_item_id)->findOrFail($row->library_item_version_id);
        $definition = $version->practiceDefinition();
        abort_unless(is_array($definition), 404);

        return $definition;
    }

    private function same(array $definition, string $stored, string $given): bool
    {
        try {
            $answers = app(PracticeAnswers::class);

            return $definition['kind'] === 'number' ? $answers->number($stored) === $answers->number($given) : trim($stored) === trim($given);
        } catch (InvalidArgumentException) {
            return false;
        }
    }

    private function status(?DiagnosticAttempt $attempt): string
    {
        return $attempt === null ? 'not_started' : ($attempt->completed_at === null ? 'in_progress' : 'completed');
    }

    /** @return array<int, string> library topic id => band, in the order asked */
    private function bands(Collection $items): array
    {
        $bands = [];
        foreach ($items->groupBy('library_topic_id') as $topicId => $rows) {
            $answered = $rows->where('state', DiagnosticAttemptItem::ANSWERED);
            $right = $answered->filter(fn ($r) => (bool) $r->correct)->count();
            $bands[(int) $topicId] = $answered->isEmpty() ? 'not_reached' : ($right === $answered->count() ? 'secure' : ($right === 0 ? 'gap' : 'developing'));
        }

        return $bands;
    }

    /** Up to three topics practice begins with: names only, never how the learner did. */
    private function focus(DiagnosticAttempt $attempt, ?Collection $items = null): array
    {
        $items ??= DiagnosticAttemptItem::query()->where('diagnostic_attempt_id', $attempt->id)->orderBy('position')->get();
        $topicIds = [];
        foreach ($this->bands($items) as $topicId => $band) {
            if (in_array($band, ['gap', 'developing'], true)) {
                $topicIds[] = $items->firstWhere('library_topic_id', $topicId)->topic_id;
            }
        }
        $names = MasteryTopic::query()->where('subject_id', $attempt->subject_id)->whereIn('id', array_slice($topicIds, 0, 3))->pluck('name', 'id');

        return array_values(array_map(fn ($id) => (string) $names[$id], array_filter(array_slice($topicIds, 0, 3), fn ($id) => isset($names[$id]))));
    }

    private function message(string $status, bool $self, string $subject): string
    {
        return match (true) {
            $status === 'not_started' && $self => __('A short starting check is ready for :subject. It helps your teacher see where to begin. It is not a test.', ['subject' => $subject]),
            $status === 'not_started' => __('A short starting check is ready for :subject. It helps the teacher see where to begin. It is not a test and has no mark.', ['subject' => $subject]),
            $status === 'in_progress' && $self => __('You have started your starting check for :subject. Carry on when you are ready.', ['subject' => $subject]),
            $status === 'in_progress' => __('The starting check for :subject has been started but is not finished yet.', ['subject' => $subject]),
            default => __('The starting check for :subject is done. It shows where practice begins. It is not a mark.', ['subject' => $subject]),
        };
    }
}
