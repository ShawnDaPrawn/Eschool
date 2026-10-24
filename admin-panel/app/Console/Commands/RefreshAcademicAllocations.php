<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Platform\School;
use App\Services\Academics\AcademicProjections;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Console\Command;

class RefreshAcademicAllocations extends Command
{
    protected $signature = 'academics:refresh-allocations {--school= : Limit to one active school}';

    protected $description = 'Refresh stable existing-app projections from current dated academic allocations';

    public function handle(): int
    {
        School::query()->active()->when($this->option('school'), fn ($q, $id) => $q->whereKey((int) $id))->orderBy('id')->each(function ($school) {
            app(CurrentSchool::class)->run((int) $school->id, fn () => app(AcademicProjections::class)->refresh());
            $this->line('School '.$school->id.': academic allocations refreshed');
        });

        return self::SUCCESS;
    }
}
