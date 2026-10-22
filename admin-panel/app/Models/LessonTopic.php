<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LessonTopic extends Model
{
    use HasFactory;

    protected static function boot()
    {
        parent::boot();
        static::deleting(function ($topic) { // before delete() method call this
            // One by one, so File removes the stored files too.
            $topic->file->each->delete();
        });
    }

    protected $hidden = ['deleted_at', 'created_at', 'updated_at'];

    public function file()
    {
        return $this->morphMany(File::class, 'modal');
    }

    public function lesson()
    {
        return $this->belongsTo(Lesson::class);
    }

    /** For a teacher, only topics of their own lessons (see Lesson::scopeLessonTeachers). */
    public function scopeLessonTopicTeachers($query)
    {
        return $query->whereHas('lesson', fn ($lesson) => $lesson->lessonTeachers());
    }
}
