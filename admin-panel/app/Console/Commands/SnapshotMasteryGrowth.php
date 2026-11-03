<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\Growth\CaptureMasteryGrowth;
use App\Models\Platform\School;
use App\Services\Growth\MasteryGrowth;
use App\Services\Platform\Modules\ModuleGate;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Console\Command;
use Throwable;

class SnapshotMasteryGrowth extends Command
{
    protected $signature = 'mastery:growth-snapshots {school? : Active school ID; omitted means every school with mastery on}
        {--backfill : Capture the current term now, in this process, instead of queueing}';

    protected $description = 'Capture term-start and latest mastery per learner, topic and term (counts only in the output)';

    public function handle(CurrentSchool $schools, MasteryGrowth $growth): int
    {
        $id = $this->argument('school');
        if ($id !== null && (! ctype_digit((string) $id) || ! School::query()->active()->whereKey($id)->exists())) {
            $this->error('Choose an active school ID.');

            return self::FAILURE;
        }
        $backfill = (bool) $this->option('backfill');
        $failed = false;
        School::query()->active()->when($id !== null, fn ($q) => $q->whereKey((int) $id))->orderBy('id')->chunkById(100, function ($list) use ($schools, $growth, $backfill, $id, &$failed) {
            foreach ($list as $school) {
                $schools->run((int) $school->id, function () use ($school, $growth, $backfill, $id, &$failed) {
                    $gate = app(ModuleGate::class);
                    $gate->forget();
                    if (! $gate->allows('mastery')) {
                        if ($id !== null) {
                            $this->error('Enable the mastery module before capturing growth.');
                            $failed = true;
                        }

                        return;
                    }
                    if (! $backfill) {
                        CaptureMasteryGrowth::dispatch((int) $school->id);

                        return;
                    }
                    try {
                        $result = $growth->capture();
                        $this->info("School {$school->id}: {$result['rows']} snapshot rows across {$result['terms']} terms; {$result['ambiguous_terms']} overlapping terms skipped; {$result['invalid']} invalid.");
                    } catch (Throwable) {
                        $this->error("School {$school->id}: capture refused. Review the term dates and mastery evidence, then retry.");
                        $failed = true;
                    }
                });
            }
        });
        if (! $backfill && ! $failed) {
            $this->info('Growth snapshots queued for schools with mastery on.');
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
