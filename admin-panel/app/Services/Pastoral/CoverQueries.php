<?php

declare(strict_types=1);

namespace App\Services\Pastoral;

use App\Models\Academics\TimetableLesson;
use App\Models\Pastoral\CoverAbsence;
use App\Models\Pastoral\CoverAssignment;
use App\Models\Platform\SchoolMembership;
use App\Models\SessionYear;
use App\Models\Teacher;
use App\Models\User;
use App\Services\Academics\AcademicAuthority;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class CoverQueries
{
    public function absence(User $actor, int $id): array
    {
        $absence = app(CoverAuthority::class)->absence($actor, $id);
        $teacher = Teacher::with('user')->find($absence->teacher_id);

        return ['id' => (int) $absence->id, 'revision' => $absence->revision, 'state' => $absence->state,
            'session_year_id' => (int) $absence->session_year_id, 'teacher_id' => (int) $absence->teacher_id,
            'teacher_name' => $teacher?->user?->full_name ?? 'Archived teacher', 'reason' => $absence->reason,
            'cancel_reason' => $absence->cancel_reason, 'starts_on' => $absence->starts_on->toDateString(), 'ends_on' => $absence->ends_on->toDateString(),
            'occurrences' => CoverAssignment::where('cover_absence_id', $id)->orderBy('on_date')->orderBy('id')->get()->map(fn ($row) => $this->occurrence($actor, (int) $row->id))->all()];
    }

    public function occurrence(User $actor, int $id): array
    {
        $authority = app(CoverAuthority::class);
        $fresh = $authority->actor($actor);
        $cover = CoverAssignment::findOrFail($id);
        $absence = CoverAbsence::findOrFail($cover->cover_absence_id);
        $owner = $authority->manages($fresh, (int) $absence->session_year_id) || $authority->own($fresh, (int) $absence->teacher_id);
        $valid = app(CoverSources::class)->valid($cover);
        $substitute = $cover->substitute_teacher_id !== null && $authority->own($fresh, (int) $cover->substitute_teacher_id);
        abort_unless($owner || ($substitute && $cover->state === 'assigned' && $valid), 403);
        $teacher = $cover->substitute_teacher_id === null ? null : Teacher::with('user')->find($cover->substitute_teacher_id);
        $state = $cover->state;
        if ($state !== 'cancelled' && $cover->on_date->toDateString() >= app(AcademicAuthority::class)->today() && ! $valid) {
            $state = 'needs_review';
        }

        return ['id' => (int) $cover->id, 'absence_id' => (int) $cover->cover_absence_id, 'revision' => $cover->revision,
            'state' => $state, 'on_date' => $cover->on_date->toDateString(), 'source' => $cover->source_snapshot,
            'substitute_teacher_id' => $cover->substitute_teacher_id === null ? null : (int) $cover->substitute_teacher_id,
            'substitute_name' => $teacher?->user?->full_name];
    }

    public function mine(User $actor, string $from, string $to, ?int $year = null): array
    {
        $authority = app(CoverAuthority::class);
        $fresh = $authority->actor($actor);
        if ($year !== null) {
            SessionYear::findOrFail($year);
        }
        $teachers = Teacher::where('user_id', $fresh->id)->get()->filter(fn ($t) => $authority->own($fresh, (int) $t->id))->pluck('id');
        abort_unless($teachers->isNotEmpty() || $authority->manages($fresh, $year ?? (int) schoolSetting('session_year')), 403);
        $result = [];
        foreach (CoverAssignment::whereIn('substitute_teacher_id', $teachers)->when($year !== null, fn ($q) => $q->whereIn('cover_absence_id', CoverAbsence::where('session_year_id', $year)->select('id')))->where('state', 'assigned')->whereBetween('on_date', [$from, $to])->orderBy('on_date')->orderBy('id')->limit(500)->get() as $cover) {
            if ($authority->own($fresh, (int) $cover->substitute_teacher_id) && app(CoverSources::class)->valid($cover)) {
                $result[] = $this->occurrence($fresh, (int) $cover->id);
            }
        }

        return $result;
    }

    public function configuration(User $actor, int $year, string $from, string $to, int $page = 1): array
    {
        $a = app(CoverAuthority::class);
        $fresh = $a->actor($actor);
        $yearRow = SessionYear::findOrFail($year);
        $manager = $a->manages($fresh, $year);
        $own = Teacher::where('user_id', $fresh->id)->get()->filter(fn ($t) => $a->own($fresh, (int) $t->id))->pluck('id')->all();
        abort_unless($manager || $own !== [], 403);
        $teachers = $this->teachers($from, $manager ? null : $own);
        $rows = CoverAbsence::where('session_year_id', $year)->whereDate('starts_on', '<=', $to)->whereDate('ends_on', '>=', $from)
            ->when(! $manager, fn ($q) => $q->whereIn('teacher_id', $own))->orderByDesc('starts_on')->orderByDesc('id')->paginate(50, ['*'], 'page', max(1, $page));

        return ['session_year_id' => $year, 'year_name' => $yearRow->name, 'can_manage' => $manager, 'teachers' => $teachers,
            'current_page' => $rows->currentPage(), 'last_page' => $rows->lastPage(), 'total' => $rows->total(),
            'absences' => $rows->getCollection()->map(fn ($row) => ['id' => (int) $row->id, 'revision' => $row->revision, 'state' => $row->state,
                'teacher_id' => (int) $row->teacher_id, 'teacher_name' => Teacher::with('user')->find($row->teacher_id)?->user?->full_name ?? __('Archived teacher'),
                'starts_on' => $row->starts_on->toDateString(), 'ends_on' => $row->ends_on->toDateString()])->all()];
    }

    private function teachers(string $date, ?array $ids = null): array
    {
        $members = SchoolMembership::active()->where('school_id', app(CurrentSchool::class)->id())->where('role', 'Teacher')
            ->where(fn ($q) => $q->whereNull('starts_on')->orWhereDate('starts_on', '<=', $date))
            ->where(fn ($q) => $q->whereNull('ends_on')->orWhereDate('ends_on', '>=', $date))->pluck('user_id');
        $rows = Teacher::with('user')->whereIn('user_id', $members)->when($ids !== null, fn ($q) => $q->whereIn('id', $ids))->orderBy('id')->limit(501)->get();
        abort_if($rows->count() > 500, 422, 'The teacher catalogue is too large. Ask your school to review its active allocations.');
        $result = [];
        foreach ($rows as $row) {
            try {
                app(CoverAuthority::class)->teacher((int) $row->id, $date);
            } catch (ModelNotFoundException|HttpException) {
                continue;
            }
            $result[] = ['id' => (int) $row->id, 'name' => $row->user->full_name];
        }

        return $result;
    }

    public function candidates(User $actor, int $id): array
    {
        $a = app(CoverAuthority::class);
        $fresh = $a->actor($actor);
        $cover = CoverAssignment::findOrFail($id);
        $absence = $a->absence($fresh, (int) $cover->cover_absence_id);
        abort_unless($a->manages($fresh, (int) $absence->session_year_id), 403);
        abort_unless($cover->state !== 'cancelled' && app(CoverSources::class)->valid($cover), 409, 'Refresh this changed cover request first.');
        $lesson = TimetableLesson::findOrFail($cover->timetable_lesson_id);

        return ['occurrence_id' => $id, 'revision' => $cover->revision, 'teachers' => array_values(array_filter($this->teachers($cover->on_date->toDateString()),
            fn ($t) => app(CoverAvailability::class)->assignable($t['id'], $cover, $lesson)))];
    }
}
