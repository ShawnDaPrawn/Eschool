<?php

declare(strict_types=1);

namespace App\Services\Academics;

use App\Models\Academics\AttendanceRegister;
use App\Models\Academics\BellPeriod;
use App\Models\Academics\HomeroomAllocation;
use App\Models\Academics\Room;
use App\Models\Academics\TeachingAllocation;
use App\Models\Academics\TeachingGroup;
use App\Models\Academics\TeachingGroupMembership;
use App\Models\Academics\TimetableLesson;
use App\Models\Pastoral\CoverAssignment;
use App\Models\People\Enrolment;
use App\Models\Platform\SchoolMembership;
use App\Models\SessionYear;
use App\Models\Teacher;
use App\Models\Term;
use App\Models\User;
use App\Services\Pastoral\CoverTimetable;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Events\AcademicTimetableChanged;
use App\Services\Platform\Events\Outbox;
use App\Services\Platform\Tenancy\CurrentSchool;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/** One placement validator for the editor, import preview and atomic commit. */
class TimetableEngine
{
    private array $dateCache = [];

    private array $periodCache = [];

    private array $rosterCache = [];

    private ?array $groupRoster = null;

    private ?array $homeroomRoster = null;

    public static function rules(): array
    {
        return ['session_year_id' => ['required', 'integer', 'min:1'], 'term_id' => ['nullable', 'integer', 'min:1'],
            'teaching_group_id' => ['nullable', 'integer', 'min:1'], 'class_section_id' => ['nullable', 'integer', 'min:1'],
            'teacher_id' => ['required', 'integer', 'min:1'], 'room_id' => ['required', 'integer', 'min:1'], 'bell_period_id' => ['required', 'integer', 'min:1'],
            'starts_on' => ['required', 'date_format:Y-m-d'], 'ends_on' => ['required', 'date_format:Y-m-d', 'after_or_equal:starts_on'],
            'occurs_on' => ['nullable', 'date_format:Y-m-d'], 'weekday' => ['required', 'integer', 'between:1,7'],
            'week_pattern' => ['required', 'in:all,odd,even'], 'active' => ['required', 'boolean'],
            'id' => ['sometimes', 'nullable', 'integer', 'min:1'], 'revision' => ['sometimes', 'nullable', 'integer', 'min:1']];
    }

    public function preview(User $actor, array $rows): array
    {
        app(AcademicAuthority::class)->manage($actor);
        $this->dateCache = $this->periodCache = $this->rosterCache = [];
        $this->groupRoster = $this->homeroomRoster = null;
        if ($rows === [] || count($rows) > 300) {
            throw ValidationException::withMessages(['rows' => __('Submit between one and 300 lessons.')]);
        }
        $normal = [];
        $seen = [];
        foreach ($rows as $row) {
            $data = $this->normalise($row);
            if ($data['id'] !== null) {
                abort_if(isset($seen[$data['id']]), 422, __('Submit each lesson once.'));
                $seen[$data['id']] = true;
                $existing = TimetableLesson::findOrFail($data['id']);
                abort_if($existing->revision !== $data['revision'], 409, __('A lesson changed. Reload before saving.'));
                // Preserve captured occurrences while allowing room moves and future range edits.
                if (Schema::hasTable('attendance_registers')) {
                    $captured = AttendanceRegister::where('timetable_lesson_id', $existing->id)->whereHas('entries')->pluck('on_date')->map(fn ($date) => substr((string) $date, 0, 10))->all();
                    if ($captured !== [] && $data['active']) {
                        foreach (['session_year_id', 'teaching_group_id', 'class_section_id', 'teacher_id', 'bell_period_id'] as $field) {
                            $before = $existing->getRawOriginal($field);
                            $value = $data[$field];
                            abort_if(($before === null) !== ($value === null) || ($before !== null && (string) $before !== (string) $value), 409, __('Copy this lesson for a new placement; retain its captured register identity.'));
                        }
                        abort_if(array_diff($captured, $this->dates($data)) !== [], 409, __('Keep captured register dates in this lesson. Split future placements instead.'));
                    }
                }
            }
            $normal[] = $data;
        }
        $conflicts = [];
        $projection = app(TimetableProjection::class);
        $legacyRows = $projection->legacyEvidence();
        $all = TimetableLesson::query()->with('period')->whereNotIn('id', array_keys($seen))->where('active', true)->get();
        foreach ($normal as $index => $row) {
            if (! $row['active']) {
                continue;
            }
            foreach ($all as $other) {
                $conflicts = array_merge($conflicts, $this->clashes($row, $other->getAttributes(), $index));
            }
            foreach (array_slice($normal, 0, $index) as $other) {
                $conflicts = array_merge($conflicts, $this->clashes($row, $other, $index));
            }
            $conflicts = array_merge($conflicts, $projection->legacyClashes($row, $index, $this, $legacyRows), app(CoverTimetable::class)->clashes($row, $index, $this));
        }
        // Relevant existing rows/revisions and memberships are included in the
        // fingerprint so a preview cannot authorize a changed timetable later.
        $evidence = ['school' => app(CurrentSchool::class)->id(), 'rows' => $normal,
            'existing' => $all->map(fn ($lesson) => $lesson->getAttributes())->all(),
            'periods' => BellPeriod::orderBy('id')->get()->toArray(),
            'rooms' => Room::orderBy('id')->get()->toArray(),
            'memberships' => TeachingGroupMembership::orderBy('id')->get()->toArray(),
            'enrolments' => Enrolment::orderBy('id')->get()->toArray(),
            'allocations' => TeachingAllocation::orderBy('id')->get()->toArray(),
            'homerooms' => HomeroomAllocation::orderBy('id')->get()->toArray(),
            'legacy' => $legacyRows,
            'cover' => app(CoverTimetable::class)->enabled() ? CoverAssignment::where('state', 'assigned')->orderBy('id')->get(['id', 'timetable_lesson_id', 'substitute_teacher_id', 'on_date', 'revision', 'source_hash'])->toArray() : []];

        return ['rows' => $normal, 'conflicts' => $conflicts, 'can_save' => $conflicts === [],
            'fingerprint' => hash('sha256', json_encode($evidence, JSON_THROW_ON_ERROR))];
    }

