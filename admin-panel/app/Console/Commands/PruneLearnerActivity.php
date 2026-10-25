<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Leadership\LearnerActivityDay;
use App\Models\Platform\School;
use App\Services\Academics\AcademicAuthority;
use App\Services\Platform\Tenancy\CurrentSchool;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

class PruneLearnerActivity extends Command
{
    protected $signature = 'leadership:prune-activity';

    protected $description = 'Remove daily learner activity observations older than ninety days';

    public function handle(): int
    {
        School::query()->orderBy('id')->each(function ($school): void {
            app(CurrentSchool::class)->run((int) $school->id, function (): void {
                $before = CarbonImmutable::parse(app(AcademicAuthority::class)->today())->subDays(89)->toDateString();
                LearnerActivityDay::whereDate('on_date', '<', $before)->delete();
            });
        });

        return self::SUCCESS;
    }
}
