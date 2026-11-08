<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Platform\School;
use App\Services\Platform\Tenancy\CurrentSchool;
use App\Services\SchoolBusiness\BursaryRetention;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

/** Bursary retention (Action Plan 5.10): runs for every school, module on or off. */
class PruneBursaryApplications extends Command
{
    protected $signature = 'bursaries:prune';

    protected $description = 'Erase due bursary household details, committee reasons and documents per school';

    public function handle(): int
    {
        $failed = false;
        School::orderBy('id')->each(function (School $school) use (&$failed): void {
            try {
                $erased = app(CurrentSchool::class)->run((int) $school->id, fn (): int => app(BursaryRetention::class)->run());
                if ($erased > 0) {
                    $this->line("School {$school->id}: {$erased} erased");
                }
            } catch (Throwable $error) {
                $failed = true;
                // Never log household details, reasons or file paths.
                Log::warning('Bursary retention needs retry.', ['school_id' => (int) $school->id, 'exception_class' => $error::class]);
            }
        });

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
