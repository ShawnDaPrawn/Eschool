<?php

declare(strict_types=1);

namespace App\Services\Learning\Marking;

use InvalidArgumentException;

/**
 * A learner's typed answer as steps, one per line (Action Plan 3.16). The last
 * line is the final answer. A line's result is what it states after its last
 * equals sign; a line with several answers ("x = 2 or x = -3") has one result
 * per clause.
 */
final class LearnerAnswer
{
    public const MAX_CHARACTERS = 4000;

    public const MAX_LINES = 40;

    public const MAX_LINE = 300;

    private const LEAD = '/^\s*(?:answer|ans|therefore|thus|so|antwoord|dus)\s*[:.,]?\s*/iu';

    /** @param list<string> $lines */
    private function __construct(public readonly array $lines) {}

    public static function fromText(string $text): self
    {
        return self::fromSteps(preg_split('/\R/u', $text) ?: []);
    }

    /** @param list<string> $steps */
    public static function fromSteps(array $steps): self
    {
        $lines = [];
        $characters = 0;
        foreach ($steps as $step) {
            if (! is_string($step) || ! mb_check_encoding($step, 'UTF-8') || str_contains($step, "\0")) {
                throw new InvalidArgumentException('Answer steps must be plain text.');
            }
            $characters += mb_strlen($step);
            $line = AnswerText::normalize($step);
            if ($line === '') {
                continue;
            }
            if (mb_strlen($line) > self::MAX_LINE) {
                throw new InvalidArgumentException('An answer line is too long.');
            }
            $lines[] = $line;
        }
        if (count($lines) > self::MAX_LINES || $characters > self::MAX_CHARACTERS) {
            throw new InvalidArgumentException('The answer is too long.');
        }

        return new self($lines);
    }

    public function isEmpty(): bool
    {
        return $this->lines === [];
    }

    public function text(): string
    {
        return implode("\n", $this->lines);
    }

    /** Line indexes, the final answer first and then upwards. @return list<int> */
    public function order(): array
    {
        return array_reverse(array_keys($this->lines));
    }

    public function isFinal(int $line): bool
    {
        return $line === count($this->lines) - 1;
    }

    /** The parts of a line between equals signs (not ≤, ≥ or ≠). @return list<string> */
    public function sides(int $line): array
    {
        return array_map('trim', preg_split('/(?<![<>!])=/', $this->lines[$line]) ?: []);
    }

    /** What a line states as its answers: the text after the last equals sign of each clause. @return list<string> */
    public function results(int $line): array
    {
        $results = [];
        foreach (preg_split('/\s+(?:or|of|and|en)\s+|;/iu', $this->lines[$line]) ?: [] as $clause) {
            $sides = preg_split('/(?<![<>!])=/', $clause) ?: [];
            $result = trim((string) preg_replace(self::LEAD, '', (string) end($sides)));
            if ($result !== '') {
                $results[] = $result;
            }
        }

        return $results;
    }

    /**
     * The values a line states as answers: a number followed only by a unit or words
     * (12,5 m/s; 3 × 10^8 m·s⁻¹ east), never a number inside a calculation (100/8).
     *
     * @return list<array{value: float, raw: string, decimals: int, sig_min: int, sig_max: int, unit: ?string, rest: string, line: int}>
     */
    public function values(int $line): array
    {
        $values = [];
        foreach ($this->results($line) as $result) {
            foreach (Numbers::all($result) as $number) {
                $before = rtrim(substr($result, 0, $number['start']));
                $rest = trim(substr($result, $number['end']));
                if (preg_match('/[-+*\/^×·(]$/u', $before) || self::calculation($rest)) {
                    continue;
                }
                $values[] = $number + ['unit' => self::unit($rest), 'rest' => $rest, 'line' => $line];

                break;
            }
        }

        return $values;
    }

    /** Whether text after a number continues a calculation rather than naming a unit. */
    private static function calculation(string $rest): bool
    {
        // Powers that belong to units (s^-1, m2, s-1) are not calculations.
        $rest = (string) preg_replace('/(?<=[\p{L})°])\^?\(?[-+]?\d\)?/u', '', $rest);

        return (bool) preg_match('/\d|^[-+*\/^×·(]/u', $rest);
    }

    /** The unit at the start of the text after a value: the longest run of words that reads as a unit. */
    public static function unit(string $rest): ?string
    {
        $words = preg_split('/\s+/', trim($rest)) ?: [];
        $unit = null;
        $candidate = '';
        foreach (array_slice($words, 0, 4) as $word) {
            $candidate = trim($candidate.' '.$word);
            if (Units::readable(rtrim($candidate, '.,;:'))) {
                $unit = rtrim($candidate, '.,;:');
            }
        }
        if ($unit === null && $words !== [] && $words[0] !== '' && Directions::in($words[0]) === [] && preg_match('/^[\p{L}°%℃µΩ][\p{L}\d°%℃µΩ·.\/^\-]*$/u', $words[0])
            && ! in_array(mb_strtolower($words[0]), ['to', 'in', 'na', 'naar', 'towards', 'the', 'die'], true)) {
            // Something unit-like that is not a unit we know: kept so marking can say so.
            return rtrim($words[0], '.,;:');
        }

        return $unit;
    }

    /** Every number written anywhere in the working. @return list<array> */
    public function numbers(): array
    {
        $numbers = [];
        foreach ($this->lines as $i => $line) {
            foreach (Numbers::all($line) as $number) {
                $numbers[] = $number + ['line' => $i];
            }
        }

        return $numbers;
    }

    public function hasNumbers(): bool
    {
        return $this->numbers() !== [];
    }
}
