<?php

declare(strict_types=1);

namespace App\Services\Academics;

use App\Models\Academics\HomeroomAllocation;
use App\Models\Academics\TeachingAllocation;
use App\Models\Academics\TeachingGroup;
use App\Models\ClassSection;
use App\Models\People\Enrolment;
use App\Models\Platform\School;
use App\Models\Platform\SchoolMembership;
use App\Models\SessionYear;
use App\Models\Teacher;
use App\Models\User;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Staff drafts for a new school year (Action Plan 1.20): who teaches each
 * teaching group and who holds each homeroom, prefilled from last year
 * wherever the same group or homeroom carried over. The draft is not stored:
 * it is built from the allocations, edited on the page and applied in one
 * step, so nothing reaches teachers before it is applied. Applying writes
 * ordinary dated allocations for the whole year through TeachingGroups::dated
 * and is refused if any allocation changed since the draft was built.
 * engineering/specs/academics/teaching-groups.md.
 */
class StaffDrafts
{
    public const REASON = 'Drafted from last year';

    /** The year a draft goes into: next year while it is being prepared, otherwise the current one. */
    public function defaultYear(): ?SessionYear
    {
        $current = SessionYear::query()->find((int) schoolSetting('session_year'));
        if ($current === null) {
            return null;
        }

        return app(NewYearService::class)->upcomingYear($current) ?? $current;
    }

    /** @return array<int, string> years a draft can go into: those that have not ended */
    public function years(): array
    {
        return SessionYear::query()->whereDate('end_date', '>=', app(AcademicAuthority::class)->today())->orderByDesc('start_date')->pluck('name', 'id')->all();
    }

    /** The year before $to, where last year's allocations come from. */
    public function previousYear(SessionYear $to): ?SessionYear
    {
        return SessionYear::query()->where('start_date', '<', $to->start_date)->whereKeyNot($to->id)->orderByDesc('start_date')->first();
    }

    /**
     * The draft for $to: every active teaching group and every homeroom in use,
     * each either already staffed (left alone) or open, with last year's
     * teachers suggested where they are still on the staff.
     *
     * @return array{to: SessionYear, from: SessionYear, starts_on: string, ends_on: string, groups: list<array<string, mixed>>, homerooms: list<array<string, mixed>>, teachers: array<int, string>, fingerprint: string}
     */
    public function plan(User $actor, SessionYear $to): array
    {
        app(AcademicAuthority::class)->manage($actor);
        $startsOn = substr((string) $to->start_date, 0, 10);
        $endsOn = substr((string) $to->end_date, 0, 10);
        if ($endsOn < app(AcademicAuthority::class)->today()) {
            throw ValidationException::withMessages(['year' => __('This school year has ended. Choose a year that is still running or still to come.')]);
        }
        $from = $this->previousYear($to);
        if ($from === null) {
            throw ValidationException::withMessages(['year' => __('There is no earlier school year to draft from.')]);
        }
        $teachers = $this->teachers();

        $groups = [];
        $targets = TeachingGroup::query()->with('subject', 'gradeLevel')->where('session_year_id', $to->id)->where('active', true)->get()
            ->sortBy(fn (TeachingGroup $g) => sprintf('%06d %s %s', (int) ($g->gradeLevel?->sort_order ?? 0), $g->subject?->name, $g->name));
        $sources = TeachingGroup::query()->where('session_year_id', $from->id)->orderByDesc('active')->orderBy('id')->get()
            ->groupBy(fn (TeachingGroup $g) => $g->subject_id.'|'.$g->grade_level_id.'|'.$g->name);
        foreach ($targets as $group) {
            $source = $sources->get($group->subject_id.'|'.$group->grade_level_id.'|'.$group->name)?->first();
            $staffed = TeachingAllocation::query()->with('teacher.user')->where('teaching_group_id', $group->id)->orderBy('id')->get();
            $last = $source === null ? collect() : $this->lastHolders(TeachingAllocation::query()->with('teacher.user')->where('teaching_group_id', $source->id)->get());
            $groups[] = [
                'id' => (int) $group->id, 'revision' => (int) $group->revision,
                'name' => $group->name, 'subject' => (string) $group->subject?->name, 'grade' => (string) $group->gradeLevel?->name,
                'source_id' => $source === null ? null : (int) $source->id,
            ] + $this->row($staffed, $last, $teachers);
        }

        $rooms = ClassSection::query()->with('class', 'section')->get()->keyBy('id');
        $inUse = HomeroomAllocation::query()->whereIn('session_year_id', [$from->id, $to->id])->pluck('class_section_id')
            ->merge(Enrolment::query()->where('session_year_id', $to->id)->pluck('class_section_id'))
            ->map(fn ($id) => (int) $id)->unique()->filter(fn (int $id) => $rooms->has($id))
            ->sortBy(fn (int $id) => $rooms[$id]->class?->name.' '.$rooms[$id]->section?->name);
        $homerooms = [];
        foreach ($inUse as $id) {
            $staffed = HomeroomAllocation::query()->with('teacher.user')->where('class_section_id', $id)->where('session_year_id', $to->id)->orderBy('id')->get();
            $last = $this->lastHolders(HomeroomAllocation::query()->with('teacher.user')->where('class_section_id', $id)->where('session_year_id', $from->id)->get());
            $homerooms[] = ['id' => $id, 'name' => trim($rooms[$id]->class?->name.' '.$rooms[$id]->section?->name)] + $this->row($staffed, $last, $teachers);
        }

        return [
            'to' => $to, 'from' => $from, 'starts_on' => $startsOn, 'ends_on' => $endsOn,
            'groups' => $groups, 'homerooms' => $homerooms, 'teachers' => $teachers,
            'fingerprint' => $this->fingerprint($to, $from, $groups, $homerooms, $teachers),
        ];
    }

