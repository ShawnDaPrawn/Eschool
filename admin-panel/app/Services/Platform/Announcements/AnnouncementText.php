<?php

declare(strict_types=1);

namespace App\Services\Platform\Announcements;

use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

/**
 * The text of a Navigate Ed announcement (SA.13): plain text with a few
 * markdown marks. Everything is escaped first, so HTML in the text shows as
 * text; then only these become markup: blank lines separate paragraphs, lines
 * that start with "- " make a list, **bold**, and [words](https://link).
 * Nothing else (no images, no raw HTML, no other link schemes).
 */
final class AnnouncementText
{
    public static function html(string $text): HtmlString
    {
        $blocks = preg_split('/\R{2,}/u', trim(str_replace("\r\n", "\n", $text))) ?: [];
        $html = [];
        foreach ($blocks as $block) {
            $lines = preg_split('/\R/u', trim($block)) ?: [];
            if ($lines !== [] && collect($lines)->every(fn (string $line) => preg_match('/^\s*[-*]\s+\S/u', $line) === 1)) {
                $items = array_map(fn (string $line) => '<li>'.self::inline((string) preg_replace('/^\s*[-*]\s+/u', '', $line)).'</li>', $lines);
                $html[] = '<ul>'.implode('', $items).'</ul>';

                continue;
            }
            $html[] = '<p>'.implode('<br>', array_map(fn (string $line) => self::inline(trim($line)), $lines)).'</p>';
        }

        return new HtmlString(implode("\n", $html));
    }

    /** The words without the marks, for a banner line, a notice or a preview. */
    public static function plain(string $text, int $limit = 240): string
    {
        $plain = (string) preg_replace('/\[([^\]\n]+)\]\((https:\/\/[^\s)]+)\)/u', '$1', $text);
        $plain = str_replace('**', '', $plain);
        $plain = (string) preg_replace('/^\s*[-*]\s+/mu', '', $plain);
        $plain = trim((string) preg_replace('/\s+/u', ' ', $plain));

        return Str::limit($plain, $limit);
    }

    private static function inline(string $line): string
    {
        $escaped = e($line);
        $escaped = (string) preg_replace('/\*\*(?=\S)(.+?)(?<=\S)\*\*/u', '<strong>$1</strong>', $escaped);

        // The URL was escaped with the rest, so it cannot leave the attribute.
        return (string) preg_replace('/\[([^\]\n]+)\]\((https:\/\/[^\s)]+)\)/u', '<a href="$2" rel="noopener noreferrer" target="_blank">$1</a>', $escaped);
    }
}
