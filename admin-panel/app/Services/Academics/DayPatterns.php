<?php

declare(strict_types=1);

namespace App\Services\Academics;

use App\Models\Academics\AttendanceRegister;
use App\Models\Academics\BellPeriod;
use App\Models\Academics\BellSchedule;
use App\Models\Academics\BellScheduleOverride;
use App\Models\Academics\TimetableLesson;
use App\Models\SessionYear;
use App\Models\Students;
use App\Models\Teacher;
use App\Models\User;
use App\Services\Pastoral\CoverTimetable;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Events\AcademicTimetableChanged;
use App\Services\Platform\Events\Outbox;
use App\Services\Platform\Identity\GuardianService;
use App\Services\Platform\Notifications\Notifier;
use App\Services\Platform\Tenancy\CurrentSchool;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Short days. On a date, a bell schedule can be replaced by another one. A
 * lesson keeps its placement and follows the replacement's teaching period in
 * the same order (the third lesson of the day takes the third short-day
 * period); a lesson beyond the replacement's last teaching period does not
 * take place that day. Breaks never count as teaching periods.
 *
 * The timetable engine asks this service whether an occurrence takes place
 * and at what time, so registers, cover, clash checks and the week view all
 * agree. Specification: engineering/specs/academics/short-days.md.
 */
class DayPatterns
{
    /** @var array<string, array<int, BellScheduleOverride>>|null date => replaced schedule id => override */
    private ?array $overrides = null;

    /** @var array<int, true> */
    private array $replaced = [];

    /** Set once the table exists, so occurrence checks stop asking the schema. */
    private static bool $ready = false;

    /** @var array<int, ?BellPeriod> */
    private array $periods = [];

    /** @var array<int, list<BellPeriod>> */
    private array $teaching = [];

    public static function rules(): array
    {
        return ['on_date' => ['required', 'date_format:Y-m-d'], 'reason' => ['required', 'string', 'max:191'],
            'replacements' => ['required', 'array', 'min:1', 'max:30'],
            'replacements.*.bell_schedule_id' => ['required', 'integer', 'min:1'],
            'replacements.*.replacement_bell_schedule_id' => ['nullable', 'integer', 'min:1']];
    }

    public function forget(): void
    {
        $this->overrides = null;
        $this->replaced = $this->periods = $this->teaching = [];
    }

    private function load(): array
    {
        if ($this->overrides === null) {
            $this->overrides = $this->replaced = [];
            if (! self::$ready && ! (self::$ready = Schema::hasTable('bell_schedule_overrides'))) {
                return $this->overrides;
            }
            foreach (BellScheduleOverride::with('replacement')->orderBy('id')->get() as $row) {
                $this->overrides[$row->on_date->toDateString()][(int) $row->bell_schedule_id] = $row;
                $this->replaced[(int) $row->bell_schedule_id] = true;
            }
        }

        return $this->overrides;
    }

    /** Whether any date replaces this schedule: lets clash checks keep their fast path. */
    public function replaces(int $scheduleId): bool
    {
        $this->load();

        return isset($this->replaced[$scheduleId]);
    }

    public function override(int $scheduleId, string $date): ?BellScheduleOverride
    {
        return $this->load()[$date][$scheduleId] ?? null;
    }

    /** @return list<BellScheduleOverride> */
    public function on(string $date): array
    {
        return array_values($this->load()[$date] ?? []);
    }

    /** The period a lesson placed in $periodId follows on $date, or null when it does not take place. */
    public function period(int $periodId, string $date): ?BellPeriod
    {
        $period = $this->find($periodId);
        if ($period === null) {
            return null;
        }
        $override = $this->override((int) $period->bell_schedule_id, $date);
        if ($override === null) {
            return $period;
        }
        $ordinal = array_search($periodId, array_map(fn ($row) => (int) $row->id, $this->teaching((int) $period->bell_schedule_id)), true);

        return $ordinal === false ? null : ($this->teaching((int) $override->replacement_bell_schedule_id)[$ordinal] ?? null);
    }

    public function takesPlace(int $periodId, string $date): bool
    {
        if (! isset($this->load()[$date])) {
            return true;
        }

        return $this->period($periodId, $date) !== null;
    }

