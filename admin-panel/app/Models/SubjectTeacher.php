<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\Academics\DatedAcademicProjection;
use App\Services\Platform\Tenancy\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class SubjectTeacher extends Model
{
    use BelongsToSchool, SoftDeletes;

    protected $hidden = ['deleted_at', 'created_at', 'updated_at', 'teaching_allocation_id'];

    public function class_section()
    {
        return $this->belongsTo(ClassSection::class)->with('class.medium', 'section')->withTrashed();
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class)->withTrashed();
    }

    public function teacher()
    {
        return $this->belongsTo(Teacher::class)->with('user')->withTrashed();
    }

    public function term()
    {
        return $this->belongsTo(Term::class);
    }

    public function timetables(): HasMany
    {
        return $this->hasMany(Timetable::class, 'subject_teacher_id');
    }

    public function announcement()
    {
        return $this->morphMany(Announcement::class, 'table');
    }

    public function scopeSubjectTeacher($query, $class_section_id = null)
    {
        $user = Auth::user();
        if ($user->hasRole('Teacher')) {
            $teacher_id = $user->teacher()->pluck('id');

            return $query->whereIn('teacher_id', $teacher_id);
        }

        return $query;
    }

    protected static function booted()
    {
        static::addGlobalScope(new DatedAcademicProjection('subject'));
        static::updating(function ($row) {
            abort_if($row->getRawOriginal('teaching_allocation_id') !== null, 409, __('Edit the dated teaching allocation instead.'));
        });
        static::deleting(function ($subjectTeacher) {
            abort_if($subjectTeacher->teaching_allocation_id !== null, 409, __('Edit the dated teaching allocation instead.'));

            if ($subjectTeacher->isForceDeleting()) {
                $subjectTeacher->timetables()->forceDelete();
                $subjectTeacher->announcement()->withTrashed()->get()->each->forceDelete();
            } else {
                $subjectTeacher->timetables()->delete();
                $subjectTeacher->announcement()->get()->each->delete();
            }
        });
    }
}
