<?php

declare(strict_types=1);

namespace App\Services\Learning;

use InvalidArgumentException;

/** Grades approved practice definitions only; this never writes official marks. */
final class PracticeAnswers
{
    public function definition(array $input): array
    {
        $kind = $input['kind'] ?? null;
        $prompt = $input['prompt'] ?? null;
        $explanation = $input['explanation'] ?? null;
        if (! in_array($kind, ['choice', 'number'], true) || ! is_string($prompt) || trim($prompt) === '' || mb_strlen($prompt) > 4000
            || ! is_string($explanation) || trim($explanation) === '' || mb_strlen($explanation) > 4000) {
            throw new InvalidArgumentException('Provide a bounded question, kind and worked explanation.');
        }
        $options = $input['options'] ?? [];
        $answers = $input['answers'] ?? null;
        if (! is_array($options) || ! array_is_list($options) || ! is_array($answers) || ! array_is_list($answers) || count($answers) < 1 || count($answers) > 10) {
            throw new InvalidArgumentException('Provide approved answer choices.');
        }
        $choices = [];
        if ($kind === 'choice') {
            if (count($options) < 2 || count($options) > 6) {
                throw new InvalidArgumentException('A choice question needs two to six options.');
            }
            foreach ($options as $option) {
                if (! is_array($option)) {
                    throw new InvalidArgumentException('Provide structured answer options.');
                }
                $id = $option['id'] ?? null;
                $text = $option['text'] ?? null;
                if (! is_string($id) || ! preg_match('/^[a-z][a-z0-9_]{0,19}$/D', $id) || array_key_exists($id, $choices)
                    || ! is_string($text) || trim($text) === '' || mb_strlen($text) > 1000) {
                    throw new InvalidArgumentException('Use unique option codes and bounded option text.');
                }
                $choices[$id] = trim($text);
            }
        } elseif ($options !== []) {
            throw new InvalidArgumentException('Numeric questions do not have choice options.');
        }
        $canonical = [];
        foreach ($answers as $answer) {
            if (! is_string($answer) || mb_strlen($answer) > 100) {
                throw new InvalidArgumentException('Approved answers must be bounded strings.');
            }
            if ($kind === 'choice') {
                if (! array_key_exists($answer, $choices)) {
                    throw new InvalidArgumentException('The approved answer must be an offered option.');
                }
                $canonical[] = $answer;
            } else {
                $canonical[] = $this->number($answer);
            }
        }

        return ['kind' => $kind, 'prompt' => trim($prompt), 'explanation' => trim($explanation), 'answers' => array_values(array_unique($canonical)),
            'options' => array_map(fn ($id, $text) => ['id' => $id, 'text' => $text], array_keys($choices), array_values($choices))];
    }

    public function correct(array $approvedDefinition, string $answer): bool
    {
        $definition = $this->definition($approvedDefinition);
        $answer = trim($answer);
        if (mb_strlen($answer) > 100) {
            throw new InvalidArgumentException('The practice answer is too long.');
        }
        if ($definition['kind'] === 'number') {
            $answer = $this->number($answer);
        } elseif (! in_array($answer, array_column($definition['options'], 'id'), true)) {
            throw new InvalidArgumentException('Choose an offered option before submitting.');
        }

        return in_array($answer, $definition['answers'], true);
    }

    /** Exact decimal strings: no floating-point equality, coercion or expressions. */
    public function number(string $value): string
    {
        $value = trim($value);
        if (! preg_match('/^-?(?:\d{1,12}(?:\.\d{1,6})?|\.\d{1,6})$/D', $value)) {
            throw new InvalidArgumentException('Enter a decimal number using a dot, without digit grouping or expressions.');
        }
        $negative = str_starts_with($value, '-');
        $parts = explode('.', ltrim($value, '-'), 2);
        $integer = ltrim($parts[0], '0');
        $integer = $integer === '' ? '0' : $integer;
        $fraction = rtrim($parts[1] ?? '', '0');
        $number = $integer.($fraction === '' ? '' : '.'.$fraction);

        return ($negative && $number !== '0' ? '-' : '').$number;
    }
}
