<?php

declare(strict_types=1);

namespace App\Services\Learning;

/**
 * Home-language explanations (Action Plan 3.10). Only languages the platform
 * already offers in its apps and panel may be chosen, and only those the
 * deployment lists in ai.tutor.home_languages: each needs its professionally
 * reviewed safeguarding phrase list (Action Plan 3.2) before it is listed.
 * English is always the default explanation language.
 */
final class TutorLanguages
{
    /** Platform languages besides English, with their own names. */
    public const OFFERED = ['af' => 'Afrikaans', 'zu' => 'isiZulu'];

    /** @return array<string, string> code => name */
    public static function enabled(): array
    {
        $configured = array_filter((array) config('ai.tutor.home_languages', []), 'is_string');

        return array_intersect_key(self::OFFERED, array_flip($configured));
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