    public function save(User $actor, array $rows, string $fingerprint): array
    {
        app(AcademicAuthority::class)->manage($actor);

        return DB::transaction(function () use ($actor, $rows, $fingerprint) {
            app(TimetableConfiguration::class)->lock($actor);
            $preview = $this->preview($actor, $rows);
            abort_unless(hash_equals($preview['fingerprint'], $fingerprint), 409, __('The timetable changed. Preview it again before saving.'));
            abort_unless($preview['can_save'], 409, __('Resolve the timetable clashes before saving.'));
            $saved = [];
            foreach ($preview['rows'] as $data) {
                $lesson = $data['id'] === null ? new TimetableLesson : TimetableLesson::query()->lockForUpdate()->findOrFail($data['id']);
                $before = $lesson->exists ? $lesson->getAttributes() : [];
                $revision = $lesson->exists ? $lesson->revision + 1 : 1;
                unset($data['id'], $data['revision']);
                $lesson->fill($data);
                $lesson->revision = $revision;
                $lesson->save();
                Audit::record('academics.lesson_saved', $lesson, $before, $lesson->getAttributes());
                $saved[] = $lesson;
            }
            app(TimetableProjection::class)->refresh();
            Outbox::record(new AcademicTimetableChanged(app(CurrentSchool::class)->id(), array_map(fn ($lesson) => (int) $lesson->id, $saved)));

            return ['lessons' => array_map(fn ($lesson) => app(TimetableQueries::class)->data($lesson), $saved)];
        }, 3);
    }

