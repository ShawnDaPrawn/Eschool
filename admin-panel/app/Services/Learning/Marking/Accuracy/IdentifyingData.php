<?php

declare(strict_types=1);

namespace App\Services\Learning\Marking\Accuracy;

/**
 * Keeps identifying data out of evaluation scripts (Action Plan 3.18). Field
 * names are allow-listed by the importer; this names the usual identifying
 * fields for a clear refusal, and finds an e-mail address, a South African
 * identity number or a phone number written into free text, so the row is
 * refused rather than stored. It cannot recognise a written name: the import
 * form asks for de-identified scripts and the spec says so.
 */
final class IdentifyingData
{
    /** Field names that identify a person or a school (any part of the name); any other unknown field is refused too. */
    private const FIELDS = '/^(id|class|dob|sex)$|name|learner|student|pupil|candidate|child|surname|initials|identity|id.?num|passport|e.?mail|phone|mobile|cell|'
        .'telephone|address|admission|exam.?num|lurits|school|emis|birth|gender|race|guardian|parent|user/i';

    /** Whether a field name is one of the usual identifying fields. */
    public static function identifyingField(string $field): bool
    {
        return (bool) preg_match(self::FIELDS, trim($field));
    }

    /**
     * What identifying data the text holds, if any: 'email', 'id_number' or 'phone'.
     */
    public static function find(string $text): ?string
    {
        if (preg_match('/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i', $text)) {
            return 'email';
        }
        if (preg_match_all('/(?<!\d|\d[.,])(\d{6})[ -]?(\d{4})[ -]?(\d{3})(?!\d|[.,]\d)/', $text, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                if (self::identityNumber($match[1].$match[2].$match[3])) {
                    return 'id_number';
                }
            }
        }
        if (preg_match('/(?<!\d|\d[.,])(?:\+27|0027|0)[ -]?[1-8]\d(?:[ -]?\d){7}(?!\d|[.,]\d)/', $text)) {
            return 'phone';
        }

        return null;
    }

    /** A South African identity number: a real date of birth and a valid Luhn check digit. */
    private static function identityNumber(string $digits): bool
    {
        $month = (int) substr($digits, 2, 2);
        $day = (int) substr($digits, 4, 2);
        if ($month < 1 || $month > 12 || $day < 1 || $day > 31) {
            return false;
        }
        $sum = 0;
        foreach (str_split(strrev($digits)) as $i => $digit) {
            $value = (int) $digit * ($i % 2 === 1 ? 2 : 1);
            $sum += $value > 9 ? $value - 9 : $value;
        }

        return $sum % 10 === 0;
    }

    /** A plain sentence for a refusal. */
    public static function describe(string $kind): string
    {
        return match ($kind) {
            'email' => __('an e-mail address'),
            'id_number' => __('an identity number'),
            default => __('a phone number'),
        };
    }
}
