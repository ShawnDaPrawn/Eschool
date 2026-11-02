<?php

declare(strict_types=1);

namespace Tests\Unit\Growth;

use App\Services\Growth\MasteryScore;
use App\Services\Growth\MasteryScoreV2;
use DateTimeImmutable;
use InvalidArgumentException;
use Tests\TestCase;

/** Action Plan 3.8: the v2 formulas, golden values and the unchanged recent20-v1 heuristic. */
class MasteryScoreV2Test extends TestCase
{
    private function day(string $date): DateTimeImmutable
    {
        return new DateTimeImmutable($date.'T00:00:00Z');
    }

    private function row(int $achieved, int $maximum, string $source = 'question_mark', string $observed = '2026-06-01', ?int $facility = null, int $responses = 0): array
    {
        return ['achieved_units' => $achieved, 'maximum_units' => $maximum, 'source_type' => $source, 'observed_at' => $this->day($observed),
            'facility_bp' => $facility, 'facility_responses' => $responses];
    }

    private function golden(): array
    {
        return [
            $this->row(8, 10, 'question_mark', '2026-05-25', 4000, 12),
            $this->row(1, 1, 'practice_review', '2026-05-20', 9000, 6),
            $this->row(3, 4, 'released_question_mark', '2026-04-01'),
            $this->row(0, 1, 'tutor_checkpoint', '2026-03-01', 5000, 3),
            $this->row(6, 10, 'question_mark', '2025-11-01'),
        ];
    }

    public function test_v2_empty_evidence_gives_no_score_not_zero(): void
    {
        $this->assertSame(['level' => null, 'score_bp' => null, 'confidence_bp' => 0, 'evidence_count' => 0, 'policy_version' => 'weighted-decay-v2'],
            (new MasteryScoreV2)->calculate([], $this->day('2026-06-01')));
        $zero = (new MasteryScoreV2)->calculate([$this->row(0, 10)], $this->day('2026-06-01'));
        $this->assertSame(0, $zero['level']);
        $this->assertSame(0, $zero['score_bp']);
    }

    public function test_v2_golden_values_for_the_fixture_learner(): void
    {
        $score = new MasteryScoreV2;
        $this->assertSame(['level' => 2, 'score_bp' => 6120, 'confidence_bp' => 2203, 'evidence_count' => 5, 'policy_version' => 'weighted-decay-v2'],
            $score->calculate($this->golden(), $this->day('2026-06-01'), [10.0, 30.0]));
        $this->assertSame(['level' => 2, 'score_bp' => 6108, 'confidence_bp' => 2268, 'evidence_count' => 5, 'policy_version' => 'weighted-decay-v2'],
            $score->calculate($this->golden(), $this->day('2026-06-01')));
        $this->assertSame(['level' => 2, 'score_bp' => 5827, 'confidence_bp' => 1362, 'evidence_count' => 5, 'policy_version' => 'weighted-decay-v2'],
            $score->calculate($this->golden(), $this->day('2027-06-01'), [10.0, 30.0]));
        // The same learner under recent20-v1: all marks summed, five rows give full "confidence".
        $this->assertSame(['level' => 2, 'score_bp' => 6923, 'confidence_bp' => 10000, 'evidence_count' => 5, 'policy_version' => 'recent20-v1'],
            (new MasteryScore)->calculate(array_map(fn ($row) => array_intersect_key($row, ['achieved_units' => 1, 'maximum_units' => 1]), $this->golden())));
        $this->assertEqualsWithDelta(44.0, $score->stability([10.0, 30.0]), 1e-12);
        $this->assertEqualsWithDelta(60.0, $score->stability([]), 1e-12);
    }

