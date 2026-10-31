<?php

declare(strict_types=1);

namespace App\Models\Academics;

use App\Services\Platform\Audit\Auditable;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Dates on which one grade writes exams (Action Plan 1.8). On those days the
 * learner and family week view shows an exam day, and normal lessons are left
 * out unless lessons_continue. engineering/specs/academics/exam-mode.md.
 *
 * @property int $id
 * @property int $session_year_id
 * @property int $grade_level_id
 * @property string $name
 * @property Carbon $starts_on
 * @property Carbon $ends_on
 * @property bool $lessons_continue
 */
class ExamPeriod extends Model
{
    use Auditable, BelongsToSchool;

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return ['session_year_id' => 'integer', 'grade_level_id' => 'integer', 'starts_on' => 'date', 'ends_on' => 'date', 'lessons_continue' => 'boolean', 'created_by' => 'integer'];
    }

    public function grade(): BelongsTo
    {
        return $this->belongsTo(GradeLevel::class, 'grade_level_id');
    }
}
