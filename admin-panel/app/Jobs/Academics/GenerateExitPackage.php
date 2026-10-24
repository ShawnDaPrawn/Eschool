<?php

declare(strict_types=1);

namespace App\Jobs\Academics;

use App\Services\Academics\Transitions\ExitArtifacts;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class GenerateExitPackage implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(public readonly int $schoolId, public readonly int $packageId, public readonly string $generation) {}

    public function backoff(): array
    {
        return [10, 60, 300];
    }

    public function handle(CurrentSchool $schools, ExitArtifacts $artifacts): void
    {
        $schools->run($this->schoolId, fn () => $artifacts->generate($this->packageId, $this->generation));
    }

    public function failed(?Throwable $error): void
    {
        app(CurrentSchool::class)->run($this->schoolId, fn () => app(ExitArtifacts::class)->failed($this->packageId, $this->generation, 'generation_failed'));
    }
}