    /** Adjusts a week row to the short-day period, labelled with the replacement schedule's name. */
    public function display(array $row, int $periodId, string $date): array
    {
        $period = $this->find($periodId);
        $override = $period === null ? null : $this->override((int) $period->bell_schedule_id, $date);
        $moved = $override === null ? null : $this->period($periodId, $date);
        if ($moved === null) {
            return $row;
        }

        return array_replace($row, ['period_name' => $moved->name, 'starts_at' => substr((string) $moved->starts_at, 0, 5),
            'ends_at' => substr((string) $moved->ends_at, 0, 5), 'day_pattern' => $override->replacement?->name ?? __('Short day')]);
    }

    private function find(int $periodId): ?BellPeriod
    {
        if (! array_key_exists($periodId, $this->periods)) {
            $this->periods[$periodId] = BellPeriod::query()->find($periodId);
        }

        return $this->periods[$periodId];
    }

    /** @return list<BellPeriod> */
    private function teaching(int $scheduleId): array
    {
        return $this->teaching[$scheduleId] ??= BellPeriod::query()->where('bell_schedule_id', $scheduleId)->where('is_break', false)->orderBy('position')->get()->values()->all();
    }

    /** Overrides of one school year, newest date first, for the workbench. */
    public function list(User $actor, int $year): array
    {
        app(AcademicAuthority::class)->manage($actor);
        SessionYear::findOrFail($year);

        return BellScheduleOverride::with('schedule', 'replacement')->where('session_year_id', $year)->orderByDesc('on_date')->orderBy('id')->get()
            ->map(fn ($row) => ['id' => (int) $row->id, 'revision' => $row->revision, 'on_date' => $row->on_date->toDateString(), 'reason' => $row->reason,
                'bell_schedule_id' => (int) $row->bell_schedule_id, 'schedule_name' => $row->schedule?->name ?? __('Archived bell schedule'),
                'replacement_bell_schedule_id' => (int) $row->replacement_bell_schedule_id, 'replacement_name' => $row->replacement?->name ?? __('Archived bell schedule')])->all();
    }

    /** Replaces the named schedules on one date. Refused, with nothing saved, when it would create a clash. */
    public function save(User $actor, array $input): array
    {
        app(AcademicAuthority::class)->manage($actor);
        $data = Validator::make($input, self::rules())->validate();
        $pairs = array_values(array_filter($data['replacements'], fn ($pair) => ($pair['replacement_bell_schedule_id'] ?? null) !== null));
        if ($pairs === []) {
            throw ValidationException::withMessages(['replacements' => __('Choose the short-day bells for at least one bell schedule.')]);
        }
        if (count(array_unique(array_map(fn ($pair) => (int) $pair['bell_schedule_id'], $pairs))) !== count($pairs)) {
            throw ValidationException::withMessages(['replacements' => __('Choose each bell schedule once.')]);
        }

        return DB::transaction(function () use ($actor, $data, $pairs) {
            app(TimetableConfiguration::class)->lock($actor);
            $date = $data['on_date'];
            $this->future($date);
            $bases = [];
            $saved = [];
            foreach ($pairs as $pair) {
                $base = BellSchedule::query()->findOrFail((int) $pair['bell_schedule_id']);
                $replacement = BellSchedule::query()->findOrFail((int) $pair['replacement_bell_schedule_id']);
                $year = SessionYear::query()->findOrFail((int) $base->session_year_id);
                if ($base->id === $replacement->id || (int) $replacement->session_year_id !== (int) $base->session_year_id) {
                    throw ValidationException::withMessages(['replacements' => __('Choose different short-day bells from the same school year.')]);
                }
                if ($date < CarbonImmutable::parse($year->start_date)->toDateString() || $date > CarbonImmutable::parse($year->end_date)->toDateString()) {
                    throw ValidationException::withMessages(['on_date' => __('The short day must fall within the bell schedule’s school year.')]);
                }
                if ($this->teaching((int) $replacement->id) === []) {
                    throw ValidationException::withMessages(['replacements' => __('The short-day bells need at least one teaching period.')]);
                }
                $row = BellScheduleOverride::query()->where('bell_schedule_id', $base->id)->whereDate('on_date', $date)->lockForUpdate()->first();
                $before = $row?->getAttributes() ?? [];
                $row ??= new BellScheduleOverride(['bell_schedule_id' => (int) $base->id, 'on_date' => $date]);
                $row->fill(['session_year_id' => (int) $base->session_year_id, 'replacement_bell_schedule_id' => (int) $replacement->id, 'reason' => $data['reason']]);
                $row->revision = $row->exists ? $row->revision + 1 : 1;
                $row->save();
                Audit::record('academics.short_day_saved', $row, $before, $row->getAttributes());
                $bases[] = (int) $base->id;
                $saved[] = $row;
            }
            $this->settle($date, $bases, true);

            return $saved;
        }, 3);
    }

