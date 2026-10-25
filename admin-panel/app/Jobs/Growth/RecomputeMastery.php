<?php

declare(strict_types=1);

namespace App\Jobs\Growth;

use App\Models\Platform\School;
use App\Services\Growth\MasteryRecompute;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

class RecomputeMastery implements ShouldBeUniqueUntilProcessing, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $uniqueFor = 120;

    public function uniqueId(): string
    {
        return $this->schoolId.':'.$this->groupId;
    }

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(public int $schoolId, public int $groupId)
    {
        $this->afterCommit();
    }

    public function backoff(): array
    {
        return [10, 60, 300];
    }

    public function handle(CurrentSchool $schools, MasteryRecompute $mastery): void
    {
        if (! School::query()->active()->whereKey($this->schoolId)->exists()) {
            return;
        }
        $schools->run($this->schoolId, function () use ($mastery) {
            $gate = app(ModuleGate::class);
            $gate->forget();
            if ($gate->allows('mastery')) {
                $mastery->group($this->groupId);
            }
        });
    }
}
