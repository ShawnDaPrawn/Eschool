<?php

declare(strict_types=1);

namespace App\Services\Academics;

use App\Models\Academics\AssessmentMark;
use App\Models\Academics\AssessmentTask;
use App\Models\Academics\TeachingGroup;
use App\Models\Academics\TeachingGroupMembership;
use App\Models\Platform\School;
use App\Models\Students;
use App\Models\Term;
use App\Models\User;
use App\Policies\Academics\MarksPolicy;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Events\AcademicMarksChanged;
use App\Services\Platform\Events\Outbox;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class MarkRecording
{
    public function legacy(User $actor, array $rows, bool $correction = false): bool
    {
        return app(LegacyMarks::class)->capture($actor, $rows, $correction);
    }

    public static function rules(): array
    {
        return ['rows' => ['required', 'array', 'min:1', 'max:1000'], 'rows.*.learner_id' => ['required', 'integer', 'min:1'],
            'rows.*.expected_revision' => ['required', 'integer', 'min:0'], 'rows.*.disposition' => ['required', 'in:recorded,missing,absent,excused,catch_up'],
            'rows.*.mark' => ['present', 'nullable', 'string', 'max:12'], 'rows.*.note' => ['sometimes', 'nullable', 'string', 'max:500'],
            'rows.*.question_marks' => ['present', 'array', 'max:100'], 'rows.*.question_marks.*.question_id' => ['required', 'integer', 'min:1'],
            'rows.*.question_marks.*.mark' => ['present', 'nullable', 'string', 'max:12']];
    }

    public function capture(User $actor, int $groupId, int $taskId, array $input, bool $asGradeHead = false): array
    {
        app(MarksPolicy::class)->group($actor, $groupId, $asGradeHead ? 'correct' : 'capture');
        $rows = Validator::make($input, self::rules())->validate()['rows'];

        return DB::transaction(function () use ($actor, $groupId, $taskId, $rows, $asGradeHead) {
            School::query()->lockForUpdate()->findOrFail(app(CurrentSchool::class)->id());
            $group = app(MarksPolicy::class)->group($actor, $groupId, $asGradeHead ? 'correct' : 'capture');
            $task = app(MarkDefinitions::class)->task($group, $taskId);
            $term = Term::query()->lockForUpdate()->findOrFail($task->assessment->term_id);
            abort_if($term->marks_lock_on !== null && app(AcademicAuthority::class)->today() >= $term->marks_lock_on->toDateString(), 409, __('This term is locked. Marks were not changed.'));
            $root = app(MarkDefinitions::class)->canonical($task);
            $normal = $this->normaliseRows($group, $task, $root, $rows);
            $saved = [];
            $changed = false;
            foreach ($normal as $row) {
                if ($asGradeHead && $row['note'] === null) {
                    throw ValidationException::withMessages(['note' => __('Record a reason for a grade-head correction.')]);
                }
                $mark = AssessmentMark::withTrashed()->where('assessment_task_id', $root->id)->where('learner_id', $row['learner_id'])->lockForUpdate()->first();
                abort_if($mark?->trashed(), 409, __('This mark is archived. Review its history before restoring it.'));
                abort_if((int) ($mark?->revision ?? 0) !== $row['expected_revision'], 409, __('A mark changed. Reload and compare before saving.'));
                if ($mark !== null && $mark->status === 'moderated') {
                    abort_unless(app(AcademicAuthority::class)->leads($actor, 'grade_head', (int) $group->grade_level_id, (int) $group->session_year_id), 403, __('Only the grade head may correct moderated marks.'));
                    if ($row['note'] === null) {
                        throw ValidationException::withMessages(['note' => __('Record a reason for correcting a moderated mark.')]);
                    }
                }
                abort_if($mark?->status === 'locked', 409, __('This mark is locked.'));
                if ($mark?->mark_units !== null && $row['mark_units'] === null && $row['note'] === null) {
                    throw ValidationException::withMessages(['note' => __('Record a reason when clearing an existing score.')]);
                }
                $mark ??= new AssessmentMark;
                $before = $mark->exists ? $this->data($mark->load('questions')) : [];
                $values = ['assessment_task_id' => $root->id, 'learner_id' => $row['learner_id'], 'mark_units' => $row['mark_units'],
                    'disposition' => $row['disposition'], 'note' => $row['note']];
                $same = $mark->exists && collect($values)->every(fn ($value, $key) => $value === $mark->$key);
                $oldQuestions = $mark->exists ? $mark->questions->mapWithKeys(fn ($q) => [(int) $q->assessment_question_id => $q->mark_units])->all() : [];
                ksort($oldQuestions);
                ksort($row['question_units']);
                if ($same && $oldQuestions === $row['question_units']) {
                    $saved[] = $before;

                    continue;
                }
                if ($mark->exists) {
                    $mark->revision++;
                }
                $mark->fill($values);
                $mark->teaching_group_id = $group->id;
                $mark->captured_by = $actor->id;
                // A grade-head correction invalidates prior moderation and must be reviewed again.
                $mark->status = 'captured';
                $mark->moderated_by = null;
                $mark->moderated_at = null;
                $mark->save();
                foreach ($row['question_units'] as $question => $units) {
                    $mark->questions()->updateOrCreate(['assessment_question_id' => $question], ['mark_units' => $units]);
                }
                $mark->load('questions');
                $after = $this->data($mark);
                Audit::record('academics.mark_captured', $mark, $before, $after);
                $saved[] = $after;
                $changed = true;
            }
            if ($changed) {
                Outbox::record(new AcademicMarksChanged(app(CurrentSchool::class)->id(), $groupId, (int) $term->id));
            }

            return ['marks' => $saved, 'recompute_pending' => $changed];
        }, 3);
    }

    public function normaliseRows(TeachingGroup $group, AssessmentTask $task, AssessmentTask $root, array $rows): array
    {
        $ids = array_map('intval', array_column($rows, 'learner_id'));
        if (count(array_unique($ids)) !== count($ids)) {
            throw ValidationException::withMessages(['rows' => __('Submit each learner once.')]);
        }
        $owned = Students::query()->whereIn('id', $ids)->pluck('id')->map(fn ($id) => (int) $id)->all();
        abort_unless(count($owned) === count($ids), 404);
        $members = TeachingGroupMembership::query()->where('teaching_group_id', $group->id)->whereIn('learner_id', $ids)->pluck('learner_id')->map(fn ($id) => (int) $id)->unique()->all();
        abort_unless(count($members) === count($ids), 403, __('Only this group’s learners may be marked here.'));
        $normal = [];
        foreach ($rows as $i => $row) {
            $normal[] = app(MarkValues::class)->normalise($task, $root, $row, 'rows.'.$i);
        }

        return $normal;
    }

    public function data(AssessmentMark $mark): array
    {
        return ['id' => (int) $mark->id, 'canonical_task_id' => (int) $mark->assessment_task_id, 'learner_id' => (int) $mark->learner_id,
            'teaching_group_id' => (int) $mark->teaching_group_id, 'mark' => $mark->mark_units === null ? null : AcademicNumbers::decimal($mark->mark_units),
            'status' => $mark->status, 'disposition' => $mark->disposition, 'revision' => $mark->revision, 'note' => $mark->note,
            'captured_by' => (int) $mark->captured_by, 'moderated_by' => $mark->moderated_by === null ? null : (int) $mark->moderated_by,
            'moderated_at' => $mark->moderated_at?->toISOString(), 'question_marks' => $mark->questions->map(fn ($q) => ['canonical_question_id' => (int) $q->assessment_question_id, 'mark' => $q->mark_units === null ? null : AcademicNumbers::decimal($q->mark_units)])->all()];
    }
}
