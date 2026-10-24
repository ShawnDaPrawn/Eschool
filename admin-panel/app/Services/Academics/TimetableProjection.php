<?php

declare(strict_types=1);

namespace App\Services\Academics;

use App\Models\Academics\BellPeriod;
use App\Models\Academics\TeachingGroup;
use App\Models\Academics\TimetableLesson;
use App\Models\SessionYear;
use App\Models\Term;
use App\Models\Timetable;
use App\Services\Platform\Tenancy\SchoolTable;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

/** Current-week v1 rows retain existing field types and stable projection IDs. */
class TimetableProjection
{
    private array $legacyYears = [];

    private array $legacyTerms = [];

    public function refresh(): void
    {
        app(AcademicProjections::class)->refresh();
        $monday = CarbonImmutable::parse(app(AcademicAuthority::class)->today())->startOfWeek()->toDateString();
        $sunday = CarbonImmutable::parse($monday)->addDays(6)->toDateString();
        $keep = [];
        $engine = app(TimetableEngine::class);
        foreach (TimetableLesson::with('group', 'period', 'room')->where('active', true)->get() as $lesson) {
            $dates = array_filter($engine->dates($lesson->getAttributes()), fn ($date) => $date >= $monday && $date <= $sunday);
            if ($dates === [] || $lesson->group === null || $lesson->group->class_section_id === null || ! $lesson->group->active) {
                continue;
            }
            $subject = SchoolTable::query('subject_teachers')->where('class_section_id', $lesson->group->class_section_id)
                ->where('subject_id', $lesson->group->subject_id)->where('teacher_id', $lesson->teacher_id)->where('session_year_id', $lesson->session_year_id)
                ->whereNull('deleted_at')->orderBy('id')->value('id');
            if ($subject === null) {
                continue;
            }
            $values = ['class_section_id' => $lesson->group->class_section_id, 'subject_teacher_id' => $subject,
                'session_year_id' => $lesson->session_year_id, 'term_id' => $lesson->term_id, 'room_id' => $lesson->room_id, 'room_name' => $lesson->room->name,
                'day' => $lesson->weekday, 'day_name' => strtolower(CarbonImmutable::parse(array_values($dates)[0])->format('l')),
                'start_time' => substr($lesson->period->starts_at, 0, 5), 'end_time' => substr($lesson->period->ends_at, 0, 5), 'deleted_at' => null, 'updated_at' => now()];
            $id = SchoolTable::query('timetables')->where('timetable_lesson_id', $lesson->id)->value('id');
            if ($id === null) {
                $id = SchoolTable::query('timetables')->insertGetId($values + ['timetable_lesson_id' => $lesson->id, 'created_at' => now(), 'live_class_url' => null, 'link_name' => null]);
            } else {
                SchoolTable::query('timetables')->where('id', $id)->update($values);
            }
            $keep[] = (int) $id;
        }
        SchoolTable::query('timetables')->whereNotNull('timetable_lesson_id')->whereNotIn('id', $keep)->whereNull('deleted_at')->update(['deleted_at' => now(), 'updated_at' => now()]);
    }

    public function legacyEvidence(): array
    {
        return SchoolTable::query('timetables')->whereNull('timetable_lesson_id')->whereNull('deleted_at')->orderBy('id')->get()->map(fn ($row) => (array) $row)->all();
    }

