<?php

declare(strict_types=1);

namespace App\Services\Growth;

use App\Models\Academics\AssessmentTask;
use App\Models\Academics\CurriculumPlan;
use App\Models\Academics\MarkReleaseEntry;
use App\Models\Academics\TeachingGroup;
use App\Models\Growth\MasteryEvidence;
use App\Models\Growth\MasteryRecord;
use App\Models\Growth\MasteryScope;
use App\Models\Growth\MasteryTopic;
use App\Models\Learning\PracticeDailySet;
use App\Models\Learning\PracticeItem;
use App\Models\Learning\PracticeItemVersion;
use App\Models\Learning\PracticeReview;
use App\Models\Learning\PracticeSetEntry;
use App\Models\Learning\TutorCheckpoint;
use App\Models\Learning\TutorSession;
use App\Models\Subject;
use App\Services\Academics\MarkInputs;
use App\Services\Platform\Tenancy\CurrentSchool;
use App\Services\Platform\Tenancy\SchoolTable;
use Illuminate\Support\Facades\DB;

/** A school/subject mutex protects evidence replacement and the aggregate across grades/years. */
class MasteryRecompute
{
    private array $definitions = [];

    public function group(int $groupId): array
    {
        $this->definitions = [];
        $group = TeachingGroup::query()->findOrFail($groupId);

        return $this->serialised((int) $group->subject_id, function () use ($group) {
            app(TopicGraph::class)->sync((int) $group->subject_id);
            $topics = MasteryTopic::query()->where('subject_id', $group->subject_id)->get()->keyBy('topic_key');
            $prepared = app(MarkInputs::class)->prepare($group, null);
            $prior = MasteryEvidence::query()->where('subject_id', $group->subject_id)->where('grade_level_id', $group->grade_level_id)
                ->where('session_year_id', $group->session_year_id)->whereIn('source_type', ['question_mark', 'released_question_mark']);
            $learnerIds = array_values(array_unique(array_merge($prior->pluck('learner_id')->map(fn ($id) => (int) $id)->all(),
                $prepared['members']->pluck('learner_id')->map(fn ($id) => (int) $id)->all())));
            $prior->delete();
            $written = 0;
            foreach ($prepared['members']->pluck('learner_id')->unique() as $learnerId) {
                $input = app(MarkInputs::class)->forLearner($group, (int) $learnerId, null, [], $prepared);
                foreach ($input['tasks'] as $task) {
                    $written += $this->task($group, (int) $learnerId, 'staff', 'question_mark', $task,
                        'mark:'.($task['mark_id'] ?? 'missing').':'.($task['revision'] ?? 0),
                        $prepared['marks']->get($learnerId)?->get($task['canonical_task_id'])?->updated_at ?? now(), $topics);
                }
            }
            $latestEntries = MarkReleaseEntry::query()->selectRaw('MAX(mark_release_entries.id)')
                ->join('mark_releases as mastery_release', 'mastery_release.id', '=', 'mark_release_entries.mark_release_id')
                ->where('mastery_release.school_id', app(CurrentSchool::class)->id())->where('mastery_release.subject_id', $group->subject_id)
                ->where('mastery_release.grade_level_id', $group->grade_level_id)->where('mastery_release.session_year_id', $group->session_year_id)
                ->groupBy('mark_release_entries.learner_id', 'mastery_release.period_key');
            $entries = MarkReleaseEntry::query()->whereIn('id', $latestEntries)
                ->with('snapshot', 'release')->orderByDesc('id')->limit(10001)->get();
            abort_if($entries->count() > 10000, 422, 'Mastery release history exceeds its bounded rebuild.');
            $seen = [];
            foreach ($entries as $entry) {
                abort_unless((int) $entry->snapshot->learner_id === (int) $entry->learner_id && (int) $entry->snapshot->subject_id === (int) $group->subject_id
                    && (int) $entry->snapshot->grade_level_id === (int) $group->grade_level_id && (int) $entry->snapshot->session_year_id === (int) $group->session_year_id, 409);
                $learnerId = (int) $entry->learner_id;
                $learnerIds[] = $learnerId;
                foreach ($entry->snapshot->inputs['tasks'] ?? [] as $task) {
                    $canonical = $task['canonical_task_id'];
                    if (isset($seen[$learnerId][$canonical])) {
                        continue;
                    }
                    $seen[$learnerId][$canonical] = true;
                    $written += $this->task($group, $learnerId, 'family', 'released_question_mark', $task,
                        'release:'.$entry->id, $entry->release->created_at, $topics);
                }
            }
            foreach (array_unique($learnerIds) as $learnerId) {
                $this->aggregate((int) $learnerId, (int) $group->subject_id);
            }

            return ['group_id' => (int) $group->id, 'learners' => count(array_unique($learnerIds)), 'evidence_written' => $written];
        });
    }

