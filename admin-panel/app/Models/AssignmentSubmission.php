<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class AssignmentSubmission extends Model
{
    use HasFactory, SoftDeletes;

    public function file()
    {
        return $this->morphMany(File::class, 'modal');
    }

    public function assignment()
    {
        return $this->belongsTo(Assignment::class);
    }

    public function student()
    {
        return $this->belongsTo(Students::class);
    }

    /** For a teacher, only submissions to their own assignments (see Assignment::scopeAssignmentTeachers). */
    public function scopeAssignmentSubmissionTeachers($query)
    {
        return $query->whereHas('assignment', fn ($assignment) => $assignment->assignmentTeachers());
    }

    protected static function booted()
    {
        static::deleting(function ($submission) {

            foreach ($submission->file as $file) {
                if (Storage::disk('public')->exists($file->file_url)) {
                    Storage::disk('public')->delete($file->file_url);
                }
            }

            if ($submission->isForceDeleting()) {
                $submission->file()->forceDelete();
            } else {
                $submission->file()->delete();
            }
        });
    }
}
