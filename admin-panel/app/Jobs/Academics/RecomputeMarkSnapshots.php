<?php

declare(strict_types=1);

namespace App\Jobs\Academics;

use App\Services\Academics\MarkSnapshots;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

class RecomputeMarkSnapshots implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(public int $schoolId, public int $groupId, public ?int $termId = null, public bool $allTerms = false)
    {
        $this->afterCommit();
    }

    public function backoff(): array
    {
        return [10, 60, 300];
    }

    public function handle(CurrentSchool $schools, MarkSnapshots $snapshots): void
    {
        $schools->run($this->schoolId, fn () => $snapshots->recompute($this->groupId, $this->termId, $this->allTerms));
    }
}
