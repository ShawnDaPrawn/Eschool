<?php

declare(strict_types=1);

namespace App\Services\Activities;

use App\Models\Activities\ActivityGroup;
use App\Models\Platform\School;
use App\Models\SessionYear;
use App\Models\User;
use App\Services\Platform\Tenancy\CurrentSchool;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Ramsey\Uuid\Uuid;

/**
 * Season rollover (Action Plan 1.22): the summer teams become the winter
 * teams. Every active group of one season in the year is copied as a new
 * group for the next season, with new dates and nobody in it, keeping its
 * place in the tree, discipline and eligibility rules; members, captains and
 * staff are allocated afresh, as at year end (TransferActivityGroups). The
 * old season's groups and their history stay as they are. A group the new
 * season already has (same name and parent) is left alone.
 * engineering/specs/activities/season-rollover.md.
 */
class ActivitySeasons
{
    public function __construct(private readonly CommunityAuthority $access) {}

    /**
     * The year's seasons with how many active groups each has.
     *
     * @return array<string, int>
     */
    public function seasons(User $actor, int $yearId): array
    {
        $this->access->manage($actor);

        return ActivityGroup::query()->where('session_year_id', $yearId)->where('active', true)
            ->selectRaw('season, count(*) as total')->groupBy('season')->orderBy('season')->pluck('total', 'season')
            ->mapWithKeys(fn ($total, $season) => [(string) $season => (int) $total])->all();
    }

    /**
     * @param  array{session_year_id: int|string, from: string, to: string, starts_on: string, ends_on: string}  $input
     * @return array{created: int, skipped: int}
     */
    public function rollover(User $actor, array $input): array
    {
        $this->access->manage($actor);
        $year = SessionYear::query()->findOrFail((int) $input['session_year_id']);
        $from = trim((string) $input['from']);
        $to = trim(preg_replace('/\s+/', ' ', (string) $input['to']) ?? '');
        $start = (string) $input['starts_on'];
        $end = (string) $input['ends_on'];
        $first = CarbonImmutable::parse($year->start_date)->toDateString();
        $last = CarbonImmutable::parse($year->end_date)->toDateString();
        if ($to === '' || mb_strlen($to) > 64 || mb_strtolower($to) === mb_strtolower($from)) {
            throw ValidationException::withMessages(['to' => __('Name the new season, different from the one it follows.')]);
        }
        if ($start > $end || $start < $first || $end > $last) {
            throw ValidationException::withMessages(['starts_on' => __('The new season must start before it ends, within :year.', ['year' => $year->name])]);
        }

        return DB::transaction(function () use ($actor, $year, $from, $to, $start, $end) {
            // Activity operation receipts belong to one actor. Lock before
            // checking destinations so different administrators cannot copy
            // the same season into duplicate trees.
            School::whereKey(app(CurrentSchool::class)->id())->lockForUpdate()->firstOrFail();
            $this->access->manage($actor);
            $groups = ActivityGroup::query()->where('session_year_id', $year->id)->where('active', true)->where('season', $from)->orderBy('id')->get()->keyBy('id');
            if ($groups->isEmpty()) {
                throw ValidationException::withMessages(['from' => __('No active groups in the season ":season".', ['season' => $from])]);
            }
            $map = [];
            $ranges = [];
            $created = 0;
            $skipped = 0;
            // Parents before children: a child of a copied group goes under its copy;
            // a child of a group outside the season (a whole-year club) stays under it.
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
                    $newParent = $parentId === null ? null : ($groups->has($parentId) ? $map[$parentId] : $parentId);
                    if ($parentId !== null && $groups->has($parentId) && $newParent === null) {
                        // Its parent was skipped: so is the child.
                        $map[$id] = null;
                        $skipped++;

                        continue;
                    }
                    [$lowest, $highest] = $newParent === null ? [$start, $end] : ($ranges[$newParent] ?? $this->range($newParent));
                    $childStart = max($start, $lowest);
                    $childEnd = min($end, $highest);
                    $exists = ActivityGroup::query()->where('session_year_id', $year->id)->where('season', $to)->where('name', $group->name)
                        ->where(fn ($q) => $newParent === null ? $q->whereNull('parent_id') : $q->where('parent_id', $newParent))->first();
                    if ($exists !== null || $childStart > $childEnd) {
                        $map[$id] = $exists === null ? null : (int) $exists->id;
                        $skipped++;

                        continue;
                    }
                    $saved = app(ActivityGroups::class)->save($actor, null, [
                        'operation_id' => Uuid::uuid5(Uuid::NAMESPACE_URL, 'season-rollover:'.$group->id.':'.mb_strtolower($to))->toString(),
                        'revision' => 0, 'session_year_id' => (int) $year->id, 'parent_id' => $newParent,
                        'name' => $group->name, 'discipline' => $group->discipline, 'season' => $to,
                        'starts_on' => $childStart, 'ends_on' => $childEnd, 'gender' => $group->gender,
                        'minimum_grade' => $group->minimum_grade, 'maximum_grade' => $group->maximum_grade,
                        'minimum_age' => $group->minimum_age, 'maximum_age' => $group->maximum_age, 'active' => true,
                    ]);
                    $map[$id] = (int) $saved['id'];
                    $ranges[(int) $saved['id']] = [$childStart, $childEnd];
                    $created++;
                }
                if (! $progress) {
                    break;
                }
            }

            return ['created' => $created, 'skipped' => $skipped];
        }, 3);
    }

    /** @return array{0: string, 1: string} */
    private function range(int $groupId): array
    {
        $group = ActivityGroup::query()->findOrFail($groupId);

        return [$group->starts_on->toDateString(), $group->ends_on->toDateString()];
    }
}
