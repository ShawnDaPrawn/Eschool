<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\Platform\DispatchOutbox;
use Illuminate\Console\Command;

/**
 * Dispatches the outbox now, in this process, instead of waiting for the
 * scheduled run.
 */
class DispatchOutboxCommand extends Command
{
    protected $signature = 'outbox:dispatch';

    protected $description = 'Hand undispatched domain events to their listeners now';

    public function handle(): int
    {
        $stats = (new DispatchOutbox)->handle();
        $this->info(sprintf('Dispatched %d, failed %d, skipped %d.', $stats['dispatched'], $stats['failed'], $stats['skipped']));

        return self::SUCCESS;
    }
}
