<?php

declare(strict_types=1);

namespace App\Services\Learning\Library;

use App\Models\Academics\GradeLevel;
use App\Models\Library\LibraryItem;
use App\Models\Library\LibraryItemVersion;
use App\Models\User;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Support\Facades\DB;

/**
 * Quality signals for library items from learners' answers (Action Plan 3.4,
 * SA.16). For each published library practice question, learners' answers in
 * daily practice across every school with the library switched on are
 * counted in the database: how many answers, how many right, how many
 * learners and how many schools. A group smaller than MINIMUM_LEARNERS
 * learners or MINIMUM_SCHOOLS schools never leaves the query (HAVING), so no
 * school and no learner can be picked out. What HQ sees is a count band and a
 * rate: never a school's name or id, a learner, an answer or a date. Nothing
 * is stored: the signals are computed when HQ reads them, and nothing a
 * school wrote is copied into the library.
 */
class LibraryQuality
{
    public const MINIMUM_LEARNERS = 20;

    public const MINIMUM_SCHOOLS = 3;

    /** Facility (share of answers right) outside this band, in basis points, needs a look. */
    public const LOW_BP = 3000;

    public const HIGH_BP = 9500;

    public const SIGNALS = ['hard' => 'Far harder than expected: check the answer key, the wording and the grade', 'easy' => 'Far easier than expected: check it is not giving the answer away'];

    /**
     * Every published library question with enough answers, keyed by item id.
     * Only items whose current published version has the minimum group.
     *
     * @return array<int, array{version_id: int, answers: int, learners: int, facility_bp: int, signal: ?string}>
     */
    public function signals(): array
    {
        abort_if(app(CurrentSchool::class)->isSet(), 403, 'Library quality signals are read only from HQ.');
        // Aggregates only, across schools by design: counts leave the database, rows never do.
        $rows = app(CurrentSchool::class)->acrossSchools(fn () => DB::table('practice_reviews as r')
            ->join('practice_set_entries as e', fn ($join) => $join->on('e.id', '=', 'r.practice_set_entry_id')->on('e.school_id', '=', 'r.school_id'))
            ->join('library_items as i', 'i.published_version_id', '=', 'e.library_item_version_id')
            ->whereNotNull('e.library_item_version_id')
            ->groupBy('i.id', 'e.library_item_version_id')
            ->havingRaw('COUNT(DISTINCT r.learner_id) >= ?', [self::MINIMUM_LEARNERS])
            ->havingRaw('COUNT(DISTINCT r.school_id) >= ?', [self::MINIMUM_SCHOOLS])
            ->selectRaw('i.id AS item_id, e.library_item_version_id AS version_id, COUNT(*) AS answers, SUM(CASE WHEN r.correct THEN 1 ELSE 0 END) AS correct, COUNT(DISTINCT r.learner_id) AS learners')
            ->orderBy('i.id')->limit(5000)->get());
        $signals = [];
        foreach ($rows as $row) {
            $facility = intdiv((int) $row->correct * 10000, max(1, (int) $row->answers));
            $signals[(int) $row->item_id] = ['version_id' => (int) $row->version_id, 'answers' => (int) $row->answers, 'learners' => (int) $row->learners,
                'facility_bp' => $facility, 'signal' => $facility < self::LOW_BP ? 'hard' : ($facility > self::HIGH_BP ? 'easy' : null)];
        }

        return $signals;
    }

    /**
     * Items whose signal needs attention.
     *
     * @return list<int>
     */
    public function flaggedItemIds(): array
    {
        return array_keys(array_filter($this->signals(), fn (array $row) => $row['signal'] !== null));
    }

    /** The /saas/library/attention page: flagged items, worst first, with what can be done. */
    public function attention(User $actor): array
    {
        app(LibraryAccess::class)->actor($actor, LibraryAccess::MANAGE);
        $signals = array_filter($this->signals(), fn (array $row) => $row['signal'] !== null);
        $items = LibraryItem::query()->with('topic.framework')->whereIn('id', array_keys($signals))->get()->keyBy('id');
        $latest = LibraryItemVersion::query()->whereIn('id', LibraryItemVersion::query()->selectRaw('MAX(id)')->whereIn('library_item_id', array_keys($signals))->groupBy('library_item_id'))
            ->get()->keyBy('library_item_id');
        $grades = GradeLevel::query()->pluck('name', 'id');
        $rows = [];
        foreach ($signals as $itemId => $signal) {
            $item = $items->get($itemId);
            if ($item === null) {
                continue;
            }
            $version = $latest->get($itemId);
            $rows[] = ['id' => (int) $item->id, 'title' => $item->title, 'revision' => (int) $item->revision, 'framework' => $item->topic->framework->code,
                'subject_code' => $item->topic->subject_code, 'grade' => (string) $grades->get($item->topic->grade_level_id), 'topic' => $item->topic->name,
                'facility' => intdiv($signal['facility_bp'] + 50, 100), 'learners' => $this->band($signal['learners']), 'signal' => $signal['signal'],
                'signal_label' => self::SIGNALS[$signal['signal']], 'origin' => (string) ($version?->origin ?? 'staff'),
                'open' => $version !== null && in_array($version->state, ['draft', 'in_review'], true) ? LibraryPages::STATES[$version->state] : null];
        }
        // Furthest from the expected band first.
        usort($rows, fn ($a, $b) => [abs(50 - $b['facility']), $a['id']] <=> [abs(50 - $a['facility']), $b['id']]);

        return ['rows' => $rows, 'minimumLearners' => self::MINIMUM_LEARNERS, 'minimumSchools' => self::MINIMUM_SCHOOLS,
            'low' => intdiv(self::LOW_BP, 100), 'high' => intdiv(self::HIGH_BP, 100)];
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
}
