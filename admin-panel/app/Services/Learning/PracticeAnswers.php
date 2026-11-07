<?php

declare(strict_types=1);

namespace App\Services\Learning;

use InvalidArgumentException;

/**
 * Approved practice definitions and their marking (Action Plan 3.11). This
 * never writes official marks.
 *
 * Kinds: `choice` and `number` (one answer), `steps` (worked steps, each
 * with its own number or choice answer, a mark per step), `short_text`
 * (matched against accepted answers after normalising case, spacing and
 * punctuation; anything else waits for the learner's teacher), `diagram_label`
 * (labels placed on the numbered regions of an approved diagram, marked by
 * exact mapping) and `audio` (a recorded spoken answer, always marked by the
 * learner's teacher).
 *
 * question() is all a learner's device ever receives before answering: no
 * accepted answers, step answers, label mapping or explanation. solution()
 * is shown only after the server has recorded the learner's answer.
 */
final class PracticeAnswers
{
    public const KINDS = ['choice', 'number', 'steps', 'short_text', 'diagram_label', 'audio'];

    /** The two kinds of the first practice release; AI teaching drafts and tutor checkpoints use only these. */
    public const SINGLE = ['choice', 'number'];

    /** Kinds whose answer may wait for a teacher. */
    public const REVIEWED = ['short_text', 'audio'];

    public const SHORT_TEXT_MAX = 500;

    public function definition(array $input, array $kinds = self::KINDS): array
    {
        $kind = $input['kind'] ?? null;
        $prompt = $input['prompt'] ?? null;
        $explanation = $input['explanation'] ?? null;
        if (! is_string($kind) || ! in_array($kind, $kinds, true) || ! in_array($kind, self::KINDS, true)
            || ! is_string($prompt) || trim($prompt) === '' || mb_strlen($prompt) > 4000
            || ! is_string($explanation) || trim($explanation) === '' || mb_strlen($explanation) > 4000) {
            throw new InvalidArgumentException('Provide a bounded question, kind and worked explanation.');
        }
        $options = $input['options'] ?? [];
        $answers = $input['answers'] ?? [];
        if (! is_array($options) || ! array_is_list($options) || ! is_array($answers) || ! array_is_list($answers)) {
            throw new InvalidArgumentException('Provide approved answer choices.');
        }
        $base = ['kind' => $kind, 'prompt' => trim($prompt), 'explanation' => trim($explanation)];

        return match ($kind) {
            'choice', 'number' => $base + $this->single($kind, $options, $answers),
            'short_text' => $base + $this->shortText($options, $answers),
            'steps' => $base + $this->steps($options, $answers, $input['steps'] ?? null),
            'diagram_label' => $base + $this->diagram($options, $answers, $input),
            'audio' => $base + $this->audio($options, $answers, $input['max_seconds'] ?? null),
        };
    }

    /**
     * What a learner's device receives before answering: never accepted
     * answers, step answers, the label mapping or the explanation.
     */
    public function question(array $approvedDefinition): array
    {
        $d = $this->definition($approvedDefinition);
        $question = ['kind' => $d['kind'], 'prompt' => $d['prompt'], 'options' => $d['options']];

        return match ($d['kind']) {
            'steps' => $question + ['steps' => array_map(fn ($s) => ['id' => $s['id'], 'prompt' => $s['prompt'], 'kind' => $s['kind'], 'options' => $s['options']], $d['steps'])],
            'diagram_label' => $question + ['diagram' => ['width' => $d['diagram']['width'], 'height' => $d['diagram']['height']], 'regions' => $d['regions'], 'labels' => $d['labels']],
            'audio' => $question + ['max_seconds' => $d['max_seconds']],
            'short_text' => $question + ['max_length' => self::SHORT_TEXT_MAX],
            default => $question,
        };
    }

    /** Shown only after the learner's answer is recorded on the server. */
    public function solution(array $approvedDefinition): array
    {
        $d = $this->definition($approvedDefinition);

        return ['explanation' => $d['explanation'], 'answers' => $d['answers'],
            'steps' => $d['kind'] === 'steps' ? array_map(fn ($s) => ['id' => $s['id'], 'answers' => $s['answers']], $d['steps']) : [],
            'mapping' => $d['kind'] === 'diagram_label' ? array_map(fn ($region, $label) => ['region' => $region, 'label' => $label], array_keys($d['mapping']), array_values($d['mapping'])) : []];
    }

