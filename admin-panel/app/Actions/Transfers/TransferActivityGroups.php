<?php

declare(strict_types=1);

namespace App\Actions\Transfers;

use App\Models\Activities\ActivityGroup;
use App\Models\SessionYear;
use App\Models\User;
use App\Services\Activities\ActivityGroups;
use App\Services\Activities\CommunityAuthority;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Copies the year's activity groups (teams, clubs and societies, with their
 * tree, discipline, season and eligibility rules) into the new year, with
 * nobody in them: members, captains and staff are allocated afresh, so last
 * year's Grade 8 learners are never left in this year's Grade 8 team.
 *
 * Seasons move by the gap between the two years' start dates and stay inside
 * the new year and inside their parent's season. A group the new year already
 * has (same name under the same parent) is left alone. Specification:
 * engineering/specs/academics/new-year-structures.md (Action Plan 1.19).
 */
class TransferActivityGroups
{
    public function execute(int $fromSessionId, int $toSessionId): void
    {
        $actor = auth()->user();
        abort_unless($actor instanceof User, 403);
        app(CommunityAuthority::class)->manage($actor);
        $from = SessionYear::query()->findOrFail($fromSessionId);
        $to = SessionYear::query()->findOrFail($toSessionId);
        $shift = (int) CarbonImmutable::parse($from->start_date)->diffInDays(CarbonImmutable::parse($to->start_date), false);
        $yearStart = CarbonImmutable::parse($to->start_date)->toDateString();
        $yearEnd = CarbonImmutable::parse($to->end_date)->toDateString();

        DB::transaction(function () use ($actor, $fromSessionId, $toSessionId, $shift, $yearStart, $yearEnd) {
            $groups = ActivityGroup::query()->where('session_year_id', $fromSessionId)->where('active', true)->orderBy('id')->get()->keyBy('id');
            $map = [];
            $ranges = [];
            // Parents before children: walk the tree level by level.
            $pending = $groups->all();
            while ($pending !== []) {
                $progress = false;
                foreach ($pending as $id => $group) {
                    $parentId = $group->parent_id === null ? null : (int) $group->parent_id;
                    if ($parentId !== null && $groups->has($parentId) && ! array_key_exists($parentId, $map)) {
                        continue;
                    }
                    unset($pending[$id]);
                    $progress = true;
                    // A parent that was archived (not carried) leaves its child at the top level.
                    $newParent = $parentId === null ? null : ($map[$parentId] ?? null);
                    [$lowest, $highest] = $newParent === null ? [$yearStart, $yearEnd] : $ranges[$newParent];
                    $start = $this->clamp(CarbonImmutable::parse($group->starts_on)->addDays($shift)->toDateString(), $lowest, $highest);
                    $end = $this->clamp(CarbonImmutable::parse($group->ends_on)->addDays($shift)->toDateString(), $start, $highest);

                    $existing = ActivityGroup::query()->where('session_year_id', $toSessionId)->where('name', $group->name)
                        ->where(fn ($q) => $newParent === null ? $q->whereNull('parent_id') : $q->where('parent_id', $newParent))->first();
                    if ($existing !== null) {
                        $map[$id] = (int) $existing->id;
                        $ranges[(int) $existing->id] = [$existing->starts_on->toDateString(), $existing->ends_on->toDateString()];

                        continue;
                    }
                    $saved = app(ActivityGroups::class)->save($actor, null, [
                        'operation_id' => (string) Str::uuid(), 'revision' => 0, 'session_year_id' => $toSessionId, 'parent_id' => $newParent,
                        'name' => $group->name, 'discipline' => $group->discipline, 'season' => $group->season,
                        'starts_on' => $start, 'ends_on' => $end, 'gender' => $group->gender,
                        'minimum_grade' => $group->minimum_grade, 'maximum_grade' => $group->maximum_grade,
                        'minimum_age' => $group->minimum_age, 'maximum_age' => $group->maximum_age, 'active' => true,
                    ]);
                    $map[$id] = (int) $saved['id'];
                    $ranges[(int) $saved['id']] = [$start, $end];
                }
                if (! $progress) {
                    break;
                }
            }
        });
    }

    private function clamp(string $date, string $lowest, string $highest): string
    {
        return max($lowest, min($highest, $date));
    }
}
