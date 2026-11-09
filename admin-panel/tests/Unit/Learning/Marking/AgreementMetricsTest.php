<?php

declare(strict_types=1);

namespace Tests\Unit\Learning\Marking;

use App\Services\Learning\Marking\Accuracy\AgreementMetrics;
use App\Services\Learning\Marking\Accuracy\IdentifyingData;
use PHPUnit\Framework\TestCase;

/**
 * The accuracy gate's arithmetic (Action Plan 3.18) on synthetic marks:
 * agreement, tolerance, mean absolute difference, bias, needs-a-teacher and
 * the pass rule; and the identifying-data screen for script text.
 */
class AgreementMetricsTest extends TestCase
{
    private const SETTINGS = ['min_scripts' => 4, 'point_marks' => 0, 'script_percent' => 10, 'point_agreement_percent' => 75,
        'script_agreement_percent' => 75, 'max_bias_percent' => 10];

    private function meta(string $type = 'answer', string $rule = 'exact_value'): array
    {
        return ['paper' => 'syn', 'question' => '1', 'index' => 1, 'earns' => 'Synthetic', 'type' => $type, 'rule' => $rule, 'mark_units' => 100];
    }

    private function engine(int $units, string $status = 'awarded', string $reason = 'Synthetic reason'): array
    {
        return ['awarded_units' => $units, 'status' => $status, 'reason' => $reason];
    }

    public function test_agreement_bias_and_the_pass_rule(): void
    {
        $metrics = new AgreementMetrics(self::SETTINGS);
        // Four scripts of 10 marks: three agree, one where the engine gave 1 mark too many.
        foreach ([[100, 100], [0, 0], [100, 100], [100, 0]] as $i => [$engine, $teacher]) {
            $metrics->point('p1', $this->meta(), $this->engine($engine, $engine > 0 ? 'awarded' : 'not_awarded'), $teacher);
            $metrics->script(900 + $engine, 900 + $teacher, 1000);
        }
        $result = $metrics->result();
        $this->assertSame(['count' => 4, 'exact_percent' => 75.0, 'within_percent' => 75.0, 'needs_teacher_percent' => 0.0, 'mean_abs_marks' => 0.25, 'bias_marks' => 0.25], $result['metrics']['points']);
        $this->assertSame([75.0, 100.0, 0.25, 0.25, 2.5], [$result['metrics']['scripts']['exact_percent'], $result['metrics']['scripts']['within_percent'],
            $result['metrics']['scripts']['mean_abs_marks'], $result['metrics']['scripts']['bias_marks'], $result['metrics']['scripts']['bias_percent']]);
        $this->assertTrue($result['passed'], json_encode($result['failures']));
        $this->assertSame([1, 0], [$result['point_stats'][0]['over'], $result['point_stats'][0]['under']]);

        // The same marks against stricter settings: each rule fails on its own.
        foreach ([['min_scripts' => 5], ['point_agreement_percent' => 76], ['script_percent' => 9.9, 'script_agreement_percent' => 76], ['max_bias_percent' => 2.4]] as $stricter) {
            $strict = new AgreementMetrics($stricter + self::SETTINGS);
            foreach ([[100, 100], [0, 0], [100, 100], [100, 0]] as [$engine, $teacher]) {
                $strict->point('p1', $this->meta(), $this->engine($engine), $teacher);
                $strict->script(900 + $engine, 900 + $teacher, 1000);
            }
            $this->assertFalse($strict->result()['passed'], json_encode($stricter));
            $this->assertCount(1, $strict->result()['failures'], json_encode($stricter));
        }
    }

    public function test_a_mark_sent_to_a_teacher_is_a_disagreement_and_tolerance_widens_agreement(): void
    {
        $metrics = new AgreementMetrics(['point_marks' => 0.5] + self::SETTINGS);
        $metrics->point('a', $this->meta('explanation', 'none'), $this->engine(0, 'needs_teacher'), 0);
        $metrics->point('b', $this->meta('method', 'expression'), $this->engine(100), 50);
        $metrics->point('c', $this->meta('method', 'expression'), $this->engine(100), 0);
        $result = $metrics->result();
        $this->assertSame(['count' => 1, 'exact_percent' => 0.0, 'within_percent' => 0.0, 'needs_teacher_percent' => 100.0, 'mean_abs_marks' => 0.0, 'bias_marks' => 0.0],
            $result['metrics']['by_type']['explanation'], 'even when the teacher also gave nothing');
        $this->assertSame([0.0, 50.0], [$result['metrics']['by_type']['method']['exact_percent'], $result['metrics']['by_type']['method']['within_percent']]);
        $this->assertSame(['a', 'c', 'b'], array_column($result['point_stats'], 'key'), 'worst agreement first');
        $this->assertSame(['too_few_scripts', 'point_agreement', 'script_agreement', 'bias'], (new AgreementMetrics(self::SETTINGS))->result()['failures'], 'nothing measured never passes');
    }

    public function test_identifying_data_in_text_and_field_names(): void
    {
        $this->assertSame('email', IdentifyingData::find('Send to learner@example.org'));
        $this->assertSame('id_number', IdentifyingData::find('ID: 900202 1234 080'));
        $this->assertSame('phone', IdentifyingData::find('+27 82 555 0101'));
        $this->assertSame('phone', IdentifyingData::find('Call 0825550101.'));
        foreach (['x = 4', '3 × 10^8 = 300000000', 'v = 0,0123456789 m/s', '9002021234081', '1 234 567', 'P = 1 000 000 000 000 W'] as $text) {
            $this->assertNull(IdentifyingData::find($text), $text);
        }
        foreach (['learner_name', 'Student Number', 'email', 'school', 'id', 'date_of_birth', 'guardian_phone'] as $field) {
            $this->assertTrue(IdentifyingData::identifyingField($field), $field);
        }
        foreach (['notes', 'score', 'comment'] as $field) {
            $this->assertFalse(IdentifyingData::identifyingField($field), $field);
        }
    }
}
