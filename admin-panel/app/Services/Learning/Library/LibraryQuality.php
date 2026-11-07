<?php

declare(strict_types=1);

namespace App\Services\Learning\Library;

use App\Models\Academics\GradeLevel;
use App\Models\Library\LibraryItem;
use App\Models\Library\LibraryItemVersion;
use App\Models\User;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Quality signals for library items from learners' answers (Action Plan 3.4,
 * SA.16). For each published library practice question, learners' answers in
 * daily practice across every school with the library switched on are
 * counted in the database: how many answers, how many right, how many
 * learners and how many schools. A group smaller than MINIMUM_LEARNERS
 * learners or MINIMUM_SCHOOLS schools never leaves the query (HAVING), so no
 * school and no learner can be picked out. Whether a question needs attention
 * is decided in the same query (HAVING on the band), so the flagged list is
 * complete however many healthy questions there are, and is paged after that.
 * What HQ sees is a count band and a rate: never a school's name or id, a
 * learner, an answer or a date. Nothing is stored: the signals are computed
 * when HQ reads them, and nothing a school wrote is copied into the library.
 */
class LibraryQuality
{
    public const MINIMUM_LEARNERS = 20;

    public const MINIMUM_SCHOOLS = 3;

    /** Facility (share of answers right) outside this band, in basis points, needs a look. */
    public const LOW_BP = 3000;

    public const HIGH_BP = 9500;

    public const PER_PAGE = 100;

    public const SIGNALS = ['hard' => 'Far harder than expected: check the answer key, the wording and the grade', 'easy' => 'Far easier than expected: check it is not giving the answer away'];

    private const CORRECT = 'SUM(CASE WHEN r.correct THEN 1 ELSE 0 END)';

    /**
     * One item's signal, looked up directly, or null when its current
     * published version has too few learners or schools.
     *
     * @return array{version_id: int, answers: int, learners: int, facility_bp: int, signal: ?string}|null
     */
    public function signal(int $itemId): ?array
    {
        $row = $this->read(fn () => $this->aggregate()->where('i.id', $itemId)->first());

        return $row === null ? null : $this->shape($row);
    }

    /**
     * Flagged items only, worst first, a page at a time.
     *
     * @return array{rows: list<array{item_id: int, version_id: int, answers: int, learners: int, facility_bp: int, signal: ?string}>, total: int}
     */
    public function flagged(int $page = 1, int $perPage = self::PER_PAGE): array
    {
        $page = max(1, min(100000, $page));
        $perPage = max(1, min(500, $perPage));

        return $this->read(function () use ($page, $perPage) {
            $rows = $this->flaggedQuery()->orderByRaw('ABS('.self::CORRECT.' * 10000 / COUNT(*) - 5000) DESC')->orderBy('i.id')
                ->offset(($page - 1) * $perPage)->limit($perPage)->get();

            return ['rows' => $rows->map(fn ($row) => ['item_id' => (int) $row->item_id] + $this->shape($row))->all(), 'total' => $this->flaggedCount()];
        });
    }

    public function flaggedCount(): int
    {
        return $this->read(fn () => DB::query()->fromSub($this->flaggedQuery()->select('i.id'), 'flagged')->count());
    }

    /**
     * Which of these items are flagged (for badges on a page of rows).
     *
     * @param  list<int>  $itemIds
     * @return list<int>
     */
    public function flaggedAmong(array $itemIds): array
    {
        if ($itemIds === []) {
            return [];
        }

        return $this->read(fn () => $this->flaggedQuery()->whereIn('i.id', $itemIds)->pluck('item_id')->map(fn ($id) => (int) $id)->all());
    }

    /**
     * The flagged item ids as a subquery, for filtering another query before it
     * is paged (the review queue): never a truncated list.
     */
    public function flaggedItemIdsQuery(): Builder
    {
        abort_if(app(CurrentSchool::class)->isSet(), 403, 'Library quality signals are read only from HQ.');

        return $this->flaggedQuery()->select('i.id');
    }

