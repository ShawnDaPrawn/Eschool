<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Platform\Saas\PlatformOffboarding;
use Illuminate\Console\Command;

/** School offboarding (SA.11): queue again every erasure that has made no progress for a while (its worker stopped part way). */
class ResumeSchoolErasures extends Command
{
    protected $signature = 'offboarding:resume-erasures';

    protected $description = 'Resume school erasures that stopped part way';

    public function handle(PlatformOffboarding $offboarding): int
    {
        $this->info('Erasures resumed: '.$offboarding->resumeStalledErasures());

        return self::SUCCESS;
    }
}
