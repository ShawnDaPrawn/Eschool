<?php

declare(strict_types=1);

namespace Tests\Unit\Learning\Marking;

use App\Services\Learning\Marking\AnswerText;
use App\Services\Learning\Marking\LearnerAnswer;
use App\Services\Learning\Marking\Numbers;
use App\Services\Learning\Marking\Units;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/** Numbers, significant figures, tolerance and SI units for the marking engine (Action Plan 3.16). */
class NumbersAndUnitsTest extends TestCase
{
    private function one(string $text): array
    {
        $numbers = Numbers::all(AnswerText::normalize($text));
        $this->assertCount(1, $numbers, $text);

        return $numbers[0];
    }

    public function test_numbers_are_read_with_a_decimal_comma_or_point_digit_groups_and_scientific_notation(): void
    {
        foreach ([
            '3,5' => 3.5, '3.5' => 3.5, '-12,50' => -12.5, '1 000 000' => 1000000.0, '0,00450' => 0.0045,
            '3 × 10^8' => 3e8, '3x10^8' => 3e8, '3,0 × 10⁸' => 3e8, '6,63 × 10⁻³⁴' => 6.63e-34, '2e3' => 2000.0, '1.5E-2' => 0.015, '10^-3' => 0.001, '10⁻³' => 0.001,
        ] as $text => $value) {
            $this->assertEqualsWithDelta($value, $this->one($text)['value'], abs($value) * 1e-12, $text);
        }
        $this->assertSame([], Numbers::all('no numbers here'));
        $this->assertSame([2.0, -3.0], array_column(Numbers::all('x = 2 or x = -3'), 'value'));
        $this->assertSame([5.0, 3.0], array_column(Numbers::all('5-3'), 'value'), 'a minus between numbers is subtraction, not a sign');
        $this->assertSame([], Numbers::all('v2 x1'), 'digits that belong to a symbol are not numbers');
        $this->assertSame(-4.0, Numbers::leading('-4 m to the left')['value']);
        $this->assertNull(Numbers::leading('m = 4'));
    }

    public function test_significant_figures_and_decimal_places(): void
    {
        foreach ([
            '12,50' => [4, 4, 2], '0,00450' => [3, 3, 5], '1200' => [2, 4, 0], '1200,0' => [5, 5, 1], '3,00 × 10^8' => [3, 3, -6],
            '7' => [1, 1, 0], '0,0' => [1, 1, 1], '9,81' => [3, 3, 2], '100' => [1, 3, 0],
        ] as $text => [$min, $max, $decimals]) {
            $number = $this->one((string) $text);
            $this->assertSame([$min, $max, $decimals], [$number['sig_min'], $number['sig_max'], $number['decimals']], (string) $text);
        }
    }

    public function test_tolerance_and_rounding(): void
    {
        $this->assertTrue(Numbers::within(12.5, 12.5, 0.0));
        $this->assertTrue(Numbers::within(0.1 + 0.2, 0.3, 0.0), 'binary rounding is not a wrong answer');
        $this->assertTrue(Numbers::within(12.54, 12.5, 0.05));
        $this->assertFalse(Numbers::within(12.56, 12.5, 0.05));
        $this->assertFalse(Numbers::within(-12.5, 12.5, 0.05));
        $this->assertTrue(Numbers::roundsTo(3.27, 3.2653, 2));
        $this->assertTrue(Numbers::roundsTo(2.5, 2.45, 1), 'half rounds up');
        $this->assertFalse(Numbers::roundsTo(3.26, 3.2653, 2));
        $this->assertFalse(Numbers::roundsTo(3.0, 3.2653, -1));
    }

    public function test_units_agree_in_the_ways_learners_write_them(): void
    {
        foreach (['m/s', 'm·s⁻¹', 'm.s-1', 'm s^-1', 'ms-1', 'm·s-1', 'metres per second', 'm/s.'] as $written) {
            $this->assertSame('same', Units::compare('m/s', rtrim($written, '.'))['status'], $written);
        }
        foreach ([['N', 'kg m/s^2'], ['N', 'kg.m.s-2'], ['J/(kg·K)', 'J.kg-1.K-1'], ['kg/m^3', 'kgm-3'], ['mol/dm^3', 'mol·dm⁻³'], ['Ω', 'ohm'], ['°C', '℃'], ['L', 'dm3'], ['m·s⁻²', 'm/s^2']] as [$memo, $learner]) {
            $this->assertSame('same', Units::compare($memo, $learner)['status'], "$memo vs $learner");
        }
    }

