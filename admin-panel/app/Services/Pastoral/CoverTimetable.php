<?php

declare(strict_types=1);

namespace App\Services\Pastoral;

use App\Models\Academics\BellPeriod;
use App\Models\Academics\TimetableLesson;
use App\Models\Pastoral\CoverAbsence;
use App\Models\Pastoral\CoverAssignment;
use App\Models\Teacher;
use App\Services\Academics\TimetableEngine;
use App\Services\Platform\Modules\ModuleGate;

class CoverTimetable
{
    public function enabled(): bool
    {
        $gate = app(ModuleGate::class);
        $gate->forget();

        return $gate->allows('relief_cover');
    }

    public function assigned(TimetableLesson $lesson, string $date): ?CoverAssignment
    {
        if (! $this->enabled()) {
            return null;
        }
        foreach (CoverAssignment::where('timetable_lesson_id', $lesson->id)->whereDate('on_date', $date)->where('state', 'assigned')->get() as $cover) {
            if (app(CoverSources::class)->valid($cover)) {
                return $cover;
            }
        }

        return null;
    }

    /**
     * The substitute holding an assigned cover for the occurrence, whether or
     * not its snapshot is still current: a stale assignment keeps its teacher
     * reserved until someone refreshes or cancels it.
     */
    public function reserved(TimetableLesson $lesson, string $date): ?int
    {
        if (! $this->enabled()) {
            return null;
        }
        $id = CoverAssignment::where('timetable_lesson_id', $lesson->id)->whereDate('on_date', $date)->where('state', 'assigned')
            ->whereNotNull('substitute_teacher_id')->whereIn('cover_absence_id', CoverAbsence::where('state', 'requested')->select('id'))
            ->orderBy('id')->value('substitute_teacher_id');

        return $id === null ? null : (int) $id;
    }

    public function display(array $row, CoverAssignment $cover): array
    {
        $teacher = Teacher::with('user')->findOrFail($cover->substitute_teacher_id);

        return array_replace($row, ['cover_id' => (int) $cover->id, 'original_teacher_id' => $row['teacher_id'], 'original_teacher_name' => $row['teacher_name'],
            'teacher_id' => (int) $teacher->id, 'teacher_name' => $teacher->user->full_name]);
    }

    /** Stale assignments reserve their original and current times until explicit review. */
    public function clashes(array $row, int $index, TimetableEngine $engine): array
    {
        if (! $this->enabled() || ! $row['active']) {
            return [];
        }
        $dates = $engine->dates($row);
        $period = BellPeriod::findOrFail($row['bell_period_id']);
        foreach (CoverAssignment::where('state', 'assigned')->where('substitute_teacher_id', $row['teacher_id'])->whereIn('on_date', $dates)->get() as $cover) {
            $snapshot = $cover->source_snapshot;
            $overlap = ($snapshot['starts_at'] ?? '') < substr($period->ends_at, 0, 5) && substr($period->starts_at, 0, 5) < ($snapshot['ends_at'] ?? '');
            $lesson = TimetableLesson::find($cover->timetable_lesson_id);
            if ($lesson !== null) {
                $candidate = app(CoverAvailability::class)->candidate((int) $cover->substitute_teacher_id, $lesson, $cover->on_date->toDateString());
                $overlap = $overlap || $engine->clashes($row, $candidate, $index) !== [];
            }
            if ($overlap) {
                return [['row' => $index, 'lesson_id' => (int) $cover->timetable_lesson_id, 'date' => $cover->on_date->toDateString(), 'reasons' => ['teacher']]];
            }
        }

        return [];
    }
}