    private function task(TeachingGroup $group, int $learnerId, string $audience, string $kind, array $entry, string $source, mixed $observed, $topics): int
    {
        if (! in_array($entry['disposition'] ?? null, ['recorded', 'catch_up'], true)) {
            return 0;
        }
        $definition = $this->definitions[$entry['id']] ??= AssessmentTask::withTrashed()->with(['questions' => fn ($q) => $q->withTrashed(), 'assessment' => fn ($q) => $q->withTrashed()])->findOrFail($entry['id']);
        abort_unless((int) ($definition->source_task_id ?? $definition->id) === (int) $entry['canonical_task_id'], 409);
        $plan = CurriculumPlan::withTrashed()->findOrFail($definition->assessment->curriculum_plan_id);
        abort_unless((int) $plan->subject_id === (int) $group->subject_id && (int) $plan->grade_level_id === (int) $group->grade_level_id
            && (int) $plan->session_year_id === (int) $group->session_year_id && (int) $definition->assessment->term_id === (int) $entry['term_id'], 409);
        $questions = $definition->questions->keyBy('number');
        $root = $this->definitions[$entry['canonical_task_id']] ??= AssessmentTask::withTrashed()->with(['questions' => fn ($q) => $q->withTrashed(), 'assessment' => fn ($q) => $q->withTrashed()])->findOrFail($entry['canonical_task_id']);
        abort_unless((int) $root->assessment->term_id === (int) $entry['term_id'] && $root->max_mark_units === $definition->max_mark_units, 409);
        $canonical = $root->questions->keyBy('id');
        $written = 0;
        foreach ($entry['question_marks'] ?? [] as $row) {
            $original = $canonical->get($row['canonical_question_id']);
            $question = $original === null ? null : $questions->get($original->number);
            $score = $row['mark_units'] ?? null;
            if ($question === null || ! is_int($score) || $score < 0 || $score > $question->max_mark_units || $original->max_mark_units !== $question->max_mark_units) {
                continue;
            }
            foreach (array_unique($question->topic_keys ?? []) as $key) {
                $topic = $topics->get($key);
                if ($topic === null) {
                    continue;
                }
                MasteryEvidence::updateOrCreate(['subject_id' => $group->subject_id, 'learner_id' => $learnerId, 'topic_id' => $topic->id,
                    'audience' => $audience, 'source_key' => $source.':q:'.$original->id], ['grade_level_id' => $group->grade_level_id,
                        'session_year_id' => $group->session_year_id, 'source_type' => $kind, 'achieved_units' => $score,
                        'maximum_units' => $question->max_mark_units, 'observed_at' => $observed]);
                $written++;
            }
        }

        return $written;
    }

    /** Verified practice/tutor adapters must share the same aggregate serialization. */
    public function learner(int $learnerId, int $subjectId): void
    {
        $this->serialised($subjectId, function () use ($learnerId, $subjectId) {
            $this->aggregate($learnerId, $subjectId);
        });
    }

    private function serialised(int $subjectId, callable $run): mixed
    {
        Subject::query()->findOrFail($subjectId);
        $identity = ['school_id' => app(CurrentSchool::class)->id(), 'subject_id' => $subjectId];
        SchoolTable::query('mastery_scopes')->insertOrIgnore($identity + ['created_at' => now(), 'updated_at' => now()]);

        return DB::transaction(function () use ($identity, $run) {
            // Lock before any evidence snapshot. Grade/year locks are insufficient:
            // each learner projection includes evidence from every grade and year.
            $scope = MasteryScope::query()->where($identity)->lockForUpdate()->firstOrFail();
            $result = $run();
            $scope->update(['computed_at' => now()]);

            return $result;
        }, 3);
    }

