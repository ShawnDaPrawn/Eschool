<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Platform\School;
use App\Services\Academics\AcademicAuthority;
use App\Services\Learning\PracticeMedia;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Console\Command;

/** Learners' recorded practice answers are kept only until their retention date (Action Plan 3.11). */
class PrunePracticeAudio extends Command
{
    protected $signature = 'learning:prune-practice-audio';

    protected $description = 'Delete recorded practice answers past their retention date; the marks stay';

    public function handle(): int
    {
        $count = 0;
        foreach (School::pluck('id') as $id) {
            $count += app(CurrentSchool::class)->run((int) $id, function () {
                $today = app(AcademicAuthority::class)->today();
                $total = 0;
                do {
                    $removed = app(PracticeMedia::class)->pruneAudio($today);
                    $total += $removed;
                } while ($removed > 0);

                return $total;
            });
        }
        $this->info('Recorded practice answers deleted: '.$count);

        return self::SUCCESS;
    }
}
