<?php

declare(strict_types=1);

namespace App\Models\Academics;

use App\Models\Students;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AssessmentMark extends Model
{
    use BelongsToSchool, SoftDeletes;

    protected $table = 'assessment_marks';

    protected $guarded = ['id', 'school_id'];

    protected $attributes = ['revision' => 1, 'status' => 'captured', 'disposition' => 'missing'];

    protected function casts(): array
    {
        return ['mark_units' => 'integer', 'revision' => 'integer', 'moderated_at' => 'datetime'];
    }

    public function task()
    {
        return $this->belongsTo(AssessmentTask::class, 'assessment_task_id');
    }

    public function learner()
    {
        return $this->belongsTo(Students::class, 'learner_id');
    }

    public function group()
    {
        return $this->belongsTo(TeachingGroup::class, 'teaching_group_id');
    }

    public function questions()
    {
        return $this->hasMany(QuestionMark::class)->orderBy('assessment_question_id');
    }
}
