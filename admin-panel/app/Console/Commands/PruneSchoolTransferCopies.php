<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Platform\School;
use App\Services\Platform\Tenancy\CurrentSchool;
use App\Services\Transfers\TransferRetention;
use App\Services\Transfers\TransferVault;
use Illuminate\Console\Command;

class PruneSchoolTransferCopies extends Command
{
    protected $signature = 'transfers:prune-copies';

    protected $description = 'Expire uncommitted stages and source hand-off copies without deleting received educational records';

    public function handle(): int
    {
        app(TransferVault::class)->pruneScratch();
        School::orderBy('id')->each(fn ($s) => app(CurrentSchool::class)->run((int) $s->id, fn () => app(TransferRetention::class)->expire()));

        return self::SUCCESS;
    }
}
