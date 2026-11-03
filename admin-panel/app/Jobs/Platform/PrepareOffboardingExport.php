<?php

declare(strict_types=1);

namespace App\Jobs\Platform;

use App\Services\Platform\Offboarding\OffboardingExport;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

/** Builds a leaving school's export (Action Plan SA.11) inside that school. */
class PrepareOffboardingExport implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /**
     * Shorter than the database connection's retry_after (180 seconds), so a
     * slow build is never handed to a second worker while it still runs. The
     * long-running academic_content worker is an optional profile that
     * production does not provision, so the job stays on the default worker.
     */
    public int $timeout = 150;

    public function __construct(public readonly int $schoolId, public readonly int $offboardingId, public readonly string $generation)
    {
        // The queue the provisioned worker consumes (docker/entrypoint.sh, infra/gcp/provision.sh).
        $this->onConnection('database')->onQueue('default');
    }

    public function backoff(): array
    {
        return [30, 300, 900];
    }

    public function handle(CurrentSchool $schools, OffboardingExport $export): void
    {
        $schools->run($this->schoolId, fn () => $export->generate($this->offboardingId, $this->generation));
    }

    public function failed(?Throwable $error): void
    {
        app(CurrentSchool::class)->run($this->schoolId, fn () => app(OffboardingExport::class)->failed($this->offboardingId, $this->generation, 'generation_failed'));
    }
}