    /** Practice is a distinct self-study provenance, never a canonical assessment mark. */
    public function practice(PracticeReview $review, TeachingGroup $group, int $topicId): void
    {
        $this->serialised((int) $group->subject_id, function () use ($review, $group, $topicId) {
            $entry = PracticeSetEntry::findOrFail($review->practice_set_entry_id);
            $set = PracticeDailySet::where('learner_id', $review->learner_id)->where('teaching_group_id', $group->id)->findOrFail($entry->practice_daily_set_id);
            $version = PracticeItemVersion::findOrFail($entry->practice_item_version_id);
            PracticeItem::where('teaching_group_id', $group->id)->where('topic_id', $topicId)->findOrFail($version->practice_item_id);
            MasteryEvidence::updateOrCreate(['subject_id' => $group->subject_id, 'learner_id' => $review->learner_id, 'topic_id' => $topicId,
                'audience' => 'practice', 'source_key' => 'practice:'.$review->id], ['grade_level_id' => $group->grade_level_id,
                    'session_year_id' => $group->session_year_id, 'source_type' => 'practice_review', 'achieved_units' => $review->correct ? 1 : 0,
                    'maximum_units' => 1, 'observed_at' => $review->created_at]);
            $this->aggregate((int) $review->learner_id, (int) $group->subject_id);
        });
    }

    /** Only deterministic first attempts on approved definitions count as Tutor evidence. */
    public function tutorCheckpoint(TutorCheckpoint $checkpoint, TeachingGroup $group): void
    {
        $this->serialised((int) $group->subject_id, function () use ($checkpoint, $group) {
            $row = TutorCheckpoint::whereNotNull('answered_at')->findOrFail($checkpoint->id);
            $session = TutorSession::where('teaching_group_id', $group->id)->where('learner_id', $row->learner_id)
                ->where('user_id', $row->user_id)->where('topic_id', $row->topic_id)->findOrFail($row->tutor_session_id);
            $version = PracticeItemVersion::findOrFail($row->practice_item_version_id);
            PracticeItem::where('teaching_group_id', $group->id)->where('topic_id', $row->topic_id)->findOrFail($version->practice_item_id);
            abort_unless((int) $session->subject_id === (int) $group->subject_id && (int) $session->session_year_id === (int) $group->session_year_id, 404);
            MasteryEvidence::updateOrCreate(['subject_id' => $group->subject_id, 'learner_id' => $row->learner_id, 'topic_id' => $row->topic_id,
                'audience' => 'practice', 'source_key' => 'tutor_checkpoint:'.$row->id], ['grade_level_id' => $group->grade_level_id,
                    'session_year_id' => $group->session_year_id, 'source_type' => 'tutor_checkpoint', 'achieved_units' => $row->correct ? 1 : 0,
                    'maximum_units' => 1, 'observed_at' => $row->answered_at->setTimezone(app(CurrentSchool::class)->school()->timezone)]);
            $this->aggregate((int) $row->learner_id, (int) $group->subject_id);
        });
    }

    private function aggregate(int $learnerId, int $subjectId): void
    {
        $topicIds = MasteryEvidence::query()->where('learner_id', $learnerId)->where('subject_id', $subjectId)->pluck('topic_id')
            ->merge(MasteryRecord::query()->where('learner_id', $learnerId)->where('subject_id', $subjectId)->pluck('topic_id'))->unique();
        foreach ($topicIds as $topicId) {
            foreach (['staff', 'family', 'practice'] as $audience) {
                $rows = MasteryEvidence::query()->where('learner_id', $learnerId)->where('subject_id', $subjectId)->where('topic_id', $topicId)
                    ->whereIn('audience', $audience === 'practice' ? ['practice'] : [$audience, 'practice'])->orderByDesc('observed_at')->orderByDesc('id')->limit(20)->get(['achieved_units', 'maximum_units'])->toArray();
                $score = app(MasteryScore::class)->calculate($rows);
                MasteryRecord::updateOrCreate(['subject_id' => $subjectId, 'learner_id' => $learnerId, 'topic_id' => $topicId, 'audience' => $audience], $score + ['computed_at' => now()]);
            }
        }
    }
}