    /**
     * Mark one learner response. `answer` is a string (choice, number,
     * short_text), `parts` maps step or region codes to answers (steps,
     * diagram_label), `audio` says a recording arrived (audio).
     *
     * @param  array{answer?: ?string, parts?: ?array, audio?: bool}  $response
     * @return array{state: 'marked'|'awaiting_teacher', correct: ?bool, achieved: ?int, maximum: int, parts: ?array<string, bool>, stored: string}
     */
    public function mark(array $approvedDefinition, array $response): array
    {
        $d = $this->definition($approvedDefinition);
        $answer = $response['answer'] ?? null;
        $parts = $response['parts'] ?? null;
        $audio = (bool) ($response['audio'] ?? false);
        $usesParts = in_array($d['kind'], ['steps', 'diagram_label'], true);
        if (($d['kind'] === 'audio') !== $audio || $usesParts !== ($parts !== null) || (! $usesParts && $d['kind'] !== 'audio') !== ($answer !== null)) {
            throw new InvalidArgumentException('Answer this kind of question in its own way.');
        }
        if ($d['kind'] === 'audio') {
            return ['state' => 'awaiting_teacher', 'correct' => null, 'achieved' => null, 'maximum' => 1, 'parts' => null, 'stored' => 'audio'];
        }
        if ($usesParts) {
            return $this->markParts($d, $parts);
        }
        if (! is_string($answer)) {
            throw new InvalidArgumentException('Enter an answer before submitting.');
        }
        $answer = trim($answer);
        if ($d['kind'] === 'short_text') {
            if ($answer === '' || mb_strlen($answer) > self::SHORT_TEXT_MAX || ! mb_check_encoding($answer, 'UTF-8')) {
                throw new InvalidArgumentException('Write a short answer of at most 500 characters.');
            }
            $matched = in_array($this->text($answer), array_map(fn ($accepted) => $this->text($accepted), $d['answers']), true);

            // Not matched is not wrong: the learner's teacher decides (an AI suggestion may help later, never decide).
            return $matched ? ['state' => 'marked', 'correct' => true, 'achieved' => 1, 'maximum' => 1, 'parts' => null, 'stored' => $answer]
                : ['state' => 'awaiting_teacher', 'correct' => null, 'achieved' => null, 'maximum' => 1, 'parts' => null, 'stored' => $answer];
        }
        $correct = $this->single1($d['kind'], $d['options'], $d['answers'], $answer);

        return ['state' => 'marked', 'correct' => $correct, 'achieved' => $correct ? 1 : 0, 'maximum' => 1, 'parts' => null, 'stored' => $this->canonical($d['kind'], $answer)];
    }

