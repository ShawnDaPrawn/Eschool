<?php

declare(strict_types=1);

namespace App\Actions\Transfers;

use App\Models\Academics\TeachingGroup;
use App\Models\Learning\PracticeItem;
use App\Models\Learning\PracticeItemGroup;
use App\Models\User;
use App\Services\Academics\AcademicAuthority;
use App\Services\Platform\Audit\Audit;
use Illuminate\Support\Facades\DB;

/**
 * Keeps teachers' practice questions in use across the year change: each
 * active question used in an active group of the old year is used in that
 * group's copy in the new year (same subject, grade and name, as
 * TransferTeachingGroups makes it). The questions themselves never move: they
 * belong to their subject and grade's bank, which outlives every group. A use
 * the new year already has is left alone. Specification:
 * engineering/specs/learning/question-banks.md (Action Plan 3.6, with 1.19).
 */
class TransferPracticeBanks
{
    public function execute(int $fromSessionId, int $toSessionId): void
    {
        $actor = auth()->user();
        abort_unless($actor instanceof User, 403);
        app(AcademicAuthority::class)->manage($actor);

        DB::transaction(function () use ($actor, $fromSessionId, $toSessionId) {
            $targets = TeachingGroup::query()->where('session_year_id', $toSessionId)->where('active', true)->orderBy('id')->get(['id', 'subject_id', 'grade_level_id', 'name'])
                ->groupBy(fn ($group) => self::key($group))->map(fn ($same) => (int) $same->first()->id);
            $sources = TeachingGroup::query()->where('session_year_id', $fromSessionId)->where('active', true)->get(['id', 'subject_id', 'grade_level_id', 'name'])->keyBy('id');
            $uses = PracticeItemGroup::query()->whereIn('teaching_group_id', $sources->keys())
                ->whereIn('practice_item_id', PracticeItem::query()->where('active', true)->select('id'))->orderBy('id')->get();
            $added = 0;
            foreach ($uses as $use) {
                $target = $targets->get(self::key($sources[$use->teaching_group_id]));
                if ($target === null) {
                    continue;
                }
                $row = PracticeItemGroup::query()->firstOrCreate(['practice_item_id' => $use->practice_item_id, 'teaching_group_id' => $target], ['added_by' => $actor->id]);
                $added += $row->wasRecentlyCreated ? 1 : 0;
            }
            Audit::record('learning.practice_banks_carried', null, [], ['from_session_year_id' => $fromSessionId, 'to_session_year_id' => $toSessionId, 'uses' => $added]);
        });
    }

    private static function key(TeachingGroup $group): string
    {
        return $group->subject_id.'|'.$group->grade_level_id.'|'.$group->name;
    }
}
