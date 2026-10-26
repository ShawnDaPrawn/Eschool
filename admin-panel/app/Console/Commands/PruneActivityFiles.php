<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Platform\School;
use App\Services\Activities\ActivityFiles;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class PruneActivityFiles extends Command
{
    protected $signature = 'activities:prune-material-files';

    protected $description = 'Remove old unreachable activity ciphertext in bounded school batches';

    public function handle(): int
    {
        $failed = false;
        School::orderBy('id')->each(function (School $school) use (&$failed): void {
            try {
                app(CurrentSchool::class)->run((int) $school->id, fn () => app(ActivityFiles::class)->pruneOrphans());
            } catch (Throwable $error) {
                $failed = true;
                Log::warning('Private activity document cleanup needs retry.', ['school_id' => (int) $school->id, 'exception_class' => $error::class]);
            }
        });

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
