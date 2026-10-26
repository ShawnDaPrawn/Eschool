<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Platform\School;
use App\Services\Learning\ExamWorkingPhotos;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Console\Command;

class PruneExamWorking extends Command
{
    protected $signature = 'exam-coach:prune-working';

    protected $description = 'Erase expired exam working derivatives, preserving curriculum and metering';

    public function handle(): int
    {
        // Expiry applies even after a school or the exam module is disabled.
        foreach (School::orderBy('id')->pluck('id') as $id) {
            app(CurrentSchool::class)->run((int) $id, function (): void {
                do {
                    $count = app(ExamWorkingPhotos::class)->prune();
                } while ($count === 500);
            });
        }

        return self::SUCCESS;
    }
}
