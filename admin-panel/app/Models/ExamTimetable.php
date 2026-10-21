<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExamTimetable extends Model
{
    use HasFactory, SoftDeletes;

    protected $hidden = ["deleted_at", "created_at", "updated_at"];

    /**
     * Both apps read the times as starting_time / ending_time (the student
     * exam-details endpoint renamed them by hand; the others didn't).
     */
    protected $appends = ['starting_time', 'ending_time'];

    public function getStartingTimeAttribute()
    {
        return $this->attributes['start_time'] ?? null;
    }

    public function getEndingTimeAttribute()
    {
        return $this->attributes['end_time'] ?? null;
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class, 'subject_id')->withTrashed();
    }

    public function exam()
    {
        return $this->belongsTo(Exam::class, 'exam_id');
    }

    public function class()
    {
        return $this->belongsTo(ClassSchool::class, 'class_id');
    }

    public function session_year()
    {
        return $this->belongsTo(SessionYear::class, 'session_year_id');
    }

    public function exam_marks()
    {
        return $this->hasMany(ExamMarks::class, 'exam_timetable_id');
    }
}
