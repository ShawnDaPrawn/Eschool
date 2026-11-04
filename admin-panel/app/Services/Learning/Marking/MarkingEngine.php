<?php

declare(strict_types=1);

namespace App\Services\Learning\Marking;

use App\Services\Learning\Marking\Rules\ExactValueRule;
use App\Services\Learning\Marking\Rules\ExpressionRule;
use App\Services\Learning\Marking\Rules\FollowThrough;
use App\Services\Learning\Marking\Rules\KeywordsRule;
use App\Services\Learning\Marking\Rules\SignificantFiguresRule;
use App\Services\Learning\Marking\Rules\UnitsRequiredRule;

/**
 * Marks one answer against one question's mark points (Action Plan 3.16).
 * Rules decide in code; method and explanation marks that no rule decides go
 * to the judgement seam, which is off, and are never awarded without a teacher.
 * Pure: no database, no network. engineering/specs/learning/marking-engine.md.
 *
 * The result has, per mark point, whether it was awarded, the rule that
 * decided it, a plain reason and a confidence, plus the question's totals.
 */
class MarkingEngine
{
    public const VERSION = 'rules-v1';

    public function __construct(private readonly MarkingJudgement $judgement) {}

    /**
     * @param  array{number: string, max_units: int, points: list<array{earns: string, type: string, mark_units: int, rule: ?array}>}  $question
     * @return array<string, mixed>
     */
    public function mark(array $question, LearnerAnswer $answer, SubjectProfile $profile): array
    {
        $points = array_values($question['points']);
        $outcomes = [];
        foreach ($points as $i => $point) {
            $outcomes[$i] = $this->point($point, $answer, $profile, $points, $outcomes);
        }
        if ($profile->formulaFirst()) {
            $outcomes = $this->formulaFirst($points, $outcomes, $answer);
        }

        return $this->result($question, $points, $outcomes, $profile);
    }

    /** @param array<int, Outcome> $earlier */
    private function point(array $point, LearnerAnswer $answer, SubjectProfile $profile, array $points, array $earlier): Outcome
    {
        if ($answer->isEmpty()) {
            return Outcome::notAwarded(__('No answer was given.'), 0.99);
        }
        $rule = $point['rule'] ?? null;
        $kind = is_array($rule) ? (string) ($rule['kind'] ?? 'none') : 'none';
        if ($point['type'] === 'explanation') {
            return $this->judge($point, $answer, $profile, $kind === 'keywords' ? [app(KeywordsRule::class)->evidence($rule, $answer)] : []);
        }
        if ($kind === 'none') {
            return $point['type'] === 'method'
                ? $this->judge($point, $answer, $profile, [])
                : Outcome::needsTeacher(__('The memo has no rule for this mark, so a teacher decides it.'), 0.2);
        }
        $outcome = match ($kind) {
            'exact_value' => app(ExactValueRule::class)->evaluate($point, $answer, $profile),
            'units_required' => app(UnitsRequiredRule::class)->evaluate($point, $answer),
            'expression' => app(ExpressionRule::class)->evaluate($point, $answer, $profile),
            'keywords' => app(KeywordsRule::class)->evaluate($point, $answer),
            'significant_figures' => app(SignificantFiguresRule::class)->evaluate($point, $answer),
            default => Outcome::needsTeacher(__('Marking does not know this memo rule, so a teacher decides it.'), 0.2),
        };
        if ($point['type'] === 'consistent_accuracy' && $outcome->status === Outcome::NOT_AWARDED) {
            $outcome = app(FollowThrough::class)->evaluate($point, $outcome, $answer, $profile, $points, $earlier);
        }

        return $outcome;
    }

    /** Judgement never decides a mark on its own: whatever it says, a teacher confirms. */
    private function judge(array $point, LearnerAnswer $answer, SubjectProfile $profile, array $evidence): Outcome
    {
        $outcome = $this->judgement->judge($point, $answer, $profile, $evidence);

        return $outcome->status === Outcome::NEEDS_TEACHER ? $outcome : Outcome::needsTeacher($outcome->reason, $outcome->confidence, $outcome->line);
    }

