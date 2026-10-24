<?php

declare(strict_types=1);

namespace App\Jobs\Academics;

use App\Services\Academics\Content\ContentPipeline;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class ConvertContent implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 330;

    public function __construct(public int $schoolId, public int $versionId, public string $generation)
    {
        $this->onConnection('academic_content')->onQueue('content');
    }

    public function backoff(): array
    {
        return [10, 30, 120];
    }

    public function handle(CurrentSchool $schools, ContentPipeline $pipeline): void
    {
        $schools->run($this->schoolId, fn () => $pipeline->run($this->versionId, $this->generation));
    }

    public function failed(?Throwable $error): void
    {
        app(CurrentSchool::class)->run($this->schoolId, fn () => app(ContentPipeline::class)->exhausted($this->versionId, $this->generation));
    }
}
