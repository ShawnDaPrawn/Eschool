<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\Academics\ExamMode;
use App\Services\Academics\MarkExamProjection;
use App\Services\Academics\ProtectsMarkProjection;
use App\Services\Platform\Tenancy\BelongsToSchool;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExamTimetable extends Model
{
    use BelongsToSchool, HasFactory, ProtectsMarkProjection, SoftDeletes;

    // revision only numbers change notices; the apps never see it.
    protected $hidden = ['deleted_at', 'created_at', 'updated_at', 'revision'];

    /**
     * Both apps read the times as starting_time / ending_time (the student
     * exam-details endpoint renamed them by hand; the others didn't).
     */
    protected $appends = ['starting_time', 'ending_time'];

    /**
     * The date, time or venue changes found while saving (ExamMode::changes),
     * announced once the row is written. Not a column.
     *
     * @var array<string, array{0: string|null, 1: string|null}>
     */
    public array $examChanges = [];

    /**
     * Exam mode (Action Plan 1.8): a change to the date, time or venue raises
     * the revision and tells the class and its guardians; so does removing an
     * upcoming sitting. Mark projection writes are not news.
     */
    protected static function booted(): void
    {
        $quiet = static fn (): bool => MarkExamProjection::$writing || app(CurrentSchool::class)->isMigrating() || ! ExamMode::ready();
        static::updating(function (ExamTimetable $sitting) use ($quiet): void {
            if ($quiet()) {
                return;
            }
            $sitting->examChanges = app(ExamMode::class)->changes($sitting);
            if ($sitting->examChanges !== []) {
                $sitting->revision = (int) ($sitting->getRawOriginal('revision') ?? 1) + 1;
            }
        });
        static::updated(function (ExamTimetable $sitting): void {
            $changes = $sitting->examChanges;
            $sitting->examChanges = [];
            if ($changes !== []) {
                app(ExamMode::class)->changed($sitting, $changes);
            }
        });
        static::deleted(function (ExamTimetable $sitting) use ($quiet): void {
            if (! $quiet()) {
                app(ExamMode::class)->cancelled($sitting);
            }
        });
    }

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
