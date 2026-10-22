<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExamClass extends Model
{
    use BelongsToSchool, HasFactory, SoftDeletes;

    protected $hidden = ['deleted_at', 'created_at', 'updated_at'];

    public function class()
    {
        return $this->belongsTo(ClassSchool::class, 'class_id');
    }

    public function exam()
    {
        return $this->belongsTo(Exam::class, 'exam_id');
    }

    public function timetableByClassID()
    {
        return $this->hasMany(ExamTimetable::class, 'class_id', 'class_id');
    }

    public function class_timetable($exam_id = null, $class_id = null)
    {
        $relation = $this->hasMany(ExamTimetable::class, 'class_id', 'class_id');
        if (! is_null($exam_id)) {
            $relation->where('exam_id', $exam_id);
        } elseif (isset($this->exam_id)) {
            $relation->where('exam_id', $this->exam_id);
        }

        return $relation;
    }

    public function timetableByExamID()
    {
        return $this->hasMany(ExamTimetable::class, 'exam_id', 'exam_id');
    }
}