    /**
     * Applies the edited draft: for each open group and homeroom, a dated
     * allocation for the whole of $to for each chosen teacher. Refused, with
     * nothing written, if anything changed since the draft was built (which
     * also makes a second apply of the same draft write nothing).
     *
     * @param  array{groups?: array<int|string, list<int|string|null>>, homerooms?: array<int|string, list<int|string|null>>}  $choices
     * @return array{teachers: int, homerooms: int}
     */
    public function apply(User $actor, SessionYear $to, array $choices, string $fingerprint): array
    {
        app(AcademicAuthority::class)->manage($actor);

        return DB::transaction(function () use ($actor, $to, $choices, $fingerprint) {
            School::query()->lockForUpdate()->findOrFail(app(CurrentSchool::class)->id());
            $plan = $this->plan($actor, $to);
            if (! hash_equals($plan['fingerprint'], $fingerprint)) {
                throw ValidationException::withMessages(['fingerprint' => __('Allocations changed since this draft was built. Check the draft again, then apply it.')]);
            }
            $groups = $this->chosen($choices['groups'] ?? [], $plan['groups'], $plan['teachers'], 'groups');
            $homerooms = $this->chosen($choices['homerooms'] ?? [], $plan['homerooms'], $plan['teachers'], 'homerooms');
            $dates = ['starts_on' => $plan['starts_on'], 'ends_on' => $plan['ends_on'], 'reason' => self::REASON];
            $done = ['teachers' => 0, 'homerooms' => 0];
            foreach ($groups as $group => $teachers) {
                foreach ($teachers as $teacher) {
                    app(TeachingGroups::class)->dated($actor, 'teachers', ['teacher_id' => $teacher] + $dates, $group);
                    $done['teachers']++;
                }
            }
            foreach ($homerooms as $room => $teachers) {
                foreach ($teachers as $teacher) {
                    app(TeachingGroups::class)->dated($actor, 'homerooms', ['teacher_id' => $teacher, 'class_section_id' => $room, 'session_year_id' => (int) $to->id] + $dates);
                    $done['homerooms']++;
                }
            }
            Audit::record('academics.staff_draft_applied', $to, [], ['from_year' => (int) $plan['from']->id, 'to_year' => (int) $to->id, 'counts' => $done, 'by' => (int) $actor->id]);

            return $done;
        }, 3);
    }

    /**
     * The submitted teachers per open row, checked against the draft: a row
     * that is not open in this draft (another school's, already staffed or
     * unknown) or a teacher who is not on the staff is refused.
     *
     * @param  array<int|string, mixed>  $submitted
     * @param  list<array<string, mixed>>  $rows
     * @param  array<int, string>  $teachers
     * @return array<int, list<int>>
     */
    private function chosen(array $submitted, array $rows, array $teachers, string $field): array
    {
        $open = collect($rows)->filter(fn (array $row) => $row['staffed'] === [])->pluck('id')->flip();
        $result = [];
        foreach ($submitted as $id => $people) {
            if (! ctype_digit((string) $id) || ! $open->has((int) $id)) {
                throw ValidationException::withMessages([$field => __('Something in the draft is not open for drafting. Check the draft again, then apply it.')]);
            }
            $ids = [];
            foreach ((array) $people as $teacher) {
                if ($teacher === null || $teacher === '') {
                    continue;
                }
                if (! ctype_digit((string) $teacher) || ! isset($teachers[(int) $teacher])) {
                    throw ValidationException::withMessages([$field => __('Choose teachers who are on the staff.')]);
                }
                $ids[(int) $teacher] = (int) $teacher;
            }
            if ($ids !== []) {
                $result[(int) $id] = array_values($ids);
            }
        }

        return $result;
    }

