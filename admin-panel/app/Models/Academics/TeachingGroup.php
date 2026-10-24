<?php

declare(strict_types=1);

namespace App\Models\Academics;

use App\Models\ClassSection;
use App\Models\SessionYear;
use App\Models\Subject;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TeachingGroup extends Model
{
    use BelongsToSchool, SoftDeletes;

    protected $table = 'teaching_groups';

    protected $guarded = ['id', 'school_id'];

    protected $attributes = ['revision' => 1, 'active' => true];

    protected function casts(): array
    {
        return ['revision' => 'integer', 'starts_on' => 'date', 'ends_on' => 'date', 'active' => 'boolean'];
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function gradeLevel()
    {
        return $this->belongsTo(GradeLevel::class, 'grade_level_id');
    }

    public function year()
    {
        return $this->belongsTo(SessionYear::class, 'session_year_id')->withTrashed();
    }

    public function homeroom()
    {
        return $this->belongsTo(ClassSection::class, 'class_section_id');
    }

    public function memberships()
    {
        return $this->hasMany(TeachingGroupMembership::class);
    }

    public function allocations()
    {
        return $this->hasMany(TeachingAllocation::class);
    }
}