    public function test_si_conversions_give_the_factor_into_the_memo_unit(): void
    {
        foreach ([['m', 'km', 1000.0], ['m', 'cm', 0.01], ['m', 'mm', 0.001], ['kg', 'g', 0.001], ['N', 'kN', 1000.0], ['J', 'MJ', 1e6], ['m/s', 'km/h', 1 / 3.6],
            ['km/h', 'm/s', 3.6], ['s', 'min', 60.0], ['s', 'h', 3600.0], ['Pa', 'kPa', 1000.0], ['A', 'mA', 0.001], ['J', 'eV', 1.602176634e-19], ['Ω', 'kΩ', 1000.0], ['m', 'nm', 1e-9], ['F', 'µF', 1e-6]] as [$memo, $learner, $ratio]) {
            $check = Units::compare($memo, $learner);
            $this->assertSame('converted', $check['status'], "$memo vs $learner");
            $this->assertEqualsWithDelta($ratio, $check['ratio'], $ratio * 1e-9, "$memo vs $learner");
        }
    }

    public function test_wrong_missing_unreadable_and_unknown_units(): void
    {
        $this->assertSame('wrong', Units::compare('m/s', 'm')['status']);
        $this->assertSame('wrong', Units::compare('N', 'J')['status']);
        $this->assertSame('wrong', Units::compare('m/s', 'm/s^2')['status']);
        $this->assertSame('wrong', Units::compare('°C', 'K')['status'], 'Celsius is not converted to kelvin');
        $this->assertSame('missing', Units::compare('m/s', null)['status']);
        $this->assertSame('missing', Units::compare('m/s', '  ')['status']);
        $this->assertSame('unreadable', Units::compare('m/s', 'furlongs')['status']);
        $this->assertSame('same', Units::compare('blocks', 'blocks')['status'], 'an unknown memo unit is compared as written');
        $this->assertSame('unknown', Units::compare('blocks', 'bricks')['status']);
        $this->assertFalse(Units::readable(str_repeat('m', 50)));
        $this->assertFalse(Units::readable('m^0'));
    }

    public function test_answers_are_steps_and_results_are_what_each_line_states(): void
    {
        $answer = LearnerAnswer::fromText("Given: d = 100 m\n\nv = d/t\nv = 100/8\nv = 12,5 m·s⁻¹ east\n");
        $this->assertCount(4, $answer->lines, 'blank lines are not steps');
        $this->assertSame([3, 2, 1, 0], $answer->order());
        $this->assertTrue($answer->isFinal(3));
        $this->assertSame([], $answer->values(2), 'a calculation is not a stated value');
        $value = $answer->values(3)[0];
        $this->assertSame([12.5, 'm·s^-1', 3], [$value['value'], $value['unit'], $value['line']]);
        $this->assertSame([2.0, -3.0], array_column(LearnerAnswer::fromText('x = 2 or x = -3')->values(0), 'value'));
        $this->assertSame([12.5], array_column(LearnerAnswer::fromText('The speed is 12,5 m/s')->values(0), 'value'));
        $this->assertSame('m/s', LearnerAnswer::fromText('Answer: 12,5 m/s to the left')->values(0)[0]['unit']);
        $this->assertNull(LearnerAnswer::fromText('x = 4')->values(0)[0]['unit']);
        $this->assertSame(['x', '2'], LearnerAnswer::fromText('x = 2')->sides(0));
        $this->assertSame(['x ≥ 2'], LearnerAnswer::fromText('x ≥ 2')->sides(0), 'inequalities are not split');
        $this->assertTrue(LearnerAnswer::fromText("  \n ")->isEmpty());
    }

    public function test_answers_are_bounded(): void
    {
        foreach ([str_repeat("1\n", LearnerAnswer::MAX_LINES + 1), str_repeat('x', LearnerAnswer::MAX_LINE + 1), str_repeat('x ', 2001)] as $text) {
            try {
                LearnerAnswer::fromText($text);
                $this->fail('Expected a bounded answer.');
            } catch (InvalidArgumentException) {
                $this->addToAssertionCount(1);
            }
        }
        $this->expectException(InvalidArgumentException::class);
        LearnerAnswer::fromSteps(["a\0b"]);
    }
}