    /** The /saas/library/attention page: flagged items, furthest from the expected band first, a page at a time. */
    public function attention(User $actor, int $page = 1): array
    {
        app(LibraryAccess::class)->actor($actor, LibraryAccess::MANAGE);
        $flagged = $this->flagged($page);
        $ids = array_column($flagged['rows'], 'item_id');
        $items = LibraryItem::query()->with('topic.framework')->whereIn('id', $ids)->get()->keyBy('id');
        $latest = LibraryItemVersion::query()->whereIn('id', LibraryItemVersion::query()->selectRaw('MAX(id)')->whereIn('library_item_id', $ids)->groupBy('library_item_id'))
            ->get()->keyBy('library_item_id');
        $grades = GradeLevel::query()->pluck('name', 'id');
        $rows = [];
        foreach ($flagged['rows'] as $signal) {
            $item = $items->get($signal['item_id']);
            if ($item === null) {
                continue;
            }
            $version = $latest->get($item->id);
            $rows[] = ['id' => (int) $item->id, 'title' => $item->title, 'revision' => (int) $item->revision, 'framework' => $item->topic->framework->code,
                'subject_code' => $item->topic->subject_code, 'grade' => (string) $grades->get($item->topic->grade_level_id), 'topic' => $item->topic->name,
                'facility' => intdiv($signal['facility_bp'] + 50, 100), 'learners' => $this->band($signal['learners']), 'signal' => $signal['signal'],
                'signal_label' => self::SIGNALS[$signal['signal']], 'origin' => (string) ($version?->origin ?? 'staff'),
                'open' => $version !== null && in_array($version->state, ['draft', 'in_review'], true) ? LibraryPages::STATES[$version->state] : null];
        }

        return ['rows' => $rows, 'total' => $flagged['total'], 'page' => max(1, $page), 'lastPage' => max(1, (int) ceil($flagged['total'] / self::PER_PAGE)),
            'minimumLearners' => self::MINIMUM_LEARNERS, 'minimumSchools' => self::MINIMUM_SCHOOLS, 'low' => intdiv(self::LOW_BP, 100), 'high' => intdiv(self::HIGH_BP, 100)];
    }

    /** Learner counts in bands, so a single new answer never shows. */
    public function band(int $learners): string
    {
        return match (true) {
            $learners >= 1000 => '1000+',
            $learners >= 100 => '100 to 999',
            $learners >= 50 => '50 to 99',
            default => self::MINIMUM_LEARNERS.' to 49',
        };
    }

    /**
     * Answers on each item's current published version, grouped per item,
     * with groups below the minimum dropped in the database. Aggregates only,
     * across schools by design: counts leave the database, rows never do.
     */
    private function aggregate(): Builder
    {
        return DB::table('practice_reviews as r')
            ->join('practice_set_entries as e', fn ($join) => $join->on('e.id', '=', 'r.practice_set_entry_id')->on('e.school_id', '=', 'r.school_id'))
            ->join('library_items as i', 'i.published_version_id', '=', 'e.library_item_version_id')
            ->whereNotNull('e.library_item_version_id')
            ->groupBy('i.id', 'e.library_item_version_id')
            ->havingRaw('COUNT(DISTINCT r.learner_id) >= ?', [self::MINIMUM_LEARNERS])
            ->havingRaw('COUNT(DISTINCT r.school_id) >= ?', [self::MINIMUM_SCHOOLS])
            ->selectRaw('i.id AS item_id, e.library_item_version_id AS version_id, COUNT(*) AS answers, '.self::CORRECT.' AS correct, COUNT(DISTINCT r.learner_id) AS learners');
    }

    /** The aggregate, keeping only questions outside the expected band. */
    private function flaggedQuery(): Builder
    {
        return $this->aggregate()->havingRaw('('.self::CORRECT.' * 10000 < ? * COUNT(*) OR '.self::CORRECT.' * 10000 > ? * COUNT(*))', [self::LOW_BP, self::HIGH_BP]);
    }

    /** @return array{version_id: int, answers: int, learners: int, facility_bp: int, signal: ?string} */
    private function shape(object $row): array
    {
        $correct = (int) $row->correct * 10000;
        $answers = max(1, (int) $row->answers);

        // The same exact comparison as flaggedQuery's HAVING, so the two never disagree at the edges.
        return ['version_id' => (int) $row->version_id, 'answers' => (int) $row->answers, 'learners' => (int) $row->learners,
            'facility_bp' => intdiv($correct, $answers), 'signal' => $correct < self::LOW_BP * $answers ? 'hard' : ($correct > self::HIGH_BP * $answers ? 'easy' : null)];
    }

    /** HQ only: refused while a school is the current school. */
    private function read(callable $callback): mixed
    {
        abort_if(app(CurrentSchool::class)->isSet(), 403, 'Library quality signals are read only from HQ.');

        return app(CurrentSchool::class)->acrossSchools($callback);
    }
}