    /** One-answer kinds only (tutor checkpoints): is this answer accepted? */
    public function correct(array $approvedDefinition, string $answer): bool
    {
        $definition = $this->definition($approvedDefinition, self::SINGLE);
        if (mb_strlen(trim($answer)) > 100) {
            throw new InvalidArgumentException('The practice answer is too long.');
        }

        return (bool) $this->mark($definition, ['answer' => $answer])['correct'];
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

    /** Short answers compare without case, extra spaces or punctuation: "The Sun." matches "the sun". */
    public function text(string $value): string
    {
        $value = mb_strtolower(trim($value), 'UTF-8');
        $value = (string) preg_replace('/[\p{P}]+/u', ' ', $value);

        return trim((string) preg_replace('/\s+/u', ' ', $value));
    }

    /**
     * A definition from the panel's plain form fields (one entry per line),
     * used by the teachers' question bank and the HQ library alike.
     *
     * @param  array<string, mixed>  $fields
     * @param  array{source: string, id: string, width: int, height: int}|null  $image
     */
    public function fromForm(array $fields, ?array $image = null): array
    {
        $kind = (string) ($fields['question_kind'] ?? '');
        $pairs = function (string $text): array {
            $rows = [];
            foreach ($this->lines($text) as $line) {
                [$id, $value] = array_pad(array_map('trim', explode(':', $line, 2)), 2, '');
                $rows[] = ['id' => $id, 'text' => $value];
            }

            return $rows;
        };
        $definition = ['kind' => $kind, 'prompt' => (string) ($fields['prompt'] ?? ''), 'explanation' => (string) ($fields['explanation'] ?? ''), 'options' => [], 'answers' => []];
        if (in_array($kind, ['choice', 'number', 'short_text'], true)) {
            $definition['options'] = $kind === 'choice' ? $pairs((string) ($fields['options'] ?? '')) : [];
            $definition['answers'] = $this->lines((string) ($fields['answers'] ?? ''));
        } elseif ($kind === 'steps') {
            $steps = [];
            foreach ($this->lines((string) ($fields['steps'] ?? '')) as $index => $line) {
                $cells = array_map('trim', explode('|', $line));
                $options = [];
                foreach (array_filter(array_map('trim', explode(';', $cells[2] ?? ''))) as $option) {
                    [$id, $text] = array_pad(array_map('trim', explode(':', $option, 2)), 2, '');
                    $options[] = ['id' => $id, 'text' => $text];
                }
                $steps[] = ['id' => 's'.($index + 1), 'prompt' => $cells[0], 'kind' => $options === [] ? 'number' : 'choice', 'options' => $options,
                    'answers' => array_values(array_filter(array_map('trim', explode(',', $cells[1] ?? '')), fn ($a) => $a !== ''))];
            }
            $definition['steps'] = $steps;
        } elseif ($kind === 'diagram_label') {
            $regions = [];
            foreach ($pairs((string) ($fields['regions'] ?? '')) as $row) {
                [$x, $y] = array_pad(array_map('trim', explode(',', $row['text'], 2)), 2, '');
                $regions[] = ['id' => $row['id'], 'x' => ctype_digit($x) ? (int) $x : -1, 'y' => ctype_digit($y) ? (int) $y : -1];
            }
            $definition['regions'] = $regions;
            $definition['labels'] = $pairs((string) ($fields['labels'] ?? ''));
            $definition['mapping'] = [];
            foreach ($pairs((string) ($fields['mapping'] ?? '')) as $row) {
                if (array_key_exists($row['id'], $definition['mapping'])) {
                    throw new InvalidArgumentException('Give each region one label.');
                }
                $definition['mapping'][$row['id']] = $row['text'];
            }
            $definition['diagram'] = $image;
        } elseif ($kind === 'audio') {
            $definition['max_seconds'] = is_numeric($fields['max_seconds'] ?? null) ? (int) $fields['max_seconds'] : null;
        }

        return $this->definition($definition);
    }

    /** A definition as the plain form fields that would produce it, for editing. */
    public function toForm(array $approvedDefinition): array
    {
        $d = $this->definition($approvedDefinition);
        $pairs = fn (array $rows) => implode("\n", array_map(fn ($r) => $r['id'].': '.$r['text'], $rows));

        return ['question_kind' => $d['kind'], 'prompt' => $d['prompt'], 'explanation' => $d['explanation'],
            'options' => $pairs($d['options']), 'answers' => implode("\n", $d['answers']),
            'steps' => $d['kind'] === 'steps' ? implode("\n", array_map(fn ($s) => $s['prompt'].' | '.implode(', ', $s['answers'])
                .($s['options'] === [] ? '' : ' | '.implode('; ', array_map(fn ($o) => $o['id'].': '.$o['text'], $s['options']))), $d['steps'])) : '',
            'regions' => $d['kind'] === 'diagram_label' ? implode("\n", array_map(fn ($r) => $r['id'].': '.$r['x'].', '.$r['y'], $d['regions'])) : '',
            'labels' => $d['kind'] === 'diagram_label' ? $pairs($d['labels']) : '',
            'mapping' => $d['kind'] === 'diagram_label' ? implode("\n", array_map(fn ($r, $l) => $r.': '.$l, array_keys($d['mapping']), array_values($d['mapping']))) : '',
            'max_seconds' => $d['kind'] === 'audio' ? $d['max_seconds'] : null,
            'diagram_id' => $d['kind'] === 'diagram_label' ? $d['diagram']['id'] : null];
    }

    /** @return list<string> */
    private function lines(string $text): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/\R/', $text) ?: []), fn (string $line) => $line !== ''));
    }

    /** @return array{answers: list<string>, options: list<array{id: string, text: string}>} */
    private function single(string $kind, array $options, array $answers, int $optionText = 1000): array
    {
        if (count($answers) < 1 || count($answers) > 10) {
            throw new InvalidArgumentException('Provide approved answer choices.');
        }
        $choices = [];
        if ($kind === 'choice') {
            $choices = $this->options($options, 2, 6, $optionText);
        } elseif ($options !== []) {
            throw new InvalidArgumentException('Numeric questions do not have choice options.');
        }
        $canonical = [];
        foreach ($answers as $answer) {
            if (! is_string($answer) || mb_strlen($answer) > 100) {
                throw new InvalidArgumentException('Approved answers must be bounded strings.');
            }
            if ($kind === 'choice' && ! array_key_exists($answer, $choices)) {
                throw new InvalidArgumentException('The approved answer must be an offered option.');
            }
            $canonical[] = $this->canonical($kind, $answer);
        }

        // Key order is part of the tutor checkpoint definition hash: answers, then options, as before 3.11.
        return ['answers' => array_values(array_unique($canonical)), 'options' => $this->listed($choices)];
    }

