<?php

declare(strict_types=1);

namespace App\Models\Academics;

use App\Models\Term;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AssessmentPlan extends Model
{
    use BelongsToSchool, SoftDeletes;

    protected $guarded = ['id', 'school_id'];

    protected $attributes = ['state' => 'draft'];

    protected function casts(): array
    {
        return ['components' => 'array', 'published_at' => 'datetime'];
    }

    public function curriculum()
    {
        return $this->belongsTo(CurriculumPlan::class, 'curriculum_plan_id');
    }

    public function tasks()
    {
        return $this->hasMany(AssessmentTask::class);
    }

    public function term()
    {
        return $this->belongsTo(Term::class);
    }
}