    public function remove(User $actor, int $id, int $revision): void
    {
        app(AcademicAuthority::class)->manage($actor);
        DB::transaction(function () use ($actor, $id, $revision) {
            app(TimetableConfiguration::class)->lock($actor);
            $row = BellScheduleOverride::query()->lockForUpdate()->findOrFail($id);
            abort_if($row->revision !== $revision, 409, __('This short day changed. Reload before removing it.'));
            $date = $row->on_date->toDateString();
            $this->future($date);
            $before = $row->getAttributes();
            $row->delete();
            Audit::record('academics.short_day_removed', $row, $before, []);
            $this->settle($date, [(int) $row->bell_schedule_id], false);
        }, 3);
    }

    private function future(string $date): void
    {
        if ($date < app(AcademicAuthority::class)->today()) {
            throw ValidationException::withMessages(['on_date' => __('Plan short days for today or later.')]);
        }
    }

    /** Re-checks the changed date, then tells the people whose lessons moved. */
    private function settle(string $date, array $bases, bool $short): void
    {
        $this->forget();
        $engine = app(TimetableEngine::class);
        $clashes = $this->clashesOn($date, $engine, $bases);
        if ($clashes !== []) {
            $titles = TimetableLesson::with('group', 'homeroom.class', 'homeroom.section')->whereIn('id', array_merge(...array_column($clashes, 'lesson_ids')))->get()->keyBy('id');
            $lines = array_map(fn ($clash) => implode(' / ', array_map(fn ($id) => $titles[$id]?->group?->name ?? trim(($titles[$id]?->homeroom?->class?->name ?? '').' '.($titles[$id]?->homeroom?->section?->name ?? '')), $clash['lesson_ids'])).' ('.implode(', ', array_map(fn ($reason) => __($reason), $clash['reasons'])).')', array_slice($clashes, 0, 5));
            throw ValidationException::withMessages(['short_day' => __('These bells create timetable clashes on :date: :clashes', ['date' => $date, 'clashes' => implode('; ', $lines)])]);
        }
        $affected = [];
        foreach ($this->scheduled($date, $engine) as $lesson) {
            if (! in_array((int) $lesson->period?->bell_schedule_id, $bases, true)) {
                continue;
            }
            if (! $engine->occursOn($lesson->getAttributes(), $date) && Schema::hasTable('attendance_registers')
                && AttendanceRegister::where('timetable_lesson_id', $lesson->id)->whereDate('on_date', $date)->whereHas('entries')->exists()) {
                abort(409, __('A register was already taken for a lesson that these bells would remove.'));
            }
            $affected[] = $lesson;
        }
        if ($affected === []) {
            return;
        }
        app(TimetableProjection::class)->refresh();
        Outbox::record(new AcademicTimetableChanged(app(CurrentSchool::class)->id(), array_map(fn ($lesson) => (int) $lesson->id, $affected)));
        $this->notify($date, $affected, $engine, $short);
    }

    /** Active lessons scheduled on the date before any short day is applied. */
    private function scheduled(string $date, TimetableEngine $engine): array
    {
        $weekday = CarbonImmutable::parse($date)->isoWeekday();

        return TimetableLesson::with('period')->where('active', true)->where('weekday', $weekday)->whereDate('starts_on', '<=', $date)->whereDate('ends_on', '>=', $date)
            ->orderBy('id')->get()->filter(fn ($lesson) => $engine->scheduledOn($lesson->getAttributes(), $date))->values()->all();
    }

