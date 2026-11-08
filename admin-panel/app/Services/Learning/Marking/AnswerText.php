<?php

declare(strict_types=1);

namespace App\Services\Learning\Marking;

use Illuminate\Support\Str;

/**
 * Brings typed answers and memo rules to one plain form before marking
 * (Action Plan 3.16): one kind of minus, space and division sign, superscripts
 * written as powers (s⁻¹ → s^-1, x² → x^2) and √ as sqrt.
 */
final class AnswerText
{
    private const SUPERSCRIPTS = ['⁰' => '0', '¹' => '1', '²' => '2', '³' => '3', '⁴' => '4', '⁵' => '5', '⁶' => '6', '⁷' => '7', '⁸' => '8', '⁹' => '9', '⁻' => '-', '⁺' => '+'];

    public static function normalize(string $text): string
    {
        $text = str_replace(["\u{2212}", "\u{2013}", "\u{2014}"], '-', $text);
        $text = str_replace(["\u{00A0}", "\u{202F}", "\u{2009}", "\u{2007}", "\t"], ' ', $text);
        $text = str_replace(['÷', '∕'], '/', $text);
        $text = str_replace(['≈', '≅', '≡'], '=', $text);
        $text = str_replace(['√'], 'sqrt', $text);
        $text = str_replace(['∴'], '', $text);
        $text = (string) preg_replace_callback('/[⁰¹²³⁴⁵⁶⁷⁸⁹⁻⁺]+/u', fn (array $m) => '^'.strtr($m[0], self::SUPERSCRIPTS), $text);

        return trim((string) preg_replace('/ {2,}/', ' ', $text));
    }

    /** Lower-case ASCII words separated by single spaces, for term matching. */
    public static function words(string $text): string
    {
        $text = Str::lower(Str::ascii(self::normalize($text)));

        return trim((string) preg_replace('/[^a-z0-9]+/', ' ', $text));
    }
}
