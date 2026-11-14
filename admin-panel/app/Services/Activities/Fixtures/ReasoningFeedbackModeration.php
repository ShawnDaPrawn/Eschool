<?php

declare(strict_types=1);

namespace App\Services\Activities\Fixtures;

use App\Services\Ai\Inference\Capability;
use App\Services\Ai\Inference\InferenceModerator;
use App\Services\Ai\Inference\ModerationContext;
use App\Services\Ai\Providers\OpenAiModerator;
use App\Services\Learning\SafeguardingPhrases;
use JsonException;

/**
 * Moderation for AI feedback on reasoning (Action Plan 5.3). Input: the same
 * safeguarding phrase guard the tutor uses (every platform language, before
 * any network call), then the shared fail-closed moderation. Output: the
 * shared moderation, then the feedback's own shape: plain advice under fixed
 * headings, with no score, mark, winner or verdict, no links and no contact
 * details. Anything else is quarantined by the gateway and nothing is shown.
 */
class ReasoningFeedbackModeration implements InferenceModerator
{
    public const KEYS = ['structure', 'evidence', 'rebuttal', 'fallacies', 'summary'];

    /** A mark or a verdict is never part of advice to an adjudicator. */
    private const VERDICT = '/\b\d+(?:[.,]\d+)?\s*(?:\/|out of)\s*\d+\b|\b\d+(?:[.,]\d+)?\s*(?:%|percent(?!\w)|points?(?!\w))|\b(?:should|would|will|deserves? to) (?:win|lose)\b|\bthe winner\b/iu';

    public function preflight(Capability $capability, string|array $input, ModerationContext $context): void
    {
        app(OpenAiModerator::class)->preflight($capability, $input, $context);
    }

    public function flags(Capability $capability, string $direction, string|array $content, ModerationContext $context): array
    {
        if ($direction === 'input' && app(SafeguardingPhrases::class)->caught(is_string($content) ? $content : json_encode($content, JSON_THROW_ON_ERROR))) {
            return ['safeguarding_disclosure'];
        }
        $flags = app(OpenAiModerator::class)->flags($capability, $direction, $content, $context);
        if ($flags !== [] || $direction !== 'output') {
            return $flags;
        }

        return self::parse($content) === null ? ['reasoning_feedback_output_invalid'] : [];
    }

    /**
     * The feedback as structured advice, or null when the answer is not one
     * an adjudicator may be shown.
     *
     * @return array{structure: string, evidence: string, rebuttal: string, fallacies: list<array{name: string, explanation: string}>, summary: string}|null
     */
    public static function parse(string|array $output): ?array
    {
        if (! is_string($output) || strlen($output) > 16000 || ! mb_check_encoding($output, 'UTF-8') || str_contains($output, "\0")) {
            return null;
        }
        try {
            $data = json_decode($output, true, 4, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }
        if (! is_array($data) || array_keys($data) !== self::KEYS || ! is_array($data['fallacies']) || count($data['fallacies']) > 6 || ! array_is_list($data['fallacies'])) {
            return null;
        }
        $texts = [];
        foreach (['structure', 'evidence', 'rebuttal', 'summary'] as $key) {
            if (! is_string($data[$key]) || trim($data[$key]) === '' || mb_strlen($data[$key]) > 1200) {
                return null;
            }
            $texts[] = $data[$key];
        }
        $fallacies = [];
        foreach ($data['fallacies'] as $row) {
            if (! is_array($row) || array_keys($row) !== ['name', 'explanation'] || ! is_string($row['name']) || ! is_string($row['explanation'])
                || trim($row['name']) === '' || mb_strlen($row['name']) > 80 || trim($row['explanation']) === '' || mb_strlen($row['explanation']) > 600) {
                return null;
            }
            $fallacies[] = ['name' => trim($row['name']), 'explanation' => trim($row['explanation'])];
            array_push($texts, $row['name'], $row['explanation']);
        }
        $all = implode("\n", $texts);
        if (preg_match('~(?:https?://|www\.|mailto:|<[a-z/!]|@)~iu', $all) === 1 || preg_match(self::VERDICT, $all) === 1) {
            return null;
        }

        return ['structure' => trim($data['structure']), 'evidence' => trim($data['evidence']), 'rebuttal' => trim($data['rebuttal']), 'fallacies' => $fallacies, 'summary' => trim($data['summary'])];
    }
}
