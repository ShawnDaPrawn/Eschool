<?php

declare(strict_types=1);

namespace App\Models\Academics;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AssessmentTask extends Model
{
    use BelongsToSchool, SoftDeletes;

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return ['topic_keys' => 'array', 'due_on' => 'date', 'weight_bp' => 'integer', 'annual_weight_bp' => 'integer', 'max_mark_units' => 'integer'];
    }

    public function assessment()
    {
        return $this->belongsTo(AssessmentPlan::class, 'assessment_plan_id');
    }

    public function questions()
    {
        return $this->hasMany(AssessmentQuestion::class);
    }
}
