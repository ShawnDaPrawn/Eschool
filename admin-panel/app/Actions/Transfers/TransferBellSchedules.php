<?php

declare(strict_types=1);

namespace App\Actions\Transfers;

use App\Models\Academics\BellSchedule;
use App\Models\SessionYear;
use App\Models\User;
use App\Services\Academics\AcademicAuthority;
use App\Services\Academics\TimetableConfiguration;
use Illuminate\Support\Facades\DB;

/**
 * Copies the year's bell schedules (each with its periods and breaks) into the
 * new year. Lessons are not copied: they name teachers and teaching groups,
 * which are allocated afresh, so the new timetable is built on these bells.
 * A new year that already has bell schedules is left alone. Specification:
 * engineering/specs/academics/new-year-structures.md (Action Plan 1.19).
 */
class TransferBellSchedules
{
    public function execute(int $fromSessionId, int $toSessionId): void
    {
        $actor = auth()->user();
        abort_unless($actor instanceof User, 403);
        app(AcademicAuthority::class)->manage($actor);
        // Both years must be this school's.
        SessionYear::query()->findOrFail($fromSessionId);
        SessionYear::query()->findOrFail($toSessionId);

        DB::transaction(function () use ($actor, $fromSessionId, $toSessionId) {
            if (BellSchedule::query()->where('session_year_id', $toSessionId)->exists()) {
                return;
            }
            $schedules = BellSchedule::query()->with(['periods' => fn ($q) => $q->orderBy('position')])->where('session_year_id', $fromSessionId)->orderBy('id')->get();
            foreach ($schedules as $schedule) {
                if ($schedule->periods->isEmpty()) {
                    continue;
                }
                app(TimetableConfiguration::class)->bells($actor, [
                    'name' => $schedule->name, 'section' => $schedule->section, 'session_year_id' => $toSessionId,
                    'periods' => $schedule->periods->map(fn ($period) => [
                        'name' => $period->name, 'starts_at' => substr((string) $period->starts_at, 0, 5),
                        'ends_at' => substr((string) $period->ends_at, 0, 5), 'is_break' => (bool) $period->is_break,
                    ])->values()->all(),
                ]);
            }
        });
    }
}
