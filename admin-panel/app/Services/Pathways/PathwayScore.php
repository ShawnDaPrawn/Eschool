<?php

declare(strict_types=1);

namespace App\Services\Pathways;

/** Deterministic source-defined projection. Missing evidence never becomes zero. */
class PathwayScore
{
    public function calculate(array $rules, array $marks): array
    {
        $bySubject = [];
        foreach ($marks as $mark) {
            $bySubject[$mark['subject_id']] = $mark['percentage_bp'];
        }
        $evidence = [];
        $points = [];
        $missing = [];
        $failed = [];
        foreach ($rules['subjects'] as $subject) {
            $id = $subject['subject_id'];
            $mark = $bySubject[$id] ?? null;
            $value = null;
            if ($mark !== null) {
                foreach ($rules['bands'] as $band) {
                    if ($mark >= $band['minimum_bp']) {
                        $value = $band['points'];
                        break;
                    }
                }
            }
            $required = $subject['minimum_bp'] !== null;
            $counts = $subject['counts_for_score'];
            if ($mark === null && ($required || $counts)) {
                $missing[] = $id;
            }
            if ($required && $mark !== null && $mark < $subject['minimum_bp']) {
                $failed[] = $id;
            }
            if ($counts && $value !== null) {
                $points[] = ['subject_id' => $id, 'points' => $value];
            }
            $evidence[] = ['subject_id' => $id, 'percentage_bp' => $mark, 'points' => $counts ? $value : null,
                'minimum_bp' => $subject['minimum_bp'], 'counts_for_score' => $counts];
        }
        usort($points, fn ($a, $b) => ($b['points'] <=> $a['points']) ?: ($a['subject_id'] <=> $b['subject_id']));
        $counted = array_slice($points, 0, $rules['best_n']);
        $known = array_sum(array_column($counted, 'points'));
        $complete = $missing === [] && count($counted) === $rules['best_n'];
        $score = $complete ? $known : null;

        return ['projected_score' => $score, 'known_subtotal' => $known, 'minimum_score' => $rules['minimum_score'],
            'status' => $failed !== [] || ($score !== null && $score < $rules['minimum_score']) ? 'below_published_minimum'
                : ($score === null ? 'unknown' : 'meets_published_minimum'),
            'missing_subject_ids' => $missing, 'below_minimum_subject_ids' => $failed, 'counted_subject_ids' => array_column($counted, 'subject_id'), 'subjects' => $evidence];
    }
}