    /**
     * Formula first: a formula mark needs a calculation; after a lost formula mark, if the
     * learner wrote a different formula, the calculation marks go to a teacher.
     *
     * @param  array<int, Outcome>  $outcomes
     * @return array<int, Outcome>
     */
    private function formulaFirst(array $points, array $outcomes, LearnerAnswer $answer): array
    {
        $formulas = array_keys(array_filter($points, fn ($p) => $p['type'] === 'method' && ($p['rule']['kind'] ?? null) === 'expression' && str_contains((string) $p['rule']['value'], '=')));
        $otherFormula = $this->writesFormula($answer);
        foreach ($formulas as $i) {
            if ($outcomes[$i]->status === Outcome::AWARDED && ! $answer->hasNumbers()) {
                $outcomes[$i] = Outcome::notAwarded(__('A formula mark needs a calculation with it (formula first).'), 0.8, $outcomes[$i]->line);
            }
            if ($outcomes[$i]->status !== Outcome::NOT_AWARDED || ! $otherFormula) {
                continue;
            }
            foreach ($outcomes as $j => $outcome) {
                if ($j > $i && $outcome->status === Outcome::AWARDED && in_array($points[$j]['type'], ['answer', 'accuracy', 'consistent_accuracy'], true)) {
                    $outcomes[$j] = Outcome::needsTeacher(__('A different formula was written, so a teacher checks the calculation marks (formula first).'), 0.3, $outcome->line);
                }
            }
        }

        return $outcomes;
    }

    /** Whether some line is a formula: an equation of symbols without numbers. */
    private function writesFormula(LearnerAnswer $answer): bool
    {
        foreach (array_keys($answer->lines) as $line) {
            $sides = $answer->sides($line);
            if (count($sides) >= 2 && preg_match('/\p{L}/u', $sides[0]) && preg_match('/\p{L}/u', $sides[1]) && ! preg_match('/\d/', $sides[0].$sides[1])) {
                return true;
            }
        }

        return false;
    }

    /** @param array<int, Outcome> $outcomes */
    private function result(array $question, array $points, array $outcomes, SubjectProfile $profile): array
    {
        $rows = [];
        $awarded = 0;
        $pending = 0;
        $confidence = null;
        foreach ($points as $i => $point) {
            $outcome = $outcomes[$i];
            $units = (int) $point['mark_units'];
            $awarded += $outcome->status === Outcome::AWARDED ? $units : 0;
            $pending += $outcome->status === Outcome::NEEDS_TEACHER ? $units : 0;
            if ($outcome->status !== Outcome::NEEDS_TEACHER) {
                $confidence = $confidence === null ? $outcome->confidence : min($confidence, $outcome->confidence);
            }
            $rows[] = ['index' => $i + 1, 'earns' => (string) $point['earns'], 'type' => (string) $point['type'], 'mark_units' => $units,
                'awarded_units' => $outcome->status === Outcome::AWARDED ? $units : 0, 'status' => $outcome->status,
                'rule' => is_array($point['rule'] ?? null) ? (string) ($point['rule']['kind'] ?? 'none') : 'none', 'decided_by' => $outcome->decidedBy,
                'reason' => $outcome->reason, 'confidence' => round($outcome->confidence, 2), 'line' => $outcome->line === null ? null : $outcome->line + 1];
        }

        return ['engine' => self::VERSION, 'profile' => $profile->key(), 'question' => (string) $question['number'], 'max_units' => (int) $question['max_units'],
            'awarded_units' => $awarded, 'pending_units' => $pending, 'status' => $pending > 0 ? 'needs_teacher' : 'complete',
            'confidence' => $confidence === null ? null : round($confidence, 2), 'points' => $rows];
    }

    /** The result without the memo's own words or rules: what a learner may see. */
    public static function forLearner(array $result): array
    {
        $result['points'] = array_map(fn ($row) => array_diff_key($row, ['earns' => true, 'rule' => true]), $result['points']);

        return $result;
    }
}