    public function test_v2_decays_over_time_and_slower_with_more_stable_practice_memory(): void
    {
        $score = new MasteryScoreV2;
        $rows = array_fill(0, 6, $this->row(9, 10, observed: '2026-01-10'));
        $fresh = $score->calculate($rows, $this->day('2026-01-10'));
        $later = $score->calculate($rows, $this->day('2026-07-10'));
        $muchLater = $score->calculate($rows, $this->day('2027-07-10'));
        $this->assertGreaterThan($later['score_bp'], $fresh['score_bp']);
        $this->assertGreaterThan($muchLater['score_bp'], $later['score_bp']);
        $this->assertGreaterThan($later['confidence_bp'], $fresh['confidence_bp']);
        $this->assertGreaterThan($muchLater['confidence_bp'], $later['confidence_bp']);
        // A strong score fades towards the midpoint, never below it, and keeps its evidence count.
        $this->assertGreaterThanOrEqual(5000, $muchLater['score_bp']);
        $this->assertSame(6, $muchLater['evidence_count']);
        $stable = $score->calculate($rows, $this->day('2026-07-10'), [200.0, 300.0, 250.0]);
        $fragile = $score->calculate($rows, $this->day('2026-07-10'), [1.0, 2.0, 1.5]);
        $this->assertGreaterThan($later['score_bp'], $stable['score_bp']);
        $this->assertGreaterThan($fragile['score_bp'], $later['score_bp']);
        // Time never raises a weak score towards the midpoint.
        $weak = array_fill(0, 6, $this->row(2, 10, observed: '2026-01-10'));
        $this->assertSame(2000, $score->calculate($weak, $this->day('2026-01-10'))['score_bp']);
        $this->assertSame(2000, $score->calculate($weak, $this->day('2027-07-10'))['score_bp']);
    }

    public function test_v2_harder_items_weigh_more(): void
    {
        $score = new MasteryScoreV2;
        $asOf = $this->day('2026-06-01');
        $hardRight = $score->calculate([$this->row(1, 1, facility: 2000, responses: 20), $this->row(0, 1, facility: 9000, responses: 20)], $asOf);
        $easyRight = $score->calculate([$this->row(1, 1, facility: 9000, responses: 20), $this->row(0, 1, facility: 2000, responses: 20)], $asOf);
        $neutral = $score->calculate([$this->row(1, 1), $this->row(0, 1)], $asOf);
        $this->assertGreaterThan($neutral['score_bp'], $hardRight['score_bp']);
        $this->assertGreaterThan($easyRight['score_bp'], $neutral['score_bp']);
        // Too few different learners on an item: no difficulty weight at all.
        $this->assertSame($neutral, $score->calculate([$this->row(1, 1, facility: 2000, responses: 4), $this->row(0, 1, facility: 9000, responses: 4)], $asOf));
        // Difficulty changes the weights, not how much evidence there is.
        $this->assertSame($neutral['confidence_bp'], $hardRight['confidence_bp']);
    }

    public function test_v2_sufficiency_rises_slowly_with_evidence_count(): void
    {
        $score = new MasteryScoreV2;
        $asOf = $this->day('2026-06-01');
        $previous = 0;
        $confidence = [];
        foreach ([1, 2, 5, 10, 20, 40] as $count) {
            $result = $score->calculate(array_fill(0, $count, $this->row(1, 1)), $asOf);
            $this->assertGreaterThan($previous, $result['confidence_bp']);
            $previous = $confidence[$count] = $result['confidence_bp'];
        }
        $this->assertSame([1 => 769, 2 => 1429, 5 => 2941, 10 => 4545, 20 => 6250, 40 => 7692], $confidence);
        // recent20-v1 claims full sufficiency after five answers.
        $this->assertSame(10000, (new MasteryScore)->calculate(array_fill(0, 5, ['achieved_units' => 1, 'maximum_units' => 1]))['confidence_bp']);
        // One perfect answer is not mastery: the lead over the midpoint grows with evidence.
        $this->assertSame([2, 6667], [($one = $score->calculate([$this->row(1, 1)], $asOf))['level'], $one['score_bp']]);
        $this->assertSame([4, 8750], [($six = $score->calculate(array_fill(0, 6, $this->row(1, 1)), $asOf))['level'], $six['score_bp']]);
        // Only the newest `window` answers count.
        $this->assertSame(40, $score->calculate(array_fill(0, 50, $this->row(1, 1)), $asOf)['evidence_count']);
    }