    /** @return array<string, string> */
    private function options(array $options, int $minimum, int $maximum, int $length, string $what = 'option'): array
    {
        if (! array_is_list($options) || count($options) < $minimum || count($options) > $maximum) {
            throw new InvalidArgumentException("Give between $minimum and $maximum {$what}s.");
        }
        $choices = [];
        foreach ($options as $option) {
            $id = is_array($option) ? ($option['id'] ?? null) : null;
            $text = is_array($option) ? ($option['text'] ?? null) : null;
            if (! is_string($id) || ! preg_match('/^[a-z][a-z0-9_]{0,19}$/D', $id) || array_key_exists($id, $choices)
                || ! is_string($text) || trim($text) === '' || mb_strlen($text) > $length) {
                throw new InvalidArgumentException("Use unique lower-case {$what} codes and bounded {$what} text.");
            }
            $choices[$id] = trim($text);
        }

        return $choices;
    }

    /** @return list<array{id: string, text: string}> */
    private function listed(array $choices): array
    {
        return array_map(fn ($id, $text) => ['id' => (string) $id, 'text' => $text], array_keys($choices), array_values($choices));
    }

    private function canonical(string $kind, string $answer): string
    {
        return $kind === 'number' ? $this->number($answer) : trim($answer);
    }

    private function single1(string $kind, array $options, array $answers, string $answer): bool
    {
        if (mb_strlen($answer) > 100) {
            throw new InvalidArgumentException('The practice answer is too long.');
        }
        if ($kind === 'number') {
            return in_array($this->number($answer), $answers, true);
        }
        if (! in_array($answer, array_column($options, 'id'), true)) {
            throw new InvalidArgumentException('Choose an offered option before submitting.');
        }

        return in_array($answer, $answers, true);
    }

    private function shortText(array $options, array $answers): array
    {
        if ($options !== [] || count($answers) < 1 || count($answers) > 20) {
            throw new InvalidArgumentException('Give between one and twenty accepted short answers.');
        }
        $accepted = [];
        foreach ($answers as $answer) {
            if (! is_string($answer) || trim($answer) === '' || mb_strlen($answer) > 200 || $this->text($answer) === '') {
                throw new InvalidArgumentException('Accepted short answers must be words of at most 200 characters.');
            }
            $accepted[$this->text($answer)] ??= trim($answer);
        }

        return ['answers' => array_values($accepted), 'options' => []];
    }

    private function steps(array $options, array $answers, mixed $steps): array
    {
        if ($options !== [] || $answers !== [] || ! is_array($steps) || ! array_is_list($steps) || count($steps) < 2 || count($steps) > 8) {
            throw new InvalidArgumentException('A worked question needs two to eight steps, each with its own answer.');
        }
        $rows = [];
        foreach ($steps as $index => $step) {
            $id = 's'.($index + 1);
            $prompt = is_array($step) ? ($step['prompt'] ?? null) : null;
            $kind = is_array($step) ? ($step['kind'] ?? null) : null;
            if (! is_array($step) || ($step['id'] ?? $id) !== $id || ! is_string($prompt) || trim($prompt) === '' || mb_strlen($prompt) > 1000 || ! in_array($kind, self::SINGLE, true)
                || ! is_array($step['options'] ?? []) || ! is_array($step['answers'] ?? null)) {
                throw new InvalidArgumentException('Give each step, in order, a bounded prompt and a number or choice answer.');
            }
            $rows[] = ['id' => $id, 'prompt' => trim($prompt), 'kind' => $kind] + $this->single($kind, $step['options'] ?? [], $step['answers'], 200);
        }

        return ['answers' => [], 'options' => [], 'steps' => $rows];
    }

