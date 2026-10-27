<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Platform\School;
use App\Services\Money\FeeReminders;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendFeeReminders extends Command
{
    protected $signature = 'fees:remind';

    protected $description = 'Queue due school-local fee statement prompts with fresh parent ownership and cadence checks';

    public function handle(): int
    {
        $failed = false;
        School::active()->orderBy('id')->each(function (School $school) use (&$failed): void {
            try {
                app(CurrentSchool::class)->run($school, fn () => app(FeeReminders::class)->run());
            } catch (Throwable $error) {
                $failed = true;
                Log::warning('fee_reminder_retry_needed', ['school_id' => (int) $school->id, 'exception_class' => $error::class]);
            }
        });

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
