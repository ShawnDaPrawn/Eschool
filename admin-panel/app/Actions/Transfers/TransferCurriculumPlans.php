<?php

declare(strict_types=1);

namespace App\Actions\Transfers;

use App\Models\Academics\CurriculumPlan;
use App\Models\User;
use App\Policies\Academics\CurriculumPolicy;
use App\Services\Academics\CurriculumPlans;
use Illuminate\Support\Facades\DB;

/** Copies definition history only; every target version starts unpublished. */
class TransferCurriculumPlans
{
    public function execute(int $fromSessionId, int $toSessionId): void
    {
        $actor = auth()->user();
        abort_unless($actor instanceof User, 403);
        app(CurriculumPolicy::class)->authorise($actor, 'publish');
        DB::transaction(function () use ($actor, $fromSessionId, $toSessionId) {
            // Keep all definition versions: later versions may publish only one term.
            CurriculumPlan::query()->where('session_year_id', $fromSessionId)->orderBy('version')->orderBy('id')
                ->get()->each(fn ($plan) => app(CurriculumPlans::class)->copy($actor, (int) $plan->id, $toSessionId));
        });
    }
}
