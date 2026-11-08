<?php

declare(strict_types=1);

namespace App\Models\Learning\Marking;

use Illuminate\Database\Eloquent\Model;

/**
 * Whether learners receive engine marking in a subject (Action Plan 3.18):
 * switched on by an HQ approver against one passing run, at the engine and
 * profile versions it measured. Platform-owned.
 *
 * @property int $id
 * @property string $subject
 * @property bool $enabled
 * @property int|null $marking_evaluation_run_id
 * @property string|null $engine_version
 * @property string|null $profile_version
 * @property int $revision
 */
class MarkingSubjectGate extends Model
{
    use PlatformEvaluationRow;

    protected $table = 'marking_subject_gates';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['enabled' => 'boolean', 'marking_evaluation_run_id' => 'integer', 'approved_by' => 'integer', 'approved_at' => 'immutable_datetime',
            'changed_by' => 'integer', 'revision' => 'integer'];
    }
}