    public function normalise(array $input): array
    {
        $data = Validator::make($input, self::rules())->validate() + ['term_id' => null, 'teaching_group_id' => null, 'class_section_id' => null, 'occurs_on' => null, 'id' => null, 'revision' => null];
        foreach (['session_year_id', 'term_id', 'teaching_group_id', 'class_section_id', 'teacher_id', 'room_id', 'bell_period_id', 'weekday', 'id', 'revision'] as $key) {
            $data[$key] = $data[$key] === null ? null : (int) $data[$key];
        }
        $data['active'] = (bool) $data['active'];
        if (($data['teaching_group_id'] === null) === ($data['class_section_id'] === null) || ($data['id'] !== null && $data['revision'] === null)) {
            throw ValidationException::withMessages(['lesson' => __('Choose exactly one teaching group or homeroom, and include the revision when editing.')]);
        }
        if (! $data['active']) {
            abort_if($data['id'] === null, 422, __('Cancel an existing lesson rather than creating an inactive placement.'));
            $existing = TimetableLesson::findOrFail($data['id']);
            abort_if($existing->revision !== $data['revision'], 409, __('A lesson changed. Reload before cancelling.'));
            // Cancellation retains exact identity/provenance even when a teacher,
            // group, room, term or year has since been disabled or archived.
            foreach ($data as $key => $value) {
                if (in_array($key, ['id', 'revision', 'active'], true)) {
                    continue;
                }
                $before = $existing->getRawOriginal($key);
                if (($before === null) !== ($value === null) || ($before !== null && (string) $before !== (string) $value)) {
                    throw ValidationException::withMessages(['lesson' => __('Cancel the saved lesson without changing its placement fields.')]);
                }
            }
            ksort($data);

            return $data;
        }
        $year = SessionYear::findOrFail($data['session_year_id']);
        if ($data['starts_on'] < $year->start_date || $data['ends_on'] > $year->end_date || CarbonImmutable::parse($data['starts_on'])->diffInDays($data['ends_on']) > 400) {
            throw ValidationException::withMessages(['starts_on' => __('The lesson must fit within its school year (at most 400 days).')]);
        }
        $grade = null;
        if ($data['teaching_group_id'] !== null) {
            $group = TeachingGroup::with('gradeLevel')->findOrFail($data['teaching_group_id']);
            abort_unless($group->active && (int) $group->session_year_id === $data['session_year_id'], 422, __('Choose an active group in this school year.'));
            $grade = $group->gradeLevel->sort_order;
        } else {
            $homeroom = app(TeachingGroups::class)->room($data['class_section_id']);
            $grade = $homeroom->class->gradeLevel?->sort_order;
        }
        if ($data['term_id'] !== null) {
            $term = Term::where('session_year_id', $year->id)->findOrFail($data['term_id']);
            if ($data['starts_on'] < $term->start_date->toDateString() || $data['ends_on'] > $term->end_date->toDateString() || ($grade !== null && ! $term->coversGrade((int) $grade))) {
                throw ValidationException::withMessages(['term_id' => __('The lesson must fit the selected grade’s term.')]);
            }
        }
        $room = Room::findOrFail($data['room_id']);
        abort_unless($room->active || ! $data['active'], 422, __('Choose an active room.'));
        $period = BellPeriod::with('schedule')->findOrFail($data['bell_period_id']);
        abort_unless($period->schedule !== null && (int) $period->schedule->session_year_id === $data['session_year_id'] && ! $period->is_break, 422, __('Choose a teaching period in this school year.'));
        $teacher = Teacher::whereHas('user', fn ($q) => $q->where('status', 1))->findOrFail($data['teacher_id']);
        abort_unless(SchoolMembership::query()->active()->where('school_id', app(CurrentSchool::class)->id())->where('user_id', $teacher->user_id)->whereNotIn('role', ['Student', 'Parent'])->exists(), 422, __('The teacher needs current staff membership.'));
        $dates = $this->dates(array_replace($data, ['active' => true]));
        if ($dates === []) {
            throw ValidationException::withMessages(['weekday' => __('This lesson has no occurrence in its date range.')]);
        }
        if ($data['occurs_on'] !== null && ($data['occurs_on'] < $data['starts_on'] || $data['occurs_on'] > $data['ends_on'])) {
            throw ValidationException::withMessages(['occurs_on' => __('The specific date must fit the lesson’s range.')]);
        }
        if ($data['active']) {
            foreach ($dates as $date) {
                $allocation = $data['teaching_group_id'] !== null
                    ? TeachingAllocation::where('teaching_group_id', $data['teaching_group_id'])
                    : HomeroomAllocation::where('class_section_id', $data['class_section_id'])->where('session_year_id', $year->id);
                abort_unless($allocation->where('teacher_id', $data['teacher_id'])->whereDate('starts_on', '<=', $date)->whereDate('ends_on', '>=', $date)->exists(), 422, __('The teacher must be allocated for every lesson date.'));
                if ($room->capacity !== null && count($this->learners($data, $date)) > $room->capacity) {
                    throw ValidationException::withMessages(['room_id' => __('The room is too small for the dated lesson roster.')]);
                }
            }
        }

        ksort($data);

        return $data;
    }

