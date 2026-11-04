<?php

declare(strict_types=1);

namespace Tests\Unit\Learning\Marking;

use App\Services\Learning\Marking\Expression;
use PHPUnit\Framework\TestCase;

/** Equivalent algebraic forms by value, without packages (Action Plan 3.16). */
class ExpressionTest extends TestCase
{
    private function same(string $a, string $b, string $style = 'letters'): bool
    {
        $left = Expression::parse($a, $style);
        $right = Expression::parse($b, $style, Expression::variables($left ?? ['n', 0]));
        $this->assertNotNull($left, $a);
        $this->assertNotNull($right, $b);

        return Expression::equivalent($left, $right);
    }

    public function test_equivalent_forms_agree_and_different_ones_do_not(): void
    {
        foreach ([
            ['2(x + 3)', '2x + 6'], ['(x+1)^2', 'x² + 2x + 1'], ['(x+1)(x-1)', 'x^2 - 1'], ['x/2', '0,5x'], ['3x^2y', '3 y x x'],
            ['√16', '4'], ['√(x+1)', '(x+1)^(1/2)'], ['2πr', '2 × pi × r'], ['a/b + c/b', '(a + c)/b'], ['-(x - 3)', '3 - x'], ['x2', '2x'], ['[x+1]·2', '2x + 2'],
        ] as [$a, $b]) {
            $this->assertTrue($this->same($a, $b), "$a = $b");
        }
        $this->assertFalse($this->same('6,63 × 10^-34 x', '6,64 × 10^-34 x'), 'tiny constants are compared relatively');
        $this->assertTrue($this->same('6,63 × 10^-34 x', 'x × 6,63e-34'));
        foreach ([['(x+1)^2', 'x^2 + 1'], ['sqrt(x^2)', 'x'], ['x/y', 'y/x'], ['2x', 'x + 3'], ['xy', 'x + y'], ['abs(x)', 'x']] as [$a, $b]) {
            $this->assertFalse($this->same($a, $b), "$a ≠ $b");
        }
    }

    public function test_symbols_style_reads_quantities_and_splits_by_the_memo_names(): void
    {
        $this->assertSame(['vf', 'vi', 'a', 'Δx'], Expression::variables(Expression::parse('vf^2 - vi^2 - 2 a Δx', 'symbols')));
        $this->assertSame(['x', 'y'], Expression::variables(Expression::parse('xy', 'letters')));
        $this->assertSame(['m', 'g', 'h'], Expression::variables(Expression::parse('mgh', 'symbols', ['m', 'g', 'h'])), 'split by the memo quantities');
        $this->assertSame(['mgh'], Expression::variables(Expression::parse('mgh', 'symbols')), 'without memo names a run is one quantity');
        $this->assertSame(['vf'], Expression::variables(Expression::parse('v_f', 'symbols')));
        $this->assertSame(['Fnet'], Expression::variables(Expression::parse('Fnet', 'symbols', ['Fnet', 'm', 'a'])));
    }

    public function test_equations_are_equivalent_when_rearranged_or_multiplied(): void
    {
        $memo = Expression::equation('v = d/t', 'symbols');
        $names = ['v', 'd', 't'];
        foreach (['d = v t', 'd = vt', 't = d/v', 'v t = d', 'v = d/t', '2v = 2d/t'] as $written) {
            $this->assertTrue(Expression::equationsEquivalent($memo, Expression::equation($written, 'symbols', $names)), $written);
        }
        foreach (['v = d t', 'd = v/t', 'v = v', 'd = d'] as $written) {
            $this->assertFalse(Expression::equationsEquivalent($memo, Expression::equation($written, 'symbols', $names)), $written);
        }
        $this->assertTrue(Expression::equationsEquivalent(Expression::equation('y = 2x + 3'), Expression::equation('2x - y + 3 = 0')));
        $this->assertTrue(Expression::equationsEquivalent(Expression::equation('vf² = vi² + 2aΔx', 'symbols'), Expression::equation('v_f^2 = v_i^2 + 2 a Δx', 'symbols', ['vf', 'vi', 'a', 'Δx'])));
        $this->assertTrue(Expression::equationsEquivalent(Expression::equation('Fnet = m a', 'symbols'), Expression::equation('a = Fnet/m', 'symbols', ['Fnet', 'm', 'a'])));
        $this->assertNull(Expression::equation('a = b = c'));
        $this->assertNull(Expression::equation('x ≥ 2'));
    }

    public function test_constants_and_values(): void
    {
        $this->assertEqualsWithDelta(12.5, Expression::constant(Expression::parse('100/8')), 1e-12);
        $this->assertEqualsWithDelta(3e8, Expression::constant(Expression::parse('3 × 10^8')), 1);
        $this->assertEqualsWithDelta(1000.0, Expression::constant(Expression::parse('1 000', 'symbols')), 1e-12, 'digit groups');
        $this->assertEqualsWithDelta(2.5, Expression::constant(Expression::parse('5 ÷ 2')), 1e-12);
        $this->assertEqualsWithDelta(-9.8, Expression::constant(Expression::parse('-9,8')), 1e-12);
        $this->assertNull(Expression::constant(Expression::parse('1/0')));
        $this->assertNull(Expression::constant(Expression::parse('(-8)^(1/3)')), 'undefined powers are not guessed');
        $this->assertNull(Expression::constant(Expression::parse('2x')));
    }

    public function test_unreadable_or_oversized_expressions_are_refused(): void
    {
        foreach (['2x +', '(((x', 'x))', '', '2 $ 3', str_repeat('(', 40).'x'.str_repeat(')', 40), str_repeat('x+', 100).'x', 'x = 2'] as $text) {
            $this->assertNull(Expression::parse($text), $text);
        }
        $this->assertFalse(Expression::equivalent(Expression::parse('a+b+c+d+e+f+g+h+i'), Expression::parse('i+h+g+f+e+d+c+b+a')), 'more than eight variables is not compared');
    }

    public function test_written_form_ignores_spaces_and_multiplication_signs_only(): void
    {
        $this->assertSame(Expression::written('2 × (x + 3)'), Expression::written('2(x+3)'));
        $this->assertSame(Expression::written('0,5x'), Expression::written('0.5x'));
        $this->assertNotSame(Expression::written('2x + 6'), Expression::written('2(x+3)'));
        // Removing a multiplication sign never joins two numbers (PR #455 review).
        $this->assertNotSame(Expression::written('2*3'), Expression::written('23'));
        $this->assertNotSame(Expression::written('2 × 3'), Expression::written('23'));
        $this->assertNotSame(Expression::written('2(3)'), Expression::written('23'));
        $this->assertSame(Expression::written('2*3'), Expression::written('2 × 3'));
        $this->assertSame(Expression::written('2*x'), Expression::written('2x'));
        $this->assertSame(Expression::written('v = d/t'), Expression::written('v=d / t'));
        $this->assertNotSame(Expression::written('v = 2*3'), Expression::written('v = 23'));
    }
}
