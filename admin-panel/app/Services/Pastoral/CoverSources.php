<?php

declare(strict_types=1);

namespace App\Services\Pastoral;

use App\Models\Academics\HomeroomAllocation;
use App\Models\Academics\TeachingAllocation;
use App\Models\Academics\TimetableLesson;
use App\Models\Pastoral\CoverAbsence;
use App\Models\Pastoral\CoverAssignment;
use App\Services\Academics\AcademicAuthority;
use App\Services\Academics\TimetableEngine;
use App\Services\Academics\TimetableQueries;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

class CoverSources
{
    /** Immutable, deliberately narrow source snapshot; never includes roster or absence reason. */
    public function descriptor(TimetableLesson $lesson, string $date): ?array
    {
        $lesson->load('period.schedule', 'room', 'group', 'homeroom');
        if (! $lesson->active || $lesson->period === null || $lesson->period->schedule === null
            || (int) $lesson->period->schedule->session_year_id !== (int) $lesson->session_year_id
            || $lesson->period->is_break || $lesson->room?->active !== true
            || ! in_array($date, app(TimetableEngine::class)->dates($lesson->getAttributes()), true)) {
            return null;
        }
        try {
            app(CoverAuthority::class)->teacher((int) $lesson->teacher_id, $date);
        } catch (ModelNotFoundException|HttpException) {
            return null;
        }
        if ($lesson->teaching_group_id !== null) {
            if ($lesson->group?->active !== true || (int) $lesson->group->session_year_id !== (int) $lesson->session_year_id
                || ! TeachingAllocation::where('teaching_group_id', $lesson->teaching_group_id)->where('teacher_id', $lesson->teacher_id)
                    ->whereDate('starts_on', '<=', $date)->whereDate('ends_on', '>=', $date)->exists()) {
                return null;
            }
        } elseif ($lesson->homeroom === null || ! HomeroomAllocation::where('class_section_id', $lesson->class_section_id)
            ->where('teacher_id', $lesson->teacher_id)->where('session_year_id', $lesson->session_year_id)
            ->whereDate('starts_on', '<=', $date)->whereDate('ends_on', '>=', $date)->exists()) {
            return null;
        }
        $view = app(TimetableQueries::class)->data($lesson, $date);

        return ['lesson_id' => (int) $lesson->id, 'lesson_revision' => $lesson->revision,
            'session_year_id' => (int) $lesson->session_year_id, 'teacher_id' => (int) $lesson->teacher_id,
            'teacher_name' => $view['teacher_name'],
            'group_id' => $lesson->teaching_group_id, 'homeroom_id' => $lesson->class_section_id,
            'title' => $view['title'], 'subject_name' => $view['subject_name'], 'room_id' => (int) $lesson->room_id,
            'room_name' => $view['room_name'], 'period_id' => (int) $lesson->bell_period_id,
            'starts_at' => $view['starts_at'], 'ends_at' => $view['ends_at'], 'on_date' => $date];
    }

    public function hash(array $source): string
    {
        return hash('sha256', json_encode($source, JSON_THROW_ON_ERROR));
    }

    public function valid(CoverAssignment $cover): bool
    {
        $absence = CoverAbsence::find($cover->cover_absence_id);
        $lesson = TimetableLesson::find($cover->timetable_lesson_id);
        if ($absence?->state !== 'requested' || $lesson === null || (int) $lesson->teacher_id !== (int) $absence->teacher_id) {
            return false;
        }
        $date = $cover->on_date->toDateString();
        $source = $this->descriptor($lesson, $date);
        if ($source === null || ! hash_equals($cover->source_hash, $this->hash($source))) {
            return false;
        }
        if ($cover->substitute_teacher_id !== null) {
            try {
                app(CoverAuthority::class)->teacher((int) $cover->substitute_teacher_id, $date);
            } catch (ModelNotFoundException|HttpException) {
                return false;
            }
            if (CoverAbsence::where('teacher_id', $cover->substitute_teacher_id)->where('state', 'requested')
                ->whereDate('starts_on', '<=', $date)->whereDate('ends_on', '>=', $date)->exists()
                || app(CoverAvailability::class)->ownBusy((int) $cover->substitute_teacher_id, $lesson, $date)) {
                return false;
            }
        }

        return true;
    }

    public function map(CoverAbsence $absence): void
    {
        $today = app(AcademicAuthority::class)->today();
        $seen = CoverAssignment::where('cover_absence_id', $absence->id)->whereDate('on_date', '<', $today)->pluck('id')->all();
        $engine = app(TimetableEngine::class);
        $lessons = TimetableLesson::where('session_year_id', $absence->session_year_id)->where('teacher_id', $absence->teacher_id)
            ->where('active', true)->whereDate('starts_on', '<=', $absence->ends_on)->whereDate('ends_on', '>=', $absence->starts_on)->get();
        foreach ($lessons as $lesson) {
            foreach ($engine->dates($lesson->getAttributes()) as $date) {
                if ($date < $today || $date < $absence->starts_on->toDateString() || $date > $absence->ends_on->toDateString()) {
                    continue;
                }
                $source = $this->descriptor($lesson, $date);
                abort_if($source === null, 409, 'An affected timetable lesson needs review before requesting cover.');
                $cover = CoverAssignment::firstOrNew(['cover_absence_id' => $absence->id, 'timetable_lesson_id' => $lesson->id, 'on_date' => $date]);
                $hash = $this->hash($source);
                if (! $cover->exists || ! hash_equals($cover->source_hash, $hash) || $cover->state === 'cancelled'
                    || ($cover->state === 'assigned' && ! $this->valid($cover))) {
                    $cover->fill(['source_hash' => $hash, 'source_snapshot' => $source, 'state' => 'open', 'substitute_teacher_id' => null,
                        'revision' => $cover->exists ? $cover->revision + 1 : 1])->save();
                }
                $seen[] = $cover->id;
                abort_if(count($seen) > 500, 422, 'Split this absence into smaller date ranges.');
            }
        }
        CoverAssignment::where('cover_absence_id', $absence->id)->whereNotIn('id', $seen)->where('state', '!=', 'cancelled')
            ->update(['state' => 'cancelled', 'substitute_teacher_id' => null, 'revision' => DB::raw('revision + 1'), 'updated_at' => now()]);
    }
}
