<?php

declare(strict_types=1);

namespace Tests\Unit\Learning;

use App\Services\Learning\PracticeAnswers;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class PracticeAnswersTest extends TestCase
{
    private function numeric(): array
    {
        return ['kind' => 'number', 'prompt' => 'What is one half as a decimal?', 'explanation' => 'Divide one by two to get 0.5.', 'answers' => ['0.5'], 'options' => []];
    }

    public function test_equivalent_decimal_notation_is_exact_without_binary_rounding(): void
    {
        $answers = new PracticeAnswers;
        foreach (['0.5', '.5', '000.500000'] as $answer) {
            $this->assertTrue($answers->correct($this->numeric(), $answer));
        }
        foreach (['0.500001', '5', '-0.5'] as $answer) {
            $this->assertFalse($answers->correct($this->numeric(), $answer));
        }
        $this->assertSame('0', $answers->number('-000.000000'));
        $this->assertSame('999999999999.999999', $answers->number('999999999999.999999'));
    }

    public function test_only_an_offered_choice_can_be_submitted_and_keys_are_not_coerced(): void
    {
        $question = ['kind' => 'choice', 'prompt' => 'Select the prime number.', 'explanation' => 'Seven has only one and itself as factors.',
            'options' => [['id' => 'a', 'text' => 'Seven'], ['id' => 'b', 'text' => 'Eight']], 'answers' => ['a']];
        $answers = new PracticeAnswers;
        $this->assertTrue($answers->correct($question, 'a'));
        $this->assertFalse($answers->correct($question, 'b'));
        $this->expectException(InvalidArgumentException::class);
        $answers->correct($question, 'c');
    }

    public function test_malformed_authored_keys_and_ambiguous_numeric_input_are_refused(): void
    {
        $answers = new PracticeAnswers;
        foreach (['1/2', '1e2', '0,5', 'INF', '', '0.1234567', '1+1', '1000000000000'] as $input) {
            try {
                $answers->number($input);
                $this->fail('Malformed numeric input must not become a graded submission.');
            } catch (InvalidArgumentException) {
                $this->addToAssertionCount(1);
            }
        }
        $question = ['kind' => 'choice', 'prompt' => 'Choose.', 'explanation' => 'A reviewed explanation.',
            'options' => [['id' => 'a', 'text' => 'One'], ['id' => 'a', 'text' => 'Duplicate code']], 'answers' => ['a']];
        $this->expectException(InvalidArgumentException::class);
        $answers->definition($question);
    }
}