    /**
     * Who holds the row now, who held it at the end of last year, and the
     * draft: last year's holders who are still on the staff.
     *
     * @param  Collection<int, TeachingAllocation|HomeroomAllocation>  $staffed
     * @param  \Illuminate\Support\Collection<int, TeachingAllocation|HomeroomAllocation>  $last
     * @param  array<int, string>  $teachers
     * @return array{staffed: list<array{id: int, revision: int, teacher_id: int, name: string}>, last_year: list<array{teacher_id: int, name: string, on_staff: bool}>, draft: list<int>}
     */
    private function row(Collection $staffed, \Illuminate\Support\Collection $last, array $teachers): array
    {
        $name = fn ($row) => $row->teacher?->user ? trim($row->teacher->user->first_name.' '.$row->teacher->user->last_name) : __('Archived person');
        $lastYear = $last->map(fn ($row) => ['teacher_id' => (int) $row->teacher_id, 'name' => $name($row), 'on_staff' => isset($teachers[(int) $row->teacher_id])])
            ->unique('teacher_id')->sortBy('teacher_id')->values()->all();

        return [
            'staffed' => $staffed->map(fn ($row) => ['id' => (int) $row->id, 'revision' => (int) $row->revision, 'teacher_id' => (int) $row->teacher_id, 'name' => $name($row)])->values()->all(),
            'last_year' => $lastYear,
            'draft' => $staffed->isEmpty() ? array_values(array_map(fn ($t) => $t['teacher_id'], array_filter($lastYear, fn ($t) => $t['on_staff']))) : [],
        ];
    }

    /**
     * The allocations that ran until the latest end date: who held the group
     * or homeroom at the end of the year.
     *
     * @param  Collection<int, TeachingAllocation|HomeroomAllocation>  $rows
     * @return \Illuminate\Support\Collection<int, TeachingAllocation|HomeroomAllocation>
     */
    private function lastHolders(Collection $rows): \Illuminate\Support\Collection
    {
        $latest = $rows->max(fn ($row) => $row->ends_on->toDateString());

        return $latest === null ? collect() : $rows->filter(fn ($row) => $row->ends_on->toDateString() === $latest)->values()->toBase();
    }

    /** @return array<int, string> teacher id => name, for teachers on the staff now */
    private function teachers(): array
    {
        $staff = SchoolMembership::query()->active()->where('school_id', app(CurrentSchool::class)->id())->whereNotIn('role', ['Student', 'Parent'])->select('user_id');

        return Teacher::query()->with('user')->whereIn('user_id', $staff)->whereHas('user', fn ($q) => $q->where('status', 1))->get()
            ->sortBy(fn (Teacher $t) => $t->user->first_name.' '.$t->user->last_name)
            ->mapWithKeys(fn (Teacher $t) => [(int) $t->id => trim($t->user->first_name.' '.$t->user->last_name)])->all();
    }

    /**
     * @param  list<array<string, mixed>>  $groups
     * @param  list<array<string, mixed>>  $homerooms
     * @param  array<int, string>  $teachers
     */
    private function fingerprint(SessionYear $to, SessionYear $from, array $groups, array $homerooms, array $teachers): string
    {
        $state = fn (array $rows) => array_map(fn (array $row) => [$row['id'], $row['revision'] ?? null, $row['source_id'] ?? null,
            array_map(fn ($s) => [$s['id'], $s['revision'], $s['teacher_id']], $row['staffed']),
            array_map(fn ($t) => [$t['teacher_id'], $t['on_staff']], $row['last_year'])], $rows);
        $ids = array_keys($teachers);
        sort($ids);

        return hash('sha256', json_encode([(int) $to->id, (int) $from->id, substr((string) $to->start_date, 0, 10), substr((string) $to->end_date, 0, 10),
            $state($groups), $state($homerooms), $ids], JSON_THROW_ON_ERROR));
    }
}
