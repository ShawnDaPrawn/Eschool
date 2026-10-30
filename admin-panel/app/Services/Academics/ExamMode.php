<?php

declare(strict_types=1);

namespace App\Services\Academics;

use App\Models\Academics\ExamPeriod;
use App\Models\Academics\GradeLevel;
use App\Models\ClassSection;
use App\Models\ExamTimetable;
use App\Models\People\Enrolment;
use App\Models\SessionYear;
use App\Models\Students;
use App\Models\User;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Identity\GuardianService;
use App\Services\Platform\Notifications\Notifier;
use App\Services\Platform\Tenancy\CurrentSchool;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

/**
 * Exam mode (Action Plan 1.8). A school sets exam periods per grade; on those
 * days a learner's week (TimetableQueries::week) lists the day as an exam day
 * and leaves normal lessons out unless the period keeps them. Each exam
 * sitting has a venue and a revision; a change to its date, time or venue, or
 * its removal, tells the class's learners and their current guardians through
 * the notification outbox (academics.exam_changed).
 * engineering/specs/academics/exam-mode.md.
 */
class ExamMode
{
    public const NOTICE = 'academics.exam_changed';

    /** The sitting fields whose change is news to families. */
    public const WATCHED = ['date', 'start_time', 'end_time', 'venue'];

    private static ?bool $ready = null;

    /** Whether the exam mode tables exist (early migrations write exam rows too). */
    public static function ready(): bool
    {
        return self::$ready ??= Schema::hasTable('exam_periods') && Schema::hasColumn('exam_timetables', 'venue');
    }

    /** @return list<array<string, mixed>> */
    public function periods(int $yearId): array
    {
        $grades = GradeLevel::query()->pluck('name', 'id');

        return ExamPeriod::query()->where('session_year_id', $yearId)->orderBy('starts_on')->orderBy('grade_level_id')->get()
            ->map(fn (ExamPeriod $period) => [
                'id' => $period->id,
                'name' => $period->name,
                'grade_level_id' => $period->grade_level_id,
                'grade' => $grades[$period->grade_level_id] ?? '',
                'starts_on' => $period->starts_on->toDateString(),
                'ends_on' => $period->ends_on->toDateString(),
                'lessons_continue' => $period->lessons_continue,
            ])->all();
    }

    /** @param  array{name: string, grade_level_id: int, starts_on: string, ends_on: string, lessons_continue?: bool}  $data */
    public function save(User $actor, int $yearId, array $data, ?ExamPeriod $period = null): ExamPeriod
    {
        $year = SessionYear::query()->findOrFail($yearId);
        $starts = CarbonImmutable::parse($data['starts_on'])->toDateString();
        $ends = CarbonImmutable::parse($data['ends_on'])->toDateString();
        if ($ends < $starts) {
            throw ValidationException::withMessages(['ends_on' => __('The exam period must end on or after its first day.')]);
        }
        if ($starts < CarbonImmutable::parse($year->start_date)->toDateString() || $ends > CarbonImmutable::parse($year->end_date)->toDateString()) {
            throw ValidationException::withMessages(['starts_on' => __('The exam period must fall inside the school year.')]);
        }
        $overlap = ExamPeriod::query()->where('session_year_id', $yearId)->where('grade_level_id', $data['grade_level_id'])
            ->when($period !== null, fn ($query) => $query->whereKeyNot($period->id))
            ->whereDate('starts_on', '<=', $ends)->whereDate('ends_on', '>=', $starts)->exists();
        if ($overlap) {
            throw ValidationException::withMessages(['starts_on' => __('This grade already has an exam period on some of these days.')]);
        }

        return DB::transaction(function () use ($actor, $yearId, $data, $period, $starts, $ends): ExamPeriod {
            $period ??= new ExamPeriod(['session_year_id' => $yearId, 'created_by' => $actor->id]);
            $period->fill([
                'name' => $data['name'],
                'grade_level_id' => (int) $data['grade_level_id'],
                'starts_on' => $starts,
                'ends_on' => $ends,
                'lessons_continue' => (bool) ($data['lessons_continue'] ?? false),
            ])->save();
            Audit::record('academics.exam_period_saved', $period, [], ['name' => $period->name, 'grade_level_id' => $period->grade_level_id, 'starts_on' => $starts, 'ends_on' => $ends]);

            return $period;
        });
    }

    public function remove(ExamPeriod $period): void
    {
        DB::transaction(function () use ($period): void {
            Audit::record('academics.exam_period_removed', $period, ['name' => $period->name, 'starts_on' => $period->starts_on->toDateString(), 'ends_on' => $period->ends_on->toDateString()]);
            $period->delete();
        });
    }

