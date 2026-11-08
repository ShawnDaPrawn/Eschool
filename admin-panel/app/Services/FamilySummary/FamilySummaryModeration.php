<?php

declare(strict_types=1);

namespace App\Services\FamilySummary;

use App\Services\Ai\Inference\Capability;
use App\Services\Ai\Inference\InferenceModerator;
use App\Services\Ai\Inference\ModerationContext;
use App\Services\Ai\Providers\OpenAiModerator;

/**
 * Moderation for the daily family summary (Action Plan 5.11): the shared
 * fail-closed moderation, and then the summary's own rules for an answer.
 * Plain text of bounded length, no links, HTML or contact details, and no
 * private topic: an answer that mentions marks, discipline, wellbeing or
 * health is refused (and quarantined by the gateway), so the plain template
 * is sent instead.
 */
class FamilySummaryModeration implements InferenceModerator
{
    public const MAXIMUM_LENGTH = 1500;

    /** Words a summary built only from timetable, homework and forms never needs. */
    private const PRIVATE_TOPICS = '/\b(marks?|scored?|scores|percent|results?|report card|discipline|detention|suspen\w*|demerit\w*|incident\w*|wellbeing|counsell?or\w*|health|medical|medication|diagnos\w*|illness|injur\w*|fees?|owing|arrears)\b|%/iu';

    public function preflight(Capability $capability, string|array $input, ModerationContext $context): void
    {
        app(OpenAiModerator::class)->preflight($capability, $input, $context);
    }

    public function flags(Capability $capability, string $direction, string|array $content, ModerationContext $context): array
    {
        $flags = app(OpenAiModerator::class)->flags($capability, $direction, $content, $context);
        if ($flags !== [] || $direction !== 'output') {
            return $flags;
        }
        if (! is_string($content) || self::clean($content) === null) {
            return ['family_summary_output_invalid'];
        }

        return preg_match(self::PRIVATE_TOPICS, $content) === 1 ? ['family_summary_private_topic'] : [];
    }

    /** The answer as plain text, or null when it is not one the summary may send. */
    public static function clean(string|array $output): ?string
    {
        if (! is_string($output) || ! mb_check_encoding($output, 'UTF-8') || str_contains($output, "\0")) {
            return null;
        }
        $text = trim(preg_replace("/\r\n?/", "\n", $output) ?? '');
        if ($text === '' || mb_strlen($text) > self::MAXIMUM_LENGTH
            || preg_match('~(?:https?://|www\.|mailto:|<[a-z/!]|&[a-z]+;|@|\+?\d[\d ]{8,}\d)~iu', $text) === 1
            || preg_match(self::PRIVATE_TOPICS, $text) === 1) {
            return null;
        }

        return $text;
    }
}
