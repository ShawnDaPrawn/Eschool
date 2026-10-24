<?php

declare(strict_types=1);

namespace App\Services\Academics;

use App\Models\Exam;
use App\Models\ExamMarks;
use App\Models\ExamTimetable;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

/** Model protection also covers old delete cascades and correction screens. */
trait ProtectsMarkProjection
{
    protected static function bootProtectsMarkProjection(): void
    {
        $guard = static function (Model $model) {
            if (MarkExamProjection::$writing || app(CurrentSchool::class)->isMigrating() || ! Schema::hasTable('academic_exam_links')) {
                return;
            }
            $examId = $model instanceof Exam ? ($model->exists ? $model->id : null)
                : ($model instanceof ExamMarks ? ExamTimetable::whereKey($model->exam_timetable_id)->value('exam_id') : $model->exam_id);
            if ($examId !== null) {
                app(MarkExamProjection::class)->assertManual((int) $examId);
            }
        };
        static::saving($guard);
        static::deleting($guard);
    }
}
