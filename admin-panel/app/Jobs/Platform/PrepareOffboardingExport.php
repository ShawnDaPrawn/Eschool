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

    /** Under the database queue's retry_after (420 seconds), so a slow build is never run twice at once. */
    public int $timeout = 400;

    public function __construct(public readonly int $schoolId, public readonly int $offboardingId, public readonly string $generation) {}

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
