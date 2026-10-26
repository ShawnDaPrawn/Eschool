<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Platform\School;
use App\Services\Assistance\SchoolSupportThreads;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Console\Command;

class PruneSchoolAssistance extends Command
{
    protected $signature = 'assistance:prune-private';

    protected $description = 'Erase expired school-support message bodies while retaining minimal operation receipts';

    public function handle(): int
    {
        $total = 0;
        // Privacy expiry applies after module withdrawal and school suspension.
        foreach (School::orderBy('id')->pluck('id') as $id) {
            $total += app(CurrentSchool::class)->run((int) $id, function (): int {
                $count = 0;
                do {
                    $removed = app(SchoolSupportThreads::class)->purgeExpired();
                    $count += $removed;
                } while ($removed === 200);

                return $count;
            });
        }
        $this->info('Expired school-support threads erased: '.$total);

        return self::SUCCESS;
    }
}
