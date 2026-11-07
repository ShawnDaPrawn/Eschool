<?php

declare(strict_types=1);

namespace App\Jobs\OpenStandards;

use App\Services\OpenStandards\OneRoster\OneRosterExports;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

/** Builds a school's OneRoster 1.2 CSV export (Action Plan 5.9) inside that school. */
class BuildOneRosterExport implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** Shorter than the database connection's retry_after, as for the offboarding export. */
    public int $timeout = 150;

    public function __construct(public readonly int $schoolId, public readonly int $exportId, public readonly string $generation)
    {
        $this->onConnection('database')->onQueue('default');
    }

    public function backoff(): array
    {
        return [30, 120, 600];
    }

    public function handle(CurrentSchool $schools): void
    {
        $schools->run($this->schoolId, fn () => app(OneRosterExports::class)->generate($this->exportId, $this->generation));
    }

    public function failed(?Throwable $error): void
    {
        app(CurrentSchool::class)->run($this->schoolId, fn () => app(OneRosterExports::class)->failed($this->exportId, $this->generation, 'generation_failed'));
    }
}
