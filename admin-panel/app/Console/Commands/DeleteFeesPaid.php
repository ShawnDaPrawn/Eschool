<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Platform\Tenancy\CurrentSchool;
use App\Services\Platform\Tenancy\SchoolTable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class DeleteFeesPaid extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:delete-fees {--school=1 : The school whose fee records are deleted}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete Fees Data';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // One school's fee records, never every school's.
        app(CurrentSchool::class)->run((int) $this->option('school'), function () {
            SchoolTable::query('fees_choiceables')->delete();
            SchoolTable::query('payment_transactions')->delete();
            SchoolTable::query('fees_paids')->delete();
        });
        Log::info('data-delete');

        return 0;
    }
}
