<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Platform\School;
use App\Services\Gallery\GalleryRetention;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class PruneGalleryCopies extends Command
{
    protected $signature = 'gallery:prune-copies';

    protected $description = 'Erase withdrawn private photos and old rollback orphans, preserving consent metadata';

    public function handle(): int
    {
        $failed = false;
        School::orderBy('id')->each(function (School $school) use (&$failed): void {
            try {
                app(CurrentSchool::class)->run((int) $school->id, fn () => app(GalleryRetention::class)->prune());
            } catch (Throwable $error) {
                $failed = true;
                Log::warning('Private gallery retention needs retry.', ['school_id' => (int) $school->id, 'exception_class' => $error::class]);
            }
        });

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
