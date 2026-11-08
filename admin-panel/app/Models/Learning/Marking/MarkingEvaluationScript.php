<?php

declare(strict_types=1);

namespace App\Models\Learning\Marking;

use Illuminate\Database\Eloquent\Model;

/**
 * One de-identified, teacher-marked script for an evaluation paper (Action
 * Plan 3.18): the answers, the teacher's marks per mark point, the marker
 * code and the source. Never linked to a learner; the source's own reference
 * is kept only as a keyed hash. Platform-owned.
 *
 * @property int $id
 * @property int $marking_evaluation_paper_id
 * @property string $reference_hash
 * @property list<array{number: string, answer: string, marks: list<int>}> $questions
 * @property int $teacher_units
 * @property int $max_units
 */
class MarkingEvaluationScript extends Model
{
    use PlatformEvaluationRow;

    protected $table = 'marking_evaluation_scripts';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['questions' => 'array', 'teacher_units' => 'integer', 'max_units' => 'integer', 'marking_evaluation_paper_id' => 'integer', 'imported_by' => 'integer'];
    }
}
