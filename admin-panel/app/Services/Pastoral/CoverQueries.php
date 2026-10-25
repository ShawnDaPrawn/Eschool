<?php

declare(strict_types=1);

namespace App\Services\Pastoral;

use App\Models\Pastoral\CoverAbsence;
use App\Models\Pastoral\CoverAssignment;
use App\Models\Teacher;
use App\Models\User;
use App\Services\Academics\AcademicAuthority;

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

    public function mine(User $actor, string $from, string $to): array
    {
        $authority = app(CoverAuthority::class);
        $fresh = $authority->actor($actor);
        $teachers = Teacher::where('user_id', $fresh->id)->pluck('id');
        $result = [];
        foreach (CoverAssignment::whereIn('substitute_teacher_id', $teachers)->where('state', 'assigned')->whereBetween('on_date', [$from, $to])->orderBy('on_date')->orderBy('id')->limit(500)->get() as $cover) {
            if ($authority->own($fresh, (int) $cover->substitute_teacher_id) && app(CoverSources::class)->valid($cover)) {
                $result[] = $this->occurrence($fresh, (int) $cover->id);
            }
        }

        return $result;
    }
}
