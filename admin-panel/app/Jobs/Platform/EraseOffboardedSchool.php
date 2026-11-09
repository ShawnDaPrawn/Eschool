<?php

declare(strict_types=1);

namespace App\Jobs\Platform;

use App\Services\Platform\Offboarding\SchoolErasure;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Erases a leaving school's rows (Action Plan SA.11) inside that school, a
 * batch at a time for up to 100 seconds, then queues itself again until the
 * school is erased. Each batch saves its own progress, so a job that dies part
 * way is simply run again (the offboarding:resume-erasures command, or HQ's
 * "Resume" button) and goes on from there.
 */
class EraseOffboardedSchool implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    /** Shorter than the database connection's retry_after (180 seconds); see PrepareOffboardingExport. */
    public int $timeout = 150;

    public function __construct(public readonly int $schoolId, public readonly int $offboardingId)
    {
        $this->onConnection('database')->onQueue('default');
    }

    public function handle(CurrentSchool $schools, SchoolErasure $erasure): void
    {
        $more = $schools->run($this->schoolId, fn (): bool => ! $erasure->run($this->offboardingId) && $erasure->erasing($this->offboardingId));
        if ($more) {
            self::dispatch($this->schoolId, $this->offboardingId);
        }
    }
}