    /**
     * The learner's exam days between two dates, each with whether normal
     * lessons continue that day. The grade is the learner's class's on each day.
     *
     * @return array<string, bool>
     */
    public function days(int $learnerId, int $yearId, string $from, string $to): array
    {
        if (! self::ready()) {
            return [];
        }
        $periods = ExamPeriod::query()->where('session_year_id', $yearId)->whereDate('starts_on', '<=', $to)->whereDate('ends_on', '>=', $from)->get();
        if ($periods->isEmpty()) {
            return [];
        }
        $enrolments = Enrolment::query()->with('homeroom.class')->where('learner_id', $learnerId)->where('session_year_id', $yearId)
            ->whereDate('starts_on', '<=', $to)->where(fn ($query) => $query->whereNull('ends_on')->orWhereDate('ends_on', '>=', $from))->get();
        $days = [];
        for ($date = CarbonImmutable::parse($from); $date->toDateString() <= $to; $date = $date->addDay()) {
            $day = $date->toDateString();
            $enrolment = $enrolments->first(fn ($row) => $row->starts_on->toDateString() <= $day && ($row->ends_on === null || $row->ends_on->toDateString() >= $day));
            $grade = $enrolment?->homeroom?->class?->grade_level_id;
            if ($grade === null) {
                continue;
            }
            foreach ($periods as $period) {
                if ((int) $period->grade_level_id === (int) $grade && $period->starts_on->toDateString() <= $day && $period->ends_on->toDateString() >= $day) {
                    $days[$day] = ($days[$day] ?? false) || $period->lessons_continue;
                }
            }
        }

        return $days;
    }

    /**
     * Before a sitting is saved: the watched values that really changed
     * (times compared as HH:MM, so "09:00" and "09:00:00" are the same).
     *
     * @return array<string, array{0: string|null, 1: string|null}>
     */
    public function changes(ExamTimetable $sitting): array
    {
        $changes = [];
        foreach (self::WATCHED as $field) {
            $before = $this->normal($field, $sitting->getRawOriginal($field));
            $after = $this->normal($field, $sitting->getAttributes()[$field] ?? null);
            if ($before !== $after) {
                $changes[$field] = [$before, $after];
            }
        }

        return $changes;
    }

    /** @param  array<string, array{0: string|null, 1: string|null}>  $changes */
    public function changed(ExamTimetable $sitting, array $changes): void
    {
        $old = $changes['date'][0] ?? $this->normal('date', $sitting->date);
        if (! $this->upcoming($old) && ! $this->upcoming($this->normal('date', $sitting->date))) {
            return;
        }
        $subject = $this->subject($sitting);
        $when = $this->when($sitting);
        $notifier = app(Notifier::class);
        $notice = $notifier->makeNotice(self::NOTICE, __('Exam changed: :subject', ['subject' => $subject]), __(':subject is now on :when.', ['subject' => $subject, 'when' => $when]), '/exams');
        $notifier->assign($notice, $this->recipients($sitting));
    }

    public function cancelled(ExamTimetable $sitting): void
    {
        if (! $this->upcoming($this->normal('date', $sitting->getRawOriginal('date') ?? $sitting->date))) {
            return;
        }
        $subject = $this->subject($sitting);
        $notifier = app(Notifier::class);
        $notice = $notifier->makeNotice(self::NOTICE, __('Exam removed: :subject', ['subject' => $subject]), __('The :subject sitting on :date was removed from the exam timetable.', ['subject' => $subject, 'date' => CarbonImmutable::parse($sitting->date)->format('j M Y')]), '/exams');
        $notifier->assign($notice, $this->recipients($sitting));
    }

    /**
     * Learners in the sitting's class today, and their current guardians.
     *
     * @return list<int>
     */
    public function recipients(ExamTimetable $sitting): array
    {
        $today = $this->today();
        $sections = ClassSection::query()->where('class_id', $sitting->class_id)->pluck('id');
        $learners = Enrolment::query()->whereIn('class_section_id', $sections)->where('session_year_id', $sitting->session_year_id)
            ->whereDate('starts_on', '<=', $today)->where(fn ($query) => $query->whereNull('ends_on')->orWhereDate('ends_on', '>=', $today))
            ->pluck('learner_id')->unique();
        $users = [];
        foreach (Students::query()->whereIn('id', $learners)->get() as $learner) {
            $users[] = (int) $learner->user_id;
            array_push($users, ...app(GuardianService::class)->guardianUserIds($learner)->all());
        }

        return array_values(array_unique(array_filter($users)));
    }

    private function subject(ExamTimetable $sitting): string
    {
        return (string) ($sitting->subject?->name ?? __('an exam'));
    }

    private function when(ExamTimetable $sitting): string
    {
        $text = CarbonImmutable::parse($sitting->date)->format('j M Y').', '.$this->normal('start_time', $sitting->start_time).'–'.$this->normal('end_time', $sitting->end_time);

        return $sitting->venue ? $text.', '.$sitting->venue : $text;
    }

    private function upcoming(?string $date): bool
    {
        return $date !== null && $date >= $this->today();
    }

    private function today(): string
    {
        return CarbonImmutable::now(app(CurrentSchool::class)->school()?->timezone ?? 'Africa/Johannesburg')->toDateString();
    }

    private function normal(string $field, mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        $value = $value instanceof \DateTimeInterface ? $value->format('Y-m-d H:i:s') : trim((string) $value);

        return match ($field) {
            'date' => date('Y-m-d', strtotime($value) ?: 0),
            'start_time', 'end_time' => date('H:i', strtotime($value) ?: 0),
            default => $value,
        };
    }
}
