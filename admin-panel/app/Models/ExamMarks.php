<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\Academics\ProtectsMarkProjection;
use App\Services\Platform\Audit\Auditable;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExamMarks extends Model
{
    use Auditable, BelongsToSchool, HasFactory, ProtectsMarkProjection, SoftDeletes;

    protected $hidden = ['deleted_at', 'created_at', 'updated_at', 'exact_obtained_units'];

    public function timetable()
    {
        return $this->belongsTo(ExamTimetable::class, 'exam_timetable_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function getObtainedMarksAttribute($value)
    {
        $units = $this->attributes['exact_obtained_units'] ?? null;

        return $units === null ? $value : ((int) $units) / 100;
    }

    public function student()
    {
        return $this->belongsTo(Students::class)->withTrashed();
    }

    //     return $this->hasManyThrough(Exam::class,ExamTimetable::class,'id','id','exam_id')->orderBy('date','asc');
    // }

    // // Working demo
    // public function results(){
    //     return $this->hasManyThrough(ExamMarks::class,ExamTimetable::class,'exam_id','exam_timetable_id')->orderBy('date','asc');
    // }
}
