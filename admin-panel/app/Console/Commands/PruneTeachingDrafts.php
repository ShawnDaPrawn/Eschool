<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Platform\School;
use App\Services\Learning\TeachingDraftRetention;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Console\Command;

class PruneTeachingDrafts extends Command
{
    protected $signature = 'learning:prune-teaching-drafts';

    protected $description = 'Erase expired temporary teaching proposals and script crops without changing official records';

    public function handle(): int
    {
        $count = 0;
        foreach (School::pluck('id') as $id) {
            $count += app(CurrentSchool::class)->run((int) $id, function () {
                $total = 0;
                do {
                    $removed = app(TeachingDraftRetention::class)->prune();
                    $total += $removed;
                } while ($removed > 0);

                return $total;
            });
        }
        $this->info('Expired temporary teaching copies removed: '.$count);

        return self::SUCCESS;
    }
}
