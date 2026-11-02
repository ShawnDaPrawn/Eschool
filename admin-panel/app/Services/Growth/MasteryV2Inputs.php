<?php

declare(strict_types=1);

namespace App\Services\Growth;

use App\Models\Growth\MasteryEvidence;
use App\Models\Learning\PracticeItem;
use App\Models\Learning\PracticeMemory;

/**
 * Reads what MasteryScoreV2 needs, inside the current school only. Item
 * difficulty uses only evidence the audience may already see: family scores
 * never depend on unreleased marks. One instance serves one locked aggregate
 * pass, so its difficulty cache never outlives the evidence it read.
 */
class MasteryV2Inputs
{
    private array $facility = [];

    public static function audiences(string $audience): array
    {
        return $audience === 'practice' ? ['practice'] : [$audience, 'practice'];
    }

    public function rows(int $learnerId, int $subjectId, int $topicId, string $audience, int $window): array
    {
        $rows = MasteryEvidence::query()->where('learner_id', $learnerId)->where('subject_id', $subjectId)->where('topic_id', $topicId)
            ->whereIn('audience', self::audiences($audience))->orderByDesc('observed_at')->orderByDesc('id')->limit($window)
            ->get(['achieved_units', 'maximum_units', 'source_type', 'item_key', 'observed_at']);
        $facility = $this->facility($subjectId, $audience, $rows->pluck('item_key')->filter()->unique()->values()->all());

        return $rows->map(fn ($row) => ['achieved_units' => $row->achieved_units, 'maximum_units' => $row->maximum_units, 'source_type' => $row->source_type,
            'observed_at' => $row->observed_at->toImmutable(), 'facility_bp' => $facility[$row->item_key]['bp'] ?? null,
            'facility_responses' => $facility[$row->item_key]['responses'] ?? 0])->all();
    }

    /** @return array<int, list<float>> the learner's FSRS stability days per topic */
    public function stabilities(int $learnerId, array $topicIds): array
    {
        $items = PracticeItem::query()->whereIn('topic_id', $topicIds)->pluck('topic_id', 'id');
        $stabilities = [];
        if ($items->isEmpty()) {
            return $stabilities;
        }
        foreach (PracticeMemory::query()->where('learner_id', $learnerId)->whereIn('practice_item_id', $items->keys())->orderBy('id')->get(['practice_item_id', 'state']) as $memory) {
            $days = $memory->state['stability_days'] ?? null;
            if (is_int($days) || is_float($days)) {
                $stabilities[(int) $items->get($memory->practice_item_id)][] = (float) $days;
            }
        }

        return $stabilities;
    }

    /** Integer success rate per item (basis points) and how many different learners answered it. */
    private function facility(int $subjectId, string $audience, array $keys): array
    {
        $missing = array_values(array_filter($keys, fn ($key) => ! array_key_exists($audience.'|'.$key, $this->facility)));
        if ($missing !== []) {
            foreach ($missing as $key) {
                $this->facility[$audience.'|'.$key] = null;
            }
            $stats = MasteryEvidence::query()->where('subject_id', $subjectId)->whereIn('audience', self::audiences($audience))->whereIn('item_key', $missing)
                ->groupBy('item_key')->selectRaw('item_key, SUM(achieved_units) AS achieved, SUM(maximum_units) AS maximum, COUNT(DISTINCT learner_id) AS responses')->get();
            foreach ($stats as $stat) {
                $maximum = (int) $stat->maximum;
                $this->facility[$audience.'|'.$stat->item_key] = $maximum < 1 ? null
                    : ['bp' => intdiv((int) $stat->achieved * 10000 + intdiv($maximum, 2), $maximum), 'responses' => (int) $stat->responses];
            }
        }
        $result = [];
        foreach ($keys as $key) {
            if ($this->facility[$audience.'|'.$key] !== null) {
                $result[$key] = $this->facility[$audience.'|'.$key];
            }
        }

        return $result;
    }
}
