<?php

declare(strict_types=1);

namespace App\Services\Learning;

/**
 * Splits published content Markdown into bounded sections at its headings, so
 * the tutor can read the section that answers the learner's question rather
 * than only the start of the document.
 */
final class TutorSections
{
    /** Bytes per section: the same bound v1 used for the whole document. */
    public const BYTES = 3000;

    /** Sections per content version that may be compared. */
    public const MAXIMUM = 8;

    /** @return list<string> */
    public static function split(string $markdown): array
    {
        $sections = [];
        $current = '';
        foreach (preg_split('/\R/u', $markdown) ?: [] as $line) {
            if (preg_match('/^#{1,6}\s/u', $line) && trim($current) !== '') {
                $sections[] = $current;
                $current = '';
            }
            $current .= $line."\n";
        }
        if (trim($current) !== '') {
            $sections[] = $current;
        }
        $chunks = [];
        foreach ($sections as $section) {
            $rest = rtrim($section);
            while ($rest !== '') {
                $part = mb_strcut($rest, 0, self::BYTES, 'UTF-8');
                if ($part === '') {
                    break;
                }
                $chunks[] = $part;
                if (count($chunks) >= self::MAXIMUM) {
                    return $chunks;
                }
                $rest = ltrim(substr($rest, strlen($part)));
            }
        }

        return $chunks;
    }

    /** The v1 view of a document, used whenever no section is chosen. */
    public static function opening(string $markdown): string
    {
        return mb_strcut($markdown, 0, self::BYTES, 'UTF-8');
    }
}
