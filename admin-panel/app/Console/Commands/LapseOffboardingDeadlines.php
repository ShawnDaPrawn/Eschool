<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Platform\Saas\PlatformOffboarding;
use Illuminate\Console\Command;

/** School offboarding (SA.11): a school that did not confirm receipt by its deadline is taken to have received its export. */
class LapseOffboardingDeadlines extends Command
{
    protected $signature = 'offboarding:receipt-deadlines';

    protected $description = 'Record receipt for leaving schools whose deadline to confirm their export has passed';

    public function handle(PlatformOffboarding $offboarding): int
    {
        $this->info('Receipt deadlines passed: '.$offboarding->lapseDeadlines());

        return self::SUCCESS;
    }
}
