<?php

declare(strict_types=1);

namespace App\Jobs\Growth;

use App\Models\Platform\School;
use App\Services\Growth\ProgressNotices;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

class CheckProgressBands implements ShouldBeUniqueUntilProcessing, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $uniqueFor = 120;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(public int $schoolId, public ?int $groupId = null, public ?int $releaseId = null)
    {
        $this->afterCommit();
    }

    public function uniqueId(): string
    {
        return $this->schoolId.':'.($this->groupId ?? 'release').':'.($this->releaseId ?? 'group');
    }

    public function handle(CurrentSchool $schools, ProgressNotices $notices): void
    {
        if (! School::active()->whereKey($this->schoolId)->exists()) {
            return;
        }
        $schools->run($this->schoolId, function () use ($notices) {
            $gate = app(ModuleGate::class);
            $gate->forget();
            if (! $gate->allows('academics')) {
                return;
            }
            if ($this->releaseId !== null) {
                $notices->released($this->releaseId);
            } elseif ($this->groupId !== null) {
                $notices->group($this->groupId);
            }
        });
    }
}
