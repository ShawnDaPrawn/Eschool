<?php

declare(strict_types=1);

namespace App\Jobs\Growth;

use App\Models\Platform\School;
use App\Models\SessionYear;
use App\Services\Academics\AcademicAuthority;
use App\Services\Growth\RiskRecompute;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

class EvaluateRisk implements ShouldBeUniqueUntilProcessing, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $uniqueFor = 120;

    public int $tries = 3;

    public int $timeout = 180;

    public function __construct(public int $schoolId, public int $yearId, public int $gradeId)
    {
        $this->afterCommit();
    }

    public function uniqueId(): string
    {
        return $this->schoolId.':'.$this->yearId.':'.$this->gradeId;
    }

    public function handle(CurrentSchool $schools, RiskRecompute $risk): void
    {
        if (! School::active()->whereKey($this->schoolId)->exists()) {
            return;
        }
        $schools->run($this->schoolId, function () use ($risk) {
            $gate = app(ModuleGate::class);
            $gate->forget();
            $year = SessionYear::find($this->yearId);
            $today = app(AcademicAuthority::class)->today();
            if ($gate->allows('early_warning') && $this->yearId === (int) schoolSetting('session_year') && $year !== null
                && $today >= $year->start_date && $today <= $year->end_date) {
                $risk->grade($this->yearId, $this->gradeId);
            }
        });
    }
}
