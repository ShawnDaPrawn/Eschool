<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\Academics\CurrentTimetableProjection;
use App\Services\Academics\DatedAcademicProjection;
use App\Services\Academics\TimetableProjection;
use App\Services\Platform\Tenancy\BelongsToSchool;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Timetable extends Model
{
    protected static function booted(): void
    {
        static::addGlobalScope(new CurrentTimetableProjection);
        static::saving(function (self $row) {
            if (! app(CurrentSchool::class)->isMigrating()) {
                app(TimetableProjection::class)->guardLegacy($row);
            }
        });
        static::deleting(function (self $row) {
            abort_if($row->timetable_lesson_id !== null, 409, __('Cancel this lesson in the academic timetable.'));
        });
    }

    use BelongsToSchool;

    protected $hidden = ['deleted_at', 'created_at', 'updated_at', 'timetable_lesson_id', 'room_id'];

    use SoftDeletes;

    public function subject_teacher()
    {
        return $this->belongsTo(SubjectTeacher::class)->withoutGlobalScope(DatedAcademicProjection::class)->with('subject', 'teacher.user:id,first_name,last_name')->withTrashed();
    }

    public function class_section()
    {
        return $this->belongsTo(ClassSection::class)->with('class.medium', 'section');
    }

    public function subject()
    {
        return $this->belongsTo(SubjectTeacher::class, 'subject_teacher_id')->withoutGlobalScope(DatedAcademicProjection::class)->with('subject')->withTrashed();
    }

    public function term()
    {
        return $this->belongsTo(Term::class);
    }
}
