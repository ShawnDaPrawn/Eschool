<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Platform\School;
use App\Services\Platform\Saas\PlatformSchoolHealth;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * The nightly school health figures for HQ (Action Plan SA.10): one row per
 * school that is not archived, for the school's own date. A school that fails
 * is logged by id and the others still run. Prints counts only.
 */
class SnapshotSchoolHealth extends Command
{
    protected $signature = 'platform:school-health {--school= : Only this school id}';

    protected $description = 'Compute and keep each school\'s health and usage figures for HQ';

    public function handle(PlatformSchoolHealth $health): int
    {
        $only = $this->option('school');
        $schools = School::query()->where('status', '!=', School::ARCHIVED)
            ->when($only !== null && $only !== '', fn ($query) => $query->whereKey((int) $only))->orderBy('id')->pluck('id');
        $failed = 0;
        foreach ($schools as $id) {
            try {
                $health->snapshot((int) $id);
            } catch (Throwable $e) {
                $failed++;
                Log::error('School health snapshot failed', ['school_id' => (int) $id, 'exception_class' => $e::class]);
            }
        }
        $this->info(sprintf('School health: %d schools computed, %d failed.', $schools->count() - $failed, $failed));

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }
}
