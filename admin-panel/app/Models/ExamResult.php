<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\Academics\ProtectsMarkProjection;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExamResult extends Model
{
    use BelongsToSchool, HasFactory, ProtectsMarkProjection, SoftDeletes;

    protected $hidden = ['deleted_at', 'created_at', 'updated_at', 'exact_obtained_units', 'exact_total_units'];

    public function getObtainedMarksAttribute($value)
    {
        $units = $this->attributes['exact_obtained_units'] ?? null;

        return $units === null ? $value : ((int) $units) / 100;
    }

    public function student()
    {
        return $this->belongsTo(Students::class, 'student_id')->with('user')->withTrashed();
    }

    public function session_year()
    {
        return $this->belongsTo(SessionYear::class, 'session_year_id');
    }

    public function exam()
    {
        return $this->belongsTo(Exam::class, 'exam_id');
    }

    public function class_section()
    {
        return $this->belongsTo(ClassSection::class, 'class_section_id')->with('class', 'section', 'class.medium', 'streams');
    }

    public function exam_timetable()
    {
        return $this->belongsTo(ExamTimetable::class, 'exam_id', 'exam_id');
    }
}
