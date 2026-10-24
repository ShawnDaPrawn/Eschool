<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Platform\School;
use App\Services\Academics\Attendance\RegisterNotices;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Console\Command;

class SendRegisterAbsences extends Command
{
    protected $signature = 'academics:register-absence-alerts';

    protected $description = 'Queue current homeroom absence notices after each school cutoff';

    public function handle(): int
    {
        $count = 0;
        School::active()->each(function ($school) use (&$count) {
            $count += app(CurrentSchool::class)->run((int) $school->id, fn () => app(RegisterNotices::class)->due());
        });
        $this->info('Absence recipients queued: '.$count);

        return self::SUCCESS;
    }
}
