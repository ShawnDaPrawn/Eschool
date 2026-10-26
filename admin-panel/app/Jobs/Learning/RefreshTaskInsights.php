<?php

declare(strict_types=1);

namespace App\Jobs\Learning;

use App\Models\Academics\TeachingGroup;
use App\Models\Platform\School;
use App\Services\Learning\TaskInsights;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

class RefreshTaskInsights implements ShouldBeUniqueUntilProcessing, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $uniqueFor = 120;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(public int $schoolId, public int $groupId)
    {
        $this->afterCommit();
    }

    public function uniqueId(): string
    {
        return $this->schoolId.':'.$this->groupId;
    }

    public function backoff(): array
    {
        return [10, 60, 300];
    }

    public function handle(CurrentSchool $schools, TaskInsights $insights): void
    {
        if (! School::active()->whereKey($this->schoolId)->exists()) {
            return;
        }
        $schools->run($this->schoolId, function () use ($insights) {
            $gate = app(ModuleGate::class);
            $gate->forget();
            if ($gate->allows('test_insights') && TeachingGroup::whereKey($this->groupId)->where('active', true)->where('session_year_id', schoolSetting('session_year'))->exists()) {
                $insights->group($this->groupId);
            }
        });
    }
}