    public function dates(array $lesson): array
    {
        if (! $lesson['active']) {
            return [];
        }
        $key = implode('|', [$lesson['starts_on'], $lesson['ends_on'], $lesson['occurs_on'] ?? '', $lesson['weekday'], $lesson['week_pattern']]);
        if (isset($this->dateCache[$key])) {
            return $this->dateCache[$key];
        }
        $result = [];
        $start = CarbonImmutable::parse($lesson['starts_on']);
        $end = CarbonImmutable::parse($lesson['ends_on']);
        for ($date = $start; $date <= $end; $date = $date->addDay()) {
            if (($lesson['occurs_on'] ?? null) !== null && $date->toDateString() !== $lesson['occurs_on']) {
                continue;
            }
            if ($date->isoWeekday() !== (int) $lesson['weekday']) {
                continue;
            }
            $pattern = $lesson['week_pattern'];
            if (($pattern === 'odd' && $date->isoWeek() % 2 === 0) || ($pattern === 'even' && $date->isoWeek() % 2 === 1)) {
                continue;
            }
            $result[] = $date->toDateString();
        }

        return $this->dateCache[$key] = $result;
    }

    public function learners(array $lesson, string $date): array
    {
        $key = ($lesson['teaching_group_id'] !== null ? 'g:'.$lesson['teaching_group_id'] : 'h:'.$lesson['class_section_id'].':'.$lesson['session_year_id']).':'.$date;
        if (isset($this->rosterCache[$key])) {
            return $this->rosterCache[$key];
        }
        $this->groupRoster ??= TeachingGroupMembership::orderBy('id')->get()->map(fn ($row) => $row->getAttributes())->all();
        $this->homeroomRoster ??= Enrolment::orderBy('id')->get()->map(fn ($row) => $row->getAttributes())->all();
        $rows = $lesson['teaching_group_id'] !== null ? $this->groupRoster : $this->homeroomRoster;
        $ids = [];
        foreach ($rows as $membership) {
            $same = $lesson['teaching_group_id'] !== null
                ? (int) $membership['teaching_group_id'] === (int) $lesson['teaching_group_id']
                : (int) $membership['class_section_id'] === (int) $lesson['class_section_id'] && (int) $membership['session_year_id'] === (int) $lesson['session_year_id'];
            if ($same && $membership['starts_on'] <= $date && ($membership['ends_on'] === null || $membership['ends_on'] >= $date)) {
                $ids[] = (int) $membership['learner_id'];
            }
        }

        return $this->rosterCache[$key] = array_values(array_unique($ids));
    }

    public function clashes(array $one, array $two, int $row): array
    {
        if (! $one['active'] || ! $two['active']) {
            return [];
        }
        $first = $this->periodCache[$one['bell_period_id']] ??= BellPeriod::findOrFail($one['bell_period_id']);
        $second = $this->periodCache[$two['bell_period_id']] ??= BellPeriod::findOrFail($two['bell_period_id']);
        if ($first->starts_at >= $second->ends_at || $second->starts_at >= $first->ends_at) {
            return [];
        }
        $reasons = [];
        if ((int) $one['teacher_id'] === (int) $two['teacher_id']) {
            $reasons[] = 'teacher';
        }
        if ((int) $one['room_id'] === (int) $two['room_id']) {
            $reasons[] = 'room';
        }
        if ($one['teaching_group_id'] !== null && $two['teaching_group_id'] !== null && (int) $one['teaching_group_id'] === (int) $two['teaching_group_id']) {
            $reasons[] = 'group';
        }
        if ($one['class_section_id'] !== null && $two['class_section_id'] !== null && (int) $one['class_section_id'] === (int) $two['class_section_id']) {
            $reasons[] = 'homeroom';
        }
        $dates = array_intersect($this->dates($one), $this->dates($two));
        foreach ($dates as $date) {
            $shared = array_intersect($this->learners($one, $date), $this->learners($two, $date));
            $onDate = $shared === [] ? $reasons : array_values(array_unique([...$reasons, 'learner']));
            if ($onDate !== []) {
                return [['row' => $row, 'lesson_id' => isset($two['id']) ? (int) $two['id'] : null, 'date' => $date, 'reasons' => $onDate]];
            }
        }

        return [];
    }
}
