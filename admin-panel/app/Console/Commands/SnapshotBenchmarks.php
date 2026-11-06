<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Leadership\Benchmarking\BenchmarkSnapshots;
use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;

/**
 * The weekly anonymous benchmarking snapshot (Action Plan 5.5): each
 * participating school's own figures, then each comparison group's published
 * median and quartiles. Prints counts only, never a school.
 */
class SnapshotBenchmarks extends Command
{
    protected $signature = 'benchmarking:snapshot {--period= : The Sunday that ends the week (default: last Sunday)}';

    protected $description = 'Work out the weekly anonymous benchmarks between comparable schools';

    public function handle(BenchmarkSnapshots $snapshots): int
    {
        $period = $this->option('period');
        try {
            $result = $snapshots->run(is_string($period) ? $period : null);
        } catch (ValidationException $e) {
            $this->error((string) collect($e->errors())->flatten()->first());

            return self::INVALID;
        }
        $this->info(sprintf('Benchmarks for the week ending %s: %d schools, %d failed; %d groups published, %d held, %d too small.',
            $result['period_end'], $result['schools'], $result['failed'], $result['released'], $result['held'], $result['withheld']));

        return $result['failed'] === 0 ? self::SUCCESS : self::FAILURE;
    }
}
