<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Platform\School;
use App\Services\Emergency\EmergencyVisitors;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Console\Command;

/** Erase visitors' personal details after each school's retention period (Action Plan 5.8). */
class PurgeEmergencyVisitors extends Command
{
    protected $signature = 'emergency:purge-visitors';

    protected $description = 'Erase the personal details of gate visitors kept longer than the school allows';

    public function handle(): int
    {
        $count = 0;
        // Retention also applies to schools and modules that are switched off.
        foreach (School::pluck('id') as $id) {
            $count += app(CurrentSchool::class)->run((int) $id, fn (): int => app(EmergencyVisitors::class)->purge());
        }
        $this->info('Visitor records erased: '.$count);

        return self::SUCCESS;
    }
}
