<?php

declare(strict_types=1);

namespace App\Actions\Transfers;

use App\Models\Discipline\DisciplineFramework;
use App\Models\User;
use App\Services\Discipline\DisciplineFrameworks;
use Illuminate\Support\Facades\DB;

/**
 * Publishes last year's discipline framework (levels, sanctions and
 * infractions) as the new year's first version. Records, counters and
 * detentions are not carried: every learner starts the year clean. Discipline
 * changes are written to the current year only, so this runs just after the
 * new year becomes current (NewYearService::AFTER_SWITCH). A new year that
 * already has a framework is left alone. Specification:
 * engineering/specs/academics/new-year-structures.md (Action Plan 1.19).
 */
class TransferDisciplineFramework
{
    public function execute(int $fromSessionId, int $toSessionId): void
    {
        $actor = auth()->user();
        abort_unless($actor instanceof User, 403);

        DB::transaction(function () use ($actor, $fromSessionId, $toSessionId) {
            $source = DisciplineFramework::query()->where('session_year_id', $fromSessionId)->first();
            if ($source === null || DisciplineFramework::query()->where('session_year_id', $toSessionId)->exists()) {
                return;
            }
            app(DisciplineFrameworks::class)->save($actor, $toSessionId, ['title' => $source->title, 'expected_revision' => 0] + (array) $source->definition);
        });
    }
}
