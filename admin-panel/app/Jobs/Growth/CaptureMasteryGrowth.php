<?php

declare(strict_types=1);

namespace App\Jobs\Growth;

use App\Models\Platform\School;
use App\Services\Growth\MasteryGrowth;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

/** One school's daily term growth snapshots; repeating it changes nothing (Action Plan 3.14). */
class CaptureMasteryGrowth implements ShouldBeUniqueUntilProcessing, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $uniqueFor = 3600;

    public int $tries = 3;

    public int $timeout = 900;

    public function __construct(public int $schoolId) {}

    public function uniqueId(): string
    {
        return (string) $this->schoolId;
    }

    public function backoff(): array
    {
        return [60, 300, 900];
    }

    public function handle(CurrentSchool $schools, MasteryGrowth $growth): void
    {
        if (! School::query()->active()->whereKey($this->schoolId)->exists()) {
            return;
        }
        $schools->run($this->schoolId, function () use ($growth) {
            $gate = app(ModuleGate::class);
            $gate->forget();
            if ($gate->allows('mastery')) {
                $growth->capture();
            }
        });
    }
}
