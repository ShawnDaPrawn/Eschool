<?php

declare(strict_types=1);

namespace App\Services\Academics;

use App\Models\Academics\AssessmentMark;
use App\Models\Platform\School;
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

class MarkModeration
{
    public static function rules(): array
    {
        return ['reason' => ['required', 'string', 'max:500'], 'rows' => ['required', 'array', 'min:1', 'max:1000'],
            'rows.*.learner_id' => ['required', 'integer', 'min:1'], 'rows.*.expected_revision' => ['required', 'integer', 'min:1']];
    }

    public function moderate(User $actor, int $groupId, int $taskId, array $input): array
    {
        app(MarksPolicy::class)->group($actor, $groupId, 'moderate');
        $data = Validator::make($input, self::rules())->validate();

        return DB::transaction(function () use ($actor, $groupId, $taskId, $data) {
            School::query()->lockForUpdate()->findOrFail(app(CurrentSchool::class)->id());
            $group = app(MarksPolicy::class)->group($actor, $groupId, 'moderate');
            $task = app(MarkDefinitions::class)->task($group, $taskId);
            $root = app(MarkDefinitions::class)->canonical($task);
            $term = Term::query()->lockForUpdate()->findOrFail($task->assessment->term_id);
            abort_if($term->marks_lock_on !== null && app(AcademicAuthority::class)->today() >= $term->marks_lock_on->toDateString(), 409, __('This term is locked.'));
            $ids = array_map('intval', array_column($data['rows'], 'learner_id'));
            if (count(array_unique($ids)) !== count($ids)) {
                throw ValidationException::withMessages(['rows' => __('Submit each learner once.')]);
            }
            $saved = [];
            foreach ($data['rows'] as $row) {
                $mark = AssessmentMark::query()->with('questions')->where('assessment_task_id', $root->id)->where('learner_id', $row['learner_id'])->lockForUpdate()->firstOrFail();
                abort_if($mark->revision !== (int) $row['expected_revision'], 409, __('A mark changed. Reload before moderation.'));
                if ($mark->mark_units === null && ! in_array($mark->disposition, ['absent', 'excused'], true)) {
                    throw ValidationException::withMessages(['rows' => __('Capture all required scores before moderating this learner.')]);
                }
                abort_if($mark->status === 'locked', 409, __('This mark is locked.'));
                $before = app(MarkRecording::class)->data($mark);
                $mark->status = 'moderated';
                $mark->moderated_by = $actor->id;
                $mark->moderated_at = now();
                $mark->revision++;
                $mark->save();
                $after = app(MarkRecording::class)->data($mark);
                $saved[] = $after;
                Audit::record('academics.mark_moderated', $mark, $before, $after + ['moderation_reason' => $data['reason']]);
            }
            Outbox::record(new AcademicMarksChanged(app(CurrentSchool::class)->id(), $groupId, (int) $term->id));

            return ['marks' => $saved, 'recompute_pending' => true];
        }, 3);
    }
}
