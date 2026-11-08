<?php

declare(strict_types=1);

namespace App\Services\Learning;

/**
 * Home-language explanations (Action Plan 3.10). Only languages the platform
 * already offers in its apps and panel may be chosen, only those the
 * deployment lists in ai.tutor.home_languages, and only while the language's
 * safeguarding phrase list has a professional review recorded in HQ for the
 * list as it is now (Action Plan 3.2, SafeguardingPhraseReviews). A draft list
 * is still checked on every message; it only keeps the language out of the
 * tutor. English is always the default explanation language.
 */
final class TutorLanguages
{
    /** Platform languages besides English, with their own names. */
    public const OFFERED = ['af' => 'Afrikaans', 'zu' => 'isiZulu'];

    /** @return array<string, string> code => name */
    public static function enabled(): array
    {
        $configured = array_filter((array) config('ai.tutor.home_languages', []), 'is_string');
        $listed = array_intersect_key(self::OFFERED, array_flip($configured));
        if ($listed === []) {
            return [];
        }

        return array_intersect_key($listed, array_flip(app(SafeguardingPhraseReviews::class)->reviewedLanguages()));
    }

    /** @return list<array{code: string, name: string}> */
    public static function choices(): array
    {
        $choices = [];
        foreach (self::enabled() as $code => $name) {
            $choices[] = ['code' => $code, 'name' => $name];
        }

        return $choices;
    }
}
