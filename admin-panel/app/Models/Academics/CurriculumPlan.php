<?php

declare(strict_types=1);

namespace App\Models\Academics;

use App\Models\SessionYear;
use App\Models\Subject;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CurriculumPlan extends Model
{
    use BelongsToSchool, SoftDeletes;

    protected $guarded = ['id', 'school_id'];

    protected $attributes = ['state' => 'draft', 'revision' => 1];

    protected function casts(): array
    {
        return ['standard_snapshot' => 'array', 'annual_components' => 'array', 'version' => 'integer', 'revision' => 'integer'];
    }

    public function topics()
    {
        return $this->hasMany(CurriculumTopic::class);
    }

    public function assessments()
    {
        return $this->hasMany(AssessmentPlan::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function gradeLevel()
    {
        return $this->belongsTo(GradeLevel::class);
    }

    public function year()
    {
        return $this->belongsTo(SessionYear::class, 'session_year_id');
    }
}
