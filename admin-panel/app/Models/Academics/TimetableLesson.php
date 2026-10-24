<?php

declare(strict_types=1);

namespace App\Models\Academics;

use App\Models\ClassSection;
use App\Models\Teacher;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TimetableLesson extends Model
{
    use BelongsToSchool, SoftDeletes;

    protected $table = 'timetable_lessons';

    protected $guarded = ['id', 'school_id'];

    protected function casts(): array
    {
        return ['revision' => 'integer', 'weekday' => 'integer', 'active' => 'boolean', 'starts_on' => 'date', 'ends_on' => 'date', 'occurs_on' => 'date'];
    }

    public function period()
    {
        return $this->belongsTo(BellPeriod::class, 'bell_period_id');
    }

    public function room()
    {
        return $this->belongsTo(Room::class, 'room_id');
    }

    public function group()
    {
        return $this->belongsTo(TeachingGroup::class, 'teaching_group_id');
    }

    public function teacher()
    {
        return $this->belongsTo(Teacher::class, 'teacher_id');
    }

    public function homeroom()
    {
        return $this->belongsTo(ClassSection::class, 'class_section_id');
    }
}