    /**
     * Teacher, room, group, homeroom and shared-learner clashes on one date,
     * using each lesson's actual time that day and any assigned relief teacher.
     * With $schedules, only clashes involving a lesson on one of those bell
     * schedules are reported.
     */
    public function clashesOn(string $date, ?TimetableEngine $engine = null, ?array $schedules = null): array
    {
        $engine ??= app(TimetableEngine::class);
        $cover = app(CoverTimetable::class);
        $items = [];
        $buckets = [];
        foreach ($this->scheduled($date, $engine) as $lesson) {
            $attributes = $lesson->getAttributes();
            $period = $this->period((int) $lesson->bell_period_id, $date);
            if ($period === null) {
                continue;
            }
            $substitute = $cover->assigned($lesson, $date)?->substitute_teacher_id;
            $index = count($items);
            $items[] = ['id' => (int) $lesson->id, 'starts' => (string) $period->starts_at, 'ends' => (string) $period->ends_at,
                'changed' => $schedules === null || in_array((int) $lesson->period?->bell_schedule_id, $schedules, true)];
            $keys = ['teacher:'.($substitute ?? $lesson->teacher_id), 'room:'.$lesson->room_id,
                $lesson->teaching_group_id !== null ? 'group:'.$lesson->teaching_group_id : 'homeroom:'.$lesson->class_section_id];
            foreach ($engine->learners($attributes, $date) as $learner) {
                $keys[] = 'learner:'.$learner;
            }
            foreach (array_unique($keys) as $key) {
                $buckets[$key][] = $index;
            }
        }
        $found = [];
        foreach ($buckets as $key => $members) {
            $reason = strstr($key, ':', true);
            for ($i = 0; $i < count($members); $i++) {
                for ($j = $i + 1; $j < count($members); $j++) {
                    [$one, $two] = [$items[$members[$i]], $items[$members[$j]]];
                    if ((! $one['changed'] && ! $two['changed']) || $one['starts'] >= $two['ends'] || $two['starts'] >= $one['ends']) {
                        continue;
                    }
                    $pair = [min($one['id'], $two['id']), max($one['id'], $two['id'])];
                    $found[implode('-', $pair)]['lesson_ids'] = $pair;
                    $found[implode('-', $pair)]['reasons'][$reason] = $reason;
                }
            }
        }
        ksort($found);

        return array_values(array_map(fn ($clash) => ['date' => $date, 'lesson_ids' => $clash['lesson_ids'], 'reasons' => array_values($clash['reasons'])], $found));
    }

    private function notify(string $date, array $lessons, TimetableEngine $engine, bool $short): void
    {
        $learners = [];
        $teachers = [];
        foreach ($lessons as $lesson) {
            $teachers[] = (int) $lesson->teacher_id;
            foreach ($engine->learners($lesson->getAttributes(), $date) as $learner) {
                $learners[$learner] = true;
            }
        }
        $users = Teacher::query()->whereIn('id', array_unique($teachers))->pluck('user_id')->map(fn ($id) => (int) $id)->all();
        foreach (Students::query()->whereIn('id', array_keys($learners))->get() as $learner) {
            $users[] = (int) $learner->user_id;
            array_push($users, ...app(GuardianService::class)->guardianUserIds($learner)->all());
        }
        $notifier = app(Notifier::class);
        $day = CarbonImmutable::parse($date)->format('j M Y');
        $notice = $short
            ? $notifier->makeNotice('academics.timetable_changed', __('Short day on :date', ['date' => $day]), __('Lessons on :date follow short-day bells. Open the timetable for the new times.', ['date' => $day]), '/academics/timetable')
            : $notifier->makeNotice('academics.timetable_changed', __('Normal bells on :date', ['date' => $day]), __('The short day on :date was cancelled. Lessons follow the normal bells.', ['date' => $day]), '/academics/timetable');
        $notifier->assign($notice, array_values(array_unique(array_filter($users))));
    }
}
