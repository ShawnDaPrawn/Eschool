<?php

declare(strict_types=1);

namespace App\Models\Academics;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AssessmentQuestion extends Model
{
    use BelongsToSchool, SoftDeletes;

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return ['topic_keys' => 'array', 'max_mark_units' => 'integer'];
    }

    public function task()
    {
        return $this->belongsTo(AssessmentTask::class, 'assessment_task_id');
    }
}