    private function diagram(array $options, array $answers, array $input): array
    {
        $image = $input['diagram'] ?? null;
        if ($options !== [] || $answers !== [] || ! is_array($image) || ! in_array($image['source'] ?? null, ['school', 'library'], true)
            || ! is_string($image['id'] ?? null) || ! preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/D', $image['id'])
            || ! is_int($image['width'] ?? null) || ! is_int($image['height'] ?? null) || $image['width'] < 1 || $image['height'] < 1 || $image['width'] > 4096 || $image['height'] > 4096) {
            throw new InvalidArgumentException('Add the diagram picture.');
        }
        $regions = $input['regions'] ?? null;
        if (! is_array($regions) || ! array_is_list($regions) || count($regions) < 1 || count($regions) > 12) {
            throw new InvalidArgumentException('Mark between one and twelve regions on the diagram.');
        }
        $points = [];
        foreach ($regions as $region) {
            $id = is_array($region) ? ($region['id'] ?? null) : null;
            $x = is_array($region) ? ($region['x'] ?? null) : null;
            $y = is_array($region) ? ($region['y'] ?? null) : null;
            if (! is_string($id) || ! preg_match('/^[a-z][a-z0-9]{0,2}$/D', $id) || array_key_exists($id, $points) || ! is_int($x) || ! is_int($y) || $x < 0 || $x > 100 || $y < 0 || $y > 100) {
                throw new InvalidArgumentException('Give each region a unique short code starting with a letter, and a position from 0 to 100 per cent across and down.');
            }
            $points[$id] = ['id' => $id, 'x' => $x, 'y' => $y];
        }
        $labels = $this->options($input['labels'] ?? null, count($points), 16, 200, 'label');
        $mapping = $input['mapping'] ?? null;
        if (! is_array($mapping) || count($mapping) !== count($points)) {
            throw new InvalidArgumentException('Give every region exactly one correct label.');
        }
        $ordered = [];
        foreach (array_keys($points) as $id) {
            $label = $mapping[$id] ?? null;
            if (! is_string($label) || ! array_key_exists($label, $labels)) {
                throw new InvalidArgumentException('Give every region exactly one correct label.');
            }
            $ordered[$id] = $label;
        }

        return ['answers' => [], 'options' => [], 'diagram' => ['source' => $image['source'], 'id' => $image['id'], 'width' => $image['width'], 'height' => $image['height']],
            'regions' => array_values($points), 'labels' => $this->listed($labels), 'mapping' => $ordered];
    }

    private function audio(array $options, array $answers, mixed $seconds): array
    {
        if ($options !== [] || $answers !== [] || ! is_int($seconds) || $seconds < 10 || $seconds > 180) {
            throw new InvalidArgumentException('A spoken answer needs a recording limit from 10 to 180 seconds, and no written answers.');
        }

        return ['answers' => [], 'options' => [], 'max_seconds' => $seconds];
    }

    private function markParts(array $d, mixed $parts): array
    {
        if (! is_array($parts) || count($parts) > 16) {
            throw new InvalidArgumentException('Answer every part before submitting.');
        }
        $marks = [];
        $stored = [];
        if ($d['kind'] === 'steps') {
            if (count($parts) !== count($d['steps'])) {
                throw new InvalidArgumentException('Answer every step before submitting.');
            }
            foreach ($d['steps'] as $step) {
                $answer = $parts[$step['id']] ?? null;
                if (! is_string($answer) || trim($answer) === '') {
                    throw new InvalidArgumentException('Answer every step before submitting.');
                }
                $marks[$step['id']] = $this->single1($step['kind'], $step['options'], $step['answers'], trim($answer));
                $stored[$step['id']] = $this->canonical($step['kind'], trim($answer));
            }
            $achieved = count(array_filter($marks));

            // Mastery evidence counts the share of correct steps.
            return ['state' => 'marked', 'correct' => $achieved === count($marks), 'achieved' => $achieved, 'maximum' => count($marks), 'parts' => $marks, 'stored' => json_encode($stored, JSON_THROW_ON_ERROR)];
        }
        $labels = array_column($d['labels'], 'id');
        foreach ($d['mapping'] as $region => $label) {
            $answer = $parts[$region] ?? null;
            if (! is_string($answer) || ! in_array($answer, $labels, true)) {
                throw new InvalidArgumentException('Place a label on every region before submitting.');
            }
            $marks[$region] = $answer === $label;
            $stored[$region] = $answer;
        }
        if (count($parts) !== count($marks)) {
            throw new InvalidArgumentException('Place a label on every region before submitting.');
        }
        $exact = ! in_array(false, $marks, true);

        // Marked by exact mapping: the whole diagram is right or not yet.
        return ['state' => 'marked', 'correct' => $exact, 'achieved' => $exact ? 1 : 0, 'maximum' => 1, 'parts' => $marks, 'stored' => json_encode($stored, JSON_THROW_ON_ERROR)];
    }
}
