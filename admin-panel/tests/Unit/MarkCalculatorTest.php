<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Academics\MarkCalculator;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class MarkCalculatorTest extends TestCase
{
    private function task(int $id, string $component, int $score, int $maximum = 100, int $weight = 10000, string $disposition = 'recorded'): array
    {
        return ['id' => $id, 'component' => $component, 'weight_bp' => $weight, 'max_mark_units' => $maximum,
            'mark_units' => $score, 'disposition' => $disposition, 'annual_component' => $component, 'annual_weight_bp' => $weight];
    }

    public static function phaseExamples(): array
    {
        // Independently worked: SBA 70%, exam 80%, applying each public S8 generic ratio.
        // They exercise arithmetic; schools must confirm the applicable subject/amendments.
        return ['foundation' => [10000, 0, 7000], 'intermediate' => [8000, 2000, 7200],
            'senior' => [6000, 4000, 7400], 'grades 10-11' => [4000, 6000, 7600], 'grade 12' => [2500, 7500, 7750]];
    }

    #[DataProvider('phaseExamples')]
    public function test_phase_ratio_worked_examples(int $sba, int $exam, int $expected): void
    {
        $tasks = [$this->task(1, 'sba', 70)];
        if ($exam > 0) {
            $tasks[] = $this->task(2, 'exam', 80);
        }
        $result = (new MarkCalculator)->calculate(['sba' => $sba, 'exam' => $exam], $tasks);
        $this->assertSame($expected, $result['percentage_bp']);
        $this->assertSame(10000, $result['coverage_bp']);
        $this->assertTrue($result['complete']);
    }

    public function test_task_percentages_are_not_rounded_before_weighting_and_practical_components_are_supported(): void
    {
        $tasks = [$this->task(1, 'sba', 1, 3, 6667), $this->task(2, 'sba', 1, 4, 3333)];
        // (1/3 × 0.6667 + 1/4 × 0.3333) × 100 = 30.555833…; early task rounding would give 30.55.
        $this->assertSame(3056, (new MarkCalculator)->calculate(['sba' => 10000], $tasks)['percentage_bp']);
        $this->assertSame(8000, (new MarkCalculator)->calculate(['sba' => 2500, 'pat' => 2500, 'exam' => 5000],
            [$this->task(1, 'sba', 70), $this->task(2, 'pat', 90), $this->task(3, 'exam', 80)])['percentage_bp']);
    }

    public function test_final_half_up_half_even_and_floor_are_exact(): void
    {
        $calculator = new MarkCalculator;
        $this->assertSame(313, $calculator->percentage(1, 32, 'half_up'));
        $this->assertSame(312, $calculator->percentage(1, 32, 'half_even'));
        $this->assertSame(312, $calculator->percentage(1, 32, 'floor'));
        $this->assertSame(188, $calculator->percentage(3, 160, 'half_even'));
        $this->assertSame(10000, $calculator->percentage(100000000, 100000000));
    }

    public function test_missing_work_is_provisional_with_weighted_coverage_and_no_official_rating(): void
    {
        $tasks = [$this->task(1, 'sba', 70, 100, 5000), $this->task(2, 'sba', 0, 100, 5000), $this->task(3, 'exam', 0)];
        $tasks[1]['mark_units'] = $tasks[2]['mark_units'] = null;
        $result = (new MarkCalculator)->calculate(['sba' => 6000, 'exam' => 4000], $tasks);
        $this->assertNull($result['percentage_bp']);
        $this->assertNull($result['rating_level']);
        $this->assertSame(7000, $result['running_bp']);
        $this->assertSame(3000, $result['coverage_bp']);
        $this->assertTrue($result['provisional']);
    }

    public function test_excused_tasks_are_excluded_but_all_excused_components_cannot_become_a_fake_pass(): void
    {
        $tasks = [$this->task(1, 'sba', 75, 100, 5000), $this->task(2, 'sba', 0, 100, 5000, 'excused')];
        $tasks[1]['mark_units'] = null;
        $result = (new MarkCalculator)->calculate(['sba' => 10000], $tasks);
        $this->assertSame(7500, $result['percentage_bp']);
        $this->assertSame(10000, $result['coverage_bp']);
        $tasks[0]['disposition'] = 'excused';
        $tasks[0]['mark_units'] = null;
        $result = (new MarkCalculator)->calculate(['sba' => 10000], $tasks);
        $this->assertNull($result['percentage_bp']);
        $this->assertNull($result['running_bp']);
        $this->assertContains('no_eligible_tasks:sba', $result['warnings']);
    }

    public function test_absent_zero_is_explicit_and_catchup_remains_incomplete_until_captured(): void
    {
        $tasks = [$this->task(1, 'sba', 80, 100, 5000), $this->task(2, 'sba', 0, 100, 5000, 'absent')];
        $tasks[1]['mark_units'] = null;
        $this->assertSame(4000, (new MarkCalculator)->calculate(['sba' => 10000], $tasks, 'half_up', 'zero')['percentage_bp']);
        $this->assertNull((new MarkCalculator)->calculate(['sba' => 10000], $tasks)['percentage_bp']);
        $tasks[1]['disposition'] = 'catch_up';
        $this->assertNull((new MarkCalculator)->calculate(['sba' => 10000], $tasks)['percentage_bp']);
        $tasks[1]['mark_units'] = 60;
        $this->assertSame(7000, (new MarkCalculator)->calculate(['sba' => 10000], $tasks)['percentage_bp']);
    }

    public function test_partial_annual_definitions_have_coverage_without_claiming_a_year_result(): void
    {
        $task = $this->task(1, 'sba', 72);
        $task['annual_weight_bp'] = 2500;
        $result = (new MarkCalculator)->calculate(['sba' => 6000, 'exam' => 4000], [$task], 'half_up', 'incomplete', true);
        $this->assertNull($result['percentage_bp']);
        $this->assertSame(7200, $result['running_bp']);
        $this->assertSame(1500, $result['coverage_bp']);
        $this->assertSame(7200, (new MarkCalculator)->calculate(['sba' => 10000], [$task])['percentage_bp']);
    }

    public function test_ratings_use_the_final_percentage_boundaries(): void
    {
        $calculator = new MarkCalculator;
        foreach ([0 => 1, 2999 => 1, 3000 => 2, 3999 => 2, 4000 => 3, 4999 => 3, 5000 => 4, 5999 => 4, 6000 => 5, 6999 => 5, 7000 => 6, 7999 => 6, 8000 => 7, 10000 => 7] as $bp => $rating) {
            $this->assertSame($rating, $calculator->rating($bp));
        }
    }

    public function test_overweighted_and_unrecognised_components_never_produce_official_results(): void
    {
        $calc = new MarkCalculator;
        $this->assertNull($calc->calculate(['sba' => 10000], [$this->task(1, 'sba', 50), $this->task(2, 'sba', 60)])['percentage_bp']);
        $this->assertNull($calc->calculate(['sba' => 10000], [$this->task(1, 'oral', 80)])['percentage_bp']);
        $this->assertNull($calc->calculate([], [])['percentage_bp']);
        $this->expectException(InvalidArgumentException::class);
        $calc->percentage(11, 10);
    }
}
