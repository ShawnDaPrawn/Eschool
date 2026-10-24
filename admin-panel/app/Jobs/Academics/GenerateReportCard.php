<?php

declare(strict_types=1);

namespace App\Jobs\Academics;

use App\Services\Academics\Reports\ReportArtifacts;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

class GenerateReportCard implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(public int $schoolId, public int $cardId, public string $fingerprint)
    {
        $this->afterCommit();
    }

    public function backoff(): array
    {
        return [10, 60, 300];
    }

    public function handle(CurrentSchool $schools, ReportArtifacts $artifacts): void
    {
        $schools->run($this->schoolId, fn () => $artifacts->generate($this->cardId, $this->fingerprint));
    }
}
