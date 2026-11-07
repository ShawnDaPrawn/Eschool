<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Platform\School;
use App\Services\OpenStandards\OneRoster\OneRosterExports;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Console\Command;

/** Deletes OneRoster export files whose retention has ended, school by school (Action Plan 5.9). */
class PruneOneRosterExports extends Command
{
    protected $signature = 'open-standards:prune-exports';

    protected $description = 'Delete stored OneRoster export files after their retention ends, keeping each export row and its audit trail';

    public function handle(): int
    {
        $purged = 0;
        School::orderBy('id')->each(function ($school) use (&$purged): void {
            $purged += (int) app(CurrentSchool::class)->run((int) $school->id, fn () => app(OneRosterExports::class)->prune());
        });
        $this->info("Deleted {$purged} expired OneRoster export files.");

        return self::SUCCESS;
    }
}
