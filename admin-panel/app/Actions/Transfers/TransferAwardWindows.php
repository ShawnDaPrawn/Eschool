<?php

declare(strict_types=1);

namespace App\Actions\Transfers;

use App\Models\Recognition\AwardCategory;
use App\Models\Recognition\AwardWindow;
use App\Models\SessionYear;
use App\Models\User;
use App\Services\Recognition\RecognitionAuthority;
use App\Services\Recognition\RecognitionCatalog;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;

/**
 * Copies the year's award nomination windows into the new year as drafts,
 * their dates moved by the gap between the two years' start dates and kept
 * inside the new year. Windows of activity-group categories are not copied:
 * they belong to a season's group, which the school chooses again. A new year
 * that already has award windows is left alone. Specification:
 * engineering/specs/academics/new-year-structures.md (Action Plan 1.19).
 */
class TransferAwardWindows
{
    public function execute(int $fromSessionId, int $toSessionId): void
    {
        $actor = auth()->user();
        abort_unless($actor instanceof User, 403);
        app(RecognitionAuthority::class)->manage($actor, 'awards');
        $from = SessionYear::query()->findOrFail($fromSessionId);
        $to = SessionYear::query()->findOrFail($toSessionId);
        $shift = (int) CarbonImmutable::parse($from->start_date)->diffInDays(CarbonImmutable::parse($to->start_date), false);
        $first = CarbonImmutable::parse($to->start_date)->toDateString();
        $last = CarbonImmutable::parse($to->end_date)->toDateString();

        DB::transaction(function () use ($actor, $from, $to, $shift, $first, $last) {
            if (AwardWindow::query()->where('session_year_id', $to->id)->exists()) {
                return;
            }
            $categories = AwardCategory::query()->where('active', true)->where('scope_kind', '!=', 'group')->pluck('id')->flip();
            foreach (AwardWindow::query()->where('session_year_id', $from->id)->whereNull('activity_group_id')->orderBy('id')->get() as $window) {
                if (! $categories->has((int) $window->award_category_id)) {
                    continue;
                }
                $opens = max($first, min($last, CarbonImmutable::parse($window->opens_on)->addDays($shift)->toDateString()));
                $closes = max($opens, min($last, CarbonImmutable::parse($window->closes_on)->addDays($shift)->toDateString()));
                app(RecognitionCatalog::class)->window($actor, [
                    'operation_id' => Uuid::uuid5(Uuid::NAMESPACE_URL, 'award-window-carry:'.$window->id.':'.$to->id)->toString(),
                    'revision' => 0, 'award_category_id' => (int) $window->award_category_id, 'session_year_id' => (int) $to->id,
                    'opens_on' => $opens, 'closes_on' => $closes, 'status' => 'draft',
                ]);
            }
        });
    }
}