    public function test_v2_practice_counts_half_of_marks(): void
    {
        $score = new MasteryScoreV2;
        $asOf = $this->day('2026-06-01');
        $mark = $score->calculate([$this->row(1, 1, 'question_mark')], $asOf);
        $practice = $score->calculate([$this->row(1, 1, 'practice_review')], $asOf);
        $tutor = $score->calculate([$this->row(1, 1, 'tutor_checkpoint')], $asOf);
        $this->assertGreaterThan($practice['score_bp'], $mark['score_bp']);
        $this->assertGreaterThan($practice['confidence_bp'], $mark['confidence_bp']);
        $this->assertSame($practice, $tutor);
        // Two practice answers carry the evidence of one mark.
        $this->assertSame($mark['confidence_bp'], $score->calculate([$this->row(1, 1, 'practice_review'), $this->row(1, 1, 'practice_review')], $asOf)['confidence_bp']);
        // A wrong mark outweighs a right practice answer of the same age and rank weight.
        $this->assertLessThan(5000, $score->calculate([$this->row(0, 1, 'question_mark'), $this->row(1, 1, 'practice_review')], $asOf)['score_bp']);
        $this->assertLessThan(5000, $score->calculate([$this->row(1, 1, 'practice_review'), $this->row(0, 1, 'question_mark')], $asOf)['score_bp']);
        // Changing the weight is configuration, not code.
        $equal = new MasteryScoreV2(['source_weights' => ['practice_review' => 1.0]]);
        $this->assertSame($mark, $equal->calculate([$this->row(1, 1, 'practice_review')], $asOf));
    }

    public function test_v2_is_deterministic_and_rejects_invalid_evidence_and_parameters(): void
    {
        $score = new MasteryScoreV2;
        $first = $score->calculate($this->golden(), $this->day('2026-06-01'), [10.0, 30.0]);
        for ($i = 0; $i < 3; $i++) {
            $this->assertSame($first, (new MasteryScoreV2)->calculate($this->golden(), $this->day('2026-06-01'), [10.0, 30.0]));
        }
        $string = array_map(fn ($row) => ['observed_at' => $row['observed_at']->format(DATE_ATOM)] + $row, $this->golden());
        $this->assertSame($first, $score->calculate($string, $this->day('2026-06-01'), [10.0, 30.0]));
        foreach ([[$this->row(3, 2)], [['achieved_units' => 1, 'maximum_units' => 1, 'source_type' => 'question_mark']]] as $rows) {
            try {
                $score->calculate($rows, $this->day('2026-06-01'));
                $this->fail('Invalid evidence must be refused.');
            } catch (InvalidArgumentException) {
                $this->assertTrue(true);
            }
        }
        foreach ([['window' => 0], ['recency_ratio' => 1.5], ['difficulty_strength' => 1.0], ['source_weights' => ['practice_review' => 0]], ['midpoint_bp' => 10001]] as $bad) {
            try {
                new MasteryScoreV2($bad);
                $this->fail('Invalid parameters must be refused.');
            } catch (InvalidArgumentException) {
                $this->assertTrue(true);
            }
        }
    }

    public function test_recent20_v1_output_is_byte_for_byte_the_recorded_snapshot(): void
    {
        $path = base_path('tests/fixtures/mastery/recent20-v1-snapshot.json');
        $snapshot = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('recent20-v1', $snapshot['policy_version']);
        $this->assertSame('recent20-v1', MasteryScore::VERSION);
        $this->assertCount(55, $snapshot['cases']);
        foreach ($snapshot['cases'] as $index => $case) {
            $this->assertSame(json_encode($case['result']), json_encode((new MasteryScore)->calculate($case['rows'])), 'Snapshot case '.$index);
        }
    }
}
