<?php

declare(strict_types=1);

namespace App\Services\Academics;

use App\Models\User;
use App\Policies\Academics\MarksPolicy;
use Illuminate\Support\Facades\Validator;

/** Same formula and validation as capture, without writing marks or audit history. */
class MarkPreview
{
    public function preview(User $actor, int $groupId, int $taskId, array $input): array
    {
        $group = app(MarksPolicy::class)->group($actor, $groupId, 'preview');
        $rows = Validator::make($input, MarkRecording::rules())->validate()['rows'];
        $task = app(MarkDefinitions::class)->task($group, $taskId);
        $root = app(MarkDefinitions::class)->canonical($task);
        $rows = app(MarkRecording::class)->normaliseRows($group, $task, $root, $rows);
        $termId = (int) $task->assessment->term_id;
        $prepared = app(MarkInputs::class)->prepare($group, $termId, array_column($rows, 'learner_id'));
        $results = [];
        foreach ($rows as $row) {
            $override = [(int) $root->id => ['mark_units' => $row['mark_units'], 'disposition' => $row['disposition'], 'revision' => $row['expected_revision'], 'mark_id' => null]];
            $inputs = app(MarkInputs::class)->forLearner($group, $row['learner_id'], $termId, $override, $prepared);
            $results[] = ['learner_id' => $row['learner_id'], 'result' => app(MarkInputs::class)->calculate($inputs)];
        }

        return ['previews' => $results];
    }
}
