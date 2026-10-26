<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Platform\School;
use App\Services\Activities\Chat\GroupChatThreads;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Console\Command;

/** Retention is independent of account, school activation and module switches. */
class PruneGroupChat extends Command
{
    protected $signature = 'chat:prune-groups';

    protected $description = 'Redact expired private group conversation content in bounded school transactions';

    public function handle(): int
    {
        School::orderBy('id')->each(function (School $school): void {
            app(CurrentSchool::class)->run((int) $school->id, function (): void {
                for ($batch = 0; $batch < 100; $batch++) {
                    if (app(GroupChatThreads::class)->prune() < 500) {
                        break;
                    }
                }
            });
        });

        return self::SUCCESS;
    }
}
