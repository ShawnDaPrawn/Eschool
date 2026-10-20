<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class LessonTopic extends Model
{
    use HasFactory;

    protected static function boot() {
        parent::boot();
        static::deleting(function ($topic) { // before delete() method call this
            if ($topic->file) {
                foreach ($topic->file as $file) {
                    if (Storage::disk('public')->exists($file->file_url)) {
                        Storage::disk('public')->delete($file->file_url);
                    }
                }
                $topic->file()->delete();
            }
        });
    }

    protected $hidden = ["deleted_at", "created_at", "updated_at"];

    public function file() {
        return $this->morphMany(File::class, 'modal');
    }

    public function lesson() {
        return $this->belongsTo(Lesson::class);
    }

    /** For a teacher, only topics of their own lessons (see Lesson::scopeLessonTeachers). */
    public function scopeLessonTopicTeachers($query) {
        return $query->whereHas('lesson', fn ($lesson) => $lesson->lessonTeachers());
    }
}
