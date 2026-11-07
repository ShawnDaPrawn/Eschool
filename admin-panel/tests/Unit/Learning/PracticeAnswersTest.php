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

    private function steps(): array
    {
        return ['kind' => 'steps', 'prompt' => 'Work out 3 x (4 + 2).', 'explanation' => 'Brackets first: 6, then 3 x 6 = 18.', 'options' => [], 'answers' => [],
            'steps' => [['kind' => 'number', 'prompt' => 'Work out the brackets.', 'options' => [], 'answers' => ['6']],
                ['kind' => 'choice', 'prompt' => 'What comes next?', 'options' => [['id' => 'a', 'text' => 'Multiply'], ['id' => 'b', 'text' => 'Add']], 'answers' => ['a']],
                ['kind' => 'number', 'prompt' => 'The answer.', 'options' => [], 'answers' => ['18']]]];
    }

    private function diagram(): array
    {
        return ['kind' => 'diagram_label', 'prompt' => 'Label the plant.', 'explanation' => 'Leaves at the top, roots below.', 'options' => [], 'answers' => [],
            'diagram' => ['source' => 'school', 'id' => '0b9d8c52-7d6e-4c43-9f43-2a4f5e6d7c81', 'width' => 800, 'height' => 600],
            'regions' => [['id' => 'p1', 'x' => 50, 'y' => 10], ['id' => 'p2', 'x' => 50, 'y' => 90]],
            'labels' => [['id' => 'leaf', 'text' => 'Leaf'], ['id' => 'root', 'text' => 'Root'], ['id' => 'stem', 'text' => 'Stem']],
            'mapping' => ['p1' => 'leaf', 'p2' => 'root']];
    }

    public function test_steps_mark_each_step_and_count_the_share_of_correct_steps(): void
    {
        $answers = new PracticeAnswers;
        $all = $answers->mark($this->steps(), ['parts' => ['s1' => '6', 's2' => 'a', 's3' => '18.0']]);
        $this->assertSame(['state' => 'marked', 'correct' => true, 'achieved' => 3, 'maximum' => 3], array_intersect_key($all, array_flip(['state', 'correct', 'achieved', 'maximum'])));
        $some = $answers->mark($this->steps(), ['parts' => ['s1' => '6', 's2' => 'b', 's3' => '12']]);
        $this->assertSame(['s1' => true, 's2' => false, 's3' => false], $some['parts']);
        $this->assertSame([false, 1, 3], [$some['correct'], $some['achieved'], $some['maximum']]);
        $this->expectException(InvalidArgumentException::class);
        $answers->mark($this->steps(), ['parts' => ['s1' => '6', 's2' => 'a']]);
    }

    public function test_short_text_matches_ignoring_case_spacing_and_punctuation_and_otherwise_waits_for_a_teacher(): void
    {
        $question = ['kind' => 'short_text', 'prompt' => 'What gives plants their energy?', 'explanation' => 'Plants use sunlight.', 'options' => [], 'answers' => ['The Sun', 'sunlight']];
        $answers = new PracticeAnswers;
        foreach (['the sun', '  THE   sun. ', 'Sunlight!'] as $answer) {
            $this->assertSame(['marked', true, 1], array_values(array_intersect_key($answers->mark($question, ['answer' => $answer]), array_flip(['state', 'correct', 'achieved']))));
        }
        $waiting = $answers->mark($question, ['answer' => 'light from the sky']);
        $this->assertSame(['awaiting_teacher', null, null, 1], [$waiting['state'], $waiting['correct'], $waiting['achieved'], $waiting['maximum']]);
        $this->assertSame('light from the sky', $waiting['stored']);
    }

    public function test_diagram_labels_are_marked_by_exact_mapping(): void
    {
        $answers = new PracticeAnswers;
        $right = $answers->mark($this->diagram(), ['parts' => ['p1' => 'leaf', 'p2' => 'root']]);
        $this->assertSame([true, 1, 1], [$right['correct'], $right['achieved'], $right['maximum']]);
        $half = $answers->mark($this->diagram(), ['parts' => ['p1' => 'leaf', 'p2' => 'stem']]);
        $this->assertSame([false, 0, 1, ['p1' => true, 'p2' => false]], [$half['correct'], $half['achieved'], $half['maximum'], $half['parts']]);
        $this->expectException(InvalidArgumentException::class);
        $answers->mark($this->diagram(), ['parts' => ['p1' => 'leaf', 'p2' => 'branch']]);
    }

    public function test_audio_always_waits_for_a_teacher_and_each_kind_is_answered_its_own_way(): void
    {
        $answers = new PracticeAnswers;
        $audio = ['kind' => 'audio', 'prompt' => 'Read the sentence aloud.', 'explanation' => 'Listen for each sound.', 'options' => [], 'answers' => [], 'max_seconds' => 60];
        $this->assertSame('awaiting_teacher', $answers->mark($audio, ['audio' => true])['state']);
        foreach ([[$audio, ['answer' => 'typed']], [$this->steps(), ['answer' => '18']], [$this->numeric(), ['parts' => ['s1' => '1']]], [$this->numeric(), ['audio' => true, 'answer' => '0.5']]] as [$question, $response]) {
            try {
                $answers->mark($question, $response);
                $this->fail('A response of the wrong shape must not be marked.');
            } catch (InvalidArgumentException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function test_the_device_question_never_carries_answers_mapping_or_explanation_and_definitions_are_stable(): void
    {
        $answers = new PracticeAnswers;
        foreach ([$this->numeric(), $this->steps(), $this->diagram()] as $definition) {
            $question = json_encode($answers->question($definition));
            $this->assertStringNotContainsString('explanation', $question);
            $this->assertStringNotContainsString('answers', $question);
            $this->assertStringNotContainsString('mapping', $question);
            $this->assertStringNotContainsString('"18"', $question);
            $this->assertStringNotContainsString('Brackets first', $question);
            $normal = $answers->definition($definition);
            $this->assertSame($normal, $answers->definition($normal), 'normalising twice changes nothing');
            $this->assertSame($normal, $answers->fromForm($answers->toForm($normal), $normal['diagram'] ?? null), 'the panel form round-trips');
        }
        // The tutor checkpoint hash depends on the one-answer key order staying as it was.
        $this->assertSame(['kind', 'prompt', 'explanation', 'answers', 'options'], array_keys($answers->definition($this->numeric())));
        $this->expectException(InvalidArgumentException::class);
        $answers->definition($this->steps(), PracticeAnswers::SINGLE);
    }
}
