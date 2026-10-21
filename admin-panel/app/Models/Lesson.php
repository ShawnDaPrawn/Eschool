<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class Lesson extends Model
{
    use HasFactory, SoftDeletes;

    protected $hidden = ["deleted_at", "created_at", "updated_at"];

    protected static function boot()
    {
        parent::boot();
        static::deleting(function ($lesson) { // before delete() method call this
            // One by one, so File and LessonTopic remove their stored files too.
            $lesson->file->each->delete();
            $lesson->topic->each->delete();
        });
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class)->withTrashed();
    }

    public function class_section()
    {
        return $this->belongsTo(ClassSection::class)->with('class', 'section');
    }

    public function file()
    {
        return $this->morphMany(File::class, 'modal');
    }

    public function topic()
    {
        return $this->hasMany(LessonTopic::class);
    }

    public function semester()
    {
        return $this->belongsTo(Semester::class);
    }

    /**
     * For a teacher, only lessons of a class section + subject pair they teach
     * (matched as a pair: teaching Maths in 5A and Science in 5B gives no access
     * to Science in 5A). Everyone else sees all lessons.
     */
    public function scopeLessonTeachers($query)
    {
        $user = Auth::user();
        if (! $user->hasRole('Teacher')) {
            return $query;
        }
        $teacherId = $user->teacher()->value('id');

        return $query->whereExists(function ($subQuery) use ($teacherId) {
            $subQuery->selectRaw('1')
                ->from('subject_teachers')
                ->whereColumn('subject_teachers.class_section_id', 'lessons.class_section_id')
                ->whereColumn('subject_teachers.subject_id', 'lessons.subject_id')
                ->where('subject_teachers.teacher_id', $teacherId)
                ->whereNull('subject_teachers.deleted_at');
        });
    }
}
