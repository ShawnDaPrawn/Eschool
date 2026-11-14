<?php

declare(strict_types=1);

namespace App\Services\Learning\Library;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * What teachers' ratings of shared library items add up to (Action Plan 5.4).
 * Ratings are school-owned rows; this is the one place that reads them across
 * schools, and only as numbers and unnamed comments: never who rated, from
 * which school, or when. Ratings from the school that shared an item never
 * count towards it. Teachers see an average and a count only once
 * `shared_library.rating_threshold` ratings count (3 by default), and the
 * comments only then; reviewers at HQ always see them, still without names.
 */
class LibraryRatings
{
    /** How many comments an item page shows, newest first. */
    public const COMMENTS = 20;

    public static function threshold(): int
    {
        return max(1, (int) config('shared_library.rating_threshold', 3));
    }

    /**
     * The counted ratings of each item, keyed by item id: count and average
     * (one decimal), for every item with at least one counted rating.
     *
     * @param  list<int>  $itemIds
     * @return array<int, array{count: int, average: float}>
     */
    public function totals(array $itemIds): array
    {
        if ($itemIds === []) {
            return [];
        }
        $rows = $this->counted()->whereIn('r.library_item_id', $itemIds)->groupBy('r.library_item_id')
            ->selectRaw('r.library_item_id as item_id, COUNT(*) as ratings, SUM(r.stars) as stars')->get();
        $totals = [];
        foreach ($rows as $row) {
            $count = (int) $row->ratings;
            // Integer arithmetic to one decimal place: tenths of a star, rounded half up.
            $totals[(int) $row->item_id] = ['count' => $count, 'average' => round(intdiv((int) $row->stars * 20 + $count, $count * 2) / 10, 1)];
        }

        return $totals;
    }

    /**
     * What a teacher at any school may see: the average and count only from
     * the threshold up, otherwise null.
     *
     * @param  list<int>  $itemIds
     * @return array<int, array{count: int, average: float}|null>
     */
    public function shown(array $itemIds): array
    {
        $totals = $this->totals($itemIds);
        $shown = [];
        foreach ($itemIds as $id) {
            $total = $totals[$id] ?? null;
            $shown[$id] = $total !== null && $total['count'] >= self::threshold() ? $total : null;
        }

        return $shown;
    }

    /**
     * Counted comments of one item, newest first, without any name or date.
     *
     * @return list<array{stars: int, comment: string}>
     */
    public function comments(int $itemId, bool $counted = true): array
    {
        $query = $counted ? $this->counted() : DB::table('library_ratings as r');

        return $query->where('r.library_item_id', $itemId)->whereNotNull('r.comment')->where('r.comment', '!=', '')
            ->orderByDesc('r.updated_at')->orderByDesc('r.id')->limit(self::COMMENTS)->get(['r.stars', 'r.comment'])
            ->map(fn ($row) => ['stars' => (int) $row->stars, 'comment' => (string) $row->comment])->all();
    }

    /** How many ratings the sharing school's own teachers gave (stored, never counted), for reviewers. */
    public function ownSchool(int $itemId): int
    {
        return DB::table('library_ratings as r')->join('library_items as i', 'i.id', '=', 'r.library_item_id')
            ->join('library_sources as s', 's.id', '=', 'i.library_source_id')->where('r.library_item_id', $itemId)
            ->whereColumn('r.school_id', 's.contributor_school_id')->count();
    }

    /** Ratings that count: every school's except the one that shared the item. */
    private function counted(): Builder
    {
        return DB::table('library_ratings as r')->join('library_items as i', 'i.id', '=', 'r.library_item_id')
            ->join('library_sources as s', 's.id', '=', 'i.library_source_id')
            ->where(fn ($q) => $q->whereNull('s.contributor_school_id')->orWhereColumn('r.school_id', '!=', 's.contributor_school_id'));
    }
}
