<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Platform\Ledger\CentsColumns;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Fills *_cents for fee rows written outside the models (raw inserts) and
 * reports any row whose cents and float disagree. Scheduled daily; safe to
 * run at any time.
 */
class BackfillCentsCommand extends Command
{
    protected $signature = 'money:backfill-cents {--chunk=1000 : rows per update}';

    protected $description = 'Fill missing *_cents money columns from their float columns and report mismatches';

    public function handle(): int
    {
        $changed = CentsColumns::backfill(max(1, (int) $this->option('chunk')));
        $mismatches = array_filter(CentsColumns::mismatches());

        $this->info("Filled {$changed} cents values.");
        foreach ($mismatches as $column => $rows) {
            $this->warn("{$column}: {$rows} rows disagree with their cents.");
        }
        Log::log($mismatches === [] ? 'info' : 'warning', 'money_cents_check', ['filled' => $changed, 'mismatches' => $mismatches]);

        return $mismatches === [] ? self::SUCCESS : self::FAILURE;
    }
}
