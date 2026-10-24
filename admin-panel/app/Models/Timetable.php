<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\Academics\DatedAcademicProjection;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Timetable extends Model
{
    use BelongsToSchool;

    protected $hidden = ['deleted_at', 'created_at', 'updated_at'];

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
