<?php

declare(strict_types=1);

namespace App\Services\Learning;

use App\Models\Learning\TutorMessage;
use App\Services\Ai\Inference\AiUnavailable;

/**
 * The tutor's prompt versions (Action Plan 3.10). v1 ('tutor') stays
 * selectable with its original context; v2 ('tutor-v2') adds stage and grade,
 * worked examples and misconceptions, "explain it back", the learner's memory,
 * the chosen content section and home-language explanations. Each turn records
 * the version it was built for, and the gateway refuses a turn whose version
 * is no longer the configured one.
 */
final class TutorPrompts
{
    public const V1 = 'tutor';

    public const V2 = 'tutor-v2';

    public const TURN_TYPES = ['ask', 'explain_back'];

    public static function selected(): string
    {
        $prompt = config('ai.inference.features.tutor.prompt');
        if (! in_array($prompt, [self::V1, self::V2], true)) {
            throw new AiUnavailable('prompt_not_allowed');
        }

        return $prompt;
    }

    /** Messages written before v2 have no recorded key and were built for v1. */
    public static function of(TutorMessage $message): string
    {
        return $message->prompt_key === self::V2 ? self::V2 : self::V1;
    }

    /** What the model is asked to do: hint before working, correction of working, or feedback on an explanation. */
    public static function phase(TutorMessage $message): string
    {
        if (self::of($message) === self::V2 && $message->turn_type === 'explain_back') {
            return 'explain_back';
        }

        return trim((string) $message->working) === '' ? 'hint' : 'correction';
    }

    /** @return list<string> */
    public static function kinds(TutorMessage $message): array
    {
        return self::phase($message) === 'hint' ? ['hint', 'question', 'refusal'] : ['hint', 'question', 'correction', 'refusal'];
    }
}
