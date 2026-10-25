<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\Growth\EvaluateRisk;
use App\Models\Academics\GradeLevel;
use App\Models\Platform\School;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Console\Command;

class QueueRiskEvaluation extends Command
{
    protected $signature = 'risk:evaluate {school? : Active school ID; omitted queues enabled schools}';

    protected $description = 'Queue current-day school-scoped early-warning observations, without model calls';

    public function handle(): int
    {
        $id = $this->argument('school');
        if ($id !== null && (! ctype_digit((string) $id) || ! School::active()->whereKey($id)->exists())) {
            $this->error('Choose an active school ID.');

            return self::FAILURE;
        }
        School::active()->when($id !== null, fn ($q) => $q->whereKey($id))->orderBy('id')->chunkById(100, function ($schools) {
            foreach ($schools as $school) {
                app(CurrentSchool::class)->run((int) $school->id, function () use ($school) {
                    $gate = app(ModuleGate::class);
                    $gate->forget();
                    if ($gate->allows('early_warning')) {
                        foreach (GradeLevel::pluck('id') as $grade) {
                            EvaluateRisk::dispatch((int) $school->id, (int) schoolSetting('session_year'), (int) $grade);
                        }
                    }
                });
            }
        });
        $this->info('Enabled school evaluations queued.');

        return self::SUCCESS;
    }
}
