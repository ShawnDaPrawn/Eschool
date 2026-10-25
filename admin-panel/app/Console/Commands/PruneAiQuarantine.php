<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Platform\School;
use App\Services\Ai\Quarantine\AiQuarantine;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Console\Command;

class PruneAiQuarantine extends Command
{
    protected $signature = 'ai:prune-quarantine';

    protected $description = 'Delete expired flagged copies without changing metering history';

    public function handle(): int
    {
        $count = 0;
        // Retention also applies to disabled modules and inactive schools.
        foreach (School::pluck('id') as $id) {
            $count += app(CurrentSchool::class)->run((int) $id, function (): int {
                $service = app(AiQuarantine::class);
                $deleted = 0;
                do {
                    $deleted += $service->prune();
                } while ($service->hasExpired());

                return $deleted;
            });
        }
        $this->info('Expired flagged copies removed: '.$count);

        return self::SUCCESS;
    }
}
