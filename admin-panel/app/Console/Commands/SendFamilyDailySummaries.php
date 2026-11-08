<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\FamilySummary\FamilyDailySummaries;
use Illuminate\Console\Command;

/** The family.daily_summary job (Action Plan 5.11). */
class SendFamilyDailySummaries extends Command
{
    protected $signature = 'family:daily-summary {--retention : Erase expired summaries and every school part of them}';

    protected $description = 'Send each opted-in guardian one daily summary across all their children and schools, at their chosen time';

    public function handle(FamilyDailySummaries $summaries): int
    {
        if ($this->option('retention')) {
            $this->info('Erased '.$summaries->prune().' expired family summaries.');

            return self::SUCCESS;
        }
        $counts = $summaries->run();
        $this->info(json_encode($counts, JSON_THROW_ON_ERROR));

        return $counts['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