    public function legacyClashes(array $lesson, int $row, ?TimetableEngine $engine = null, ?array $legacyRows = null): array
    {
        if (! $lesson['active']) {
            return [];
        }
        $period = BellPeriod::findOrFail($lesson['bell_period_id']);
        $group = $lesson['teaching_group_id'] === null ? null : TeachingGroup::findOrFail($lesson['teaching_group_id']);
        $homeroom = $lesson['class_section_id'] ?? $group?->class_section_id;
        $result = [];
        $engine ??= app(TimetableEngine::class);
        foreach ($legacyRows ?? $this->legacyEvidence() as $legacy) {
            if ((int) $legacy['day'] !== $lesson['weekday']) {
                continue;
            }
            $subject = SchoolTable::query('subject_teachers')->where('id', $legacy['subject_teacher_id'])->first();
            $sameTeacher = $subject !== null && (int) $subject->teacher_id === $lesson['teacher_id'];
            $sameClass = $homeroom !== null && (int) $legacy['class_section_id'] === (int) $homeroom;
            $sameRoom = ($legacy['room_id'] ?? null) !== null && (int) $legacy['room_id'] === $lesson['room_id'];
            try {
                $start = CarbonImmutable::parse($legacy['start_time'])->format('H:i:s');
                $end = CarbonImmutable::parse($legacy['end_time'])->format('H:i:s');
            } catch (\Throwable) {
                if ($sameTeacher || $sameClass || $sameRoom) {
                    $result[] = ['row' => $row, 'lesson_id' => null, 'date' => null, 'reasons' => ['legacy_time_needs_review']];
                }

                continue;
            }
            if ($start >= $period->ends_at || $period->starts_at >= $end) {
                continue;
            }
            foreach ($engine->dates($lesson) as $date) {
                if (! $this->legacyOn($legacy, $date)) {
                    continue;
                }
                $reasons = [];
                if ($sameTeacher) {
                    $reasons[] = 'teacher';
                }
                if ($sameClass) {
                    $reasons[] = 'homeroom';
                }
                if ($sameRoom) {
                    $reasons[] = 'room';
                }
                $legacyLesson = ['teaching_group_id' => null, 'class_section_id' => $legacy['class_section_id'], 'session_year_id' => $legacy['session_year_id']];
                if (array_intersect($engine->learners($lesson, $date), $engine->learners($legacyLesson, $date)) !== []) {
                    $reasons[] = 'learner';
                }
                if ($reasons !== []) {
                    $result[] = ['row' => $row, 'lesson_id' => null, 'date' => $date, 'reasons' => $reasons];
                    break;
                }
            }
        }

        return $result;
    }

    public function guardLegacy(Model $model): void
    {
        $id = $model->getKey();
        $source = $model->getOriginal('timetable_lesson_id') ?? $model->getAttribute('timetable_lesson_id');
        if ($source === null && $id !== null) {
            $source = SchoolTable::query('timetables')->where('id', $id)->value('timetable_lesson_id');
        }
        if ($source !== null) {
            abort_if(array_diff(array_keys($model->getDirty()), ['live_class_url', 'link_name', 'updated_at']) !== [], 409, __('Edit this lesson in the academic timetable.'));

            return;
        }
        // Old writers may not place a competing class/teacher over the canonical
        // timetable. The editor's full candidate validation handles canonical writes.
        if (! $model->isDirty(['start_time', 'end_time', 'day', 'subject_teacher_id', 'class_section_id', 'session_year_id'])) {
            return;
        }
        $subject = SchoolTable::query('subject_teachers')->where('id', $model->subject_teacher_id)->first();
        foreach (TimetableLesson::with('period', 'group')->where('active', true)->where('weekday', $model->day)->get() as $lesson) {
            $same = ($subject !== null && (int) $subject->teacher_id === (int) $lesson->teacher_id)
                || (int) ($lesson->class_section_id ?? $lesson->group?->class_section_id ?? 0) === (int) $model->class_section_id;
            if (! $same) {
                continue;
            }
            $start = CarbonImmutable::parse($model->start_time)->format('H:i:s');
            $end = CarbonImmutable::parse($model->end_time)->format('H:i:s');
            if ($start < $lesson->period->ends_at && $lesson->period->starts_at < $end) {
                foreach (app(TimetableEngine::class)->dates($lesson->getAttributes()) as $date) {
                    abort_if($this->legacyOn($model->getAttributes(), $date), 409, __('This legacy placement clashes with a canonical academic lesson.'));
                }
            }
        }
    }

    private function legacyOn(array $row, string $date): bool
    {
        $year = $this->legacyYears[$row['session_year_id']] ??= SessionYear::withTrashed()->find($row['session_year_id']);
        if ($year === null || $date < $year->start_date || $date > $year->end_date) {
            return false;
        }
        if (($row['term_id'] ?? null) === null) {
            return true;
        }
        $term = $this->legacyTerms[$row['term_id']] ??= Term::withTrashed()->find($row['term_id']);

        return $term !== null && $date >= $term->start_date->toDateString() && $date <= $term->end_date->toDateString();
    }
}
