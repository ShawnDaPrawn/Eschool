<?php

declare(strict_types=1);

namespace App\Services\Reading;

/**
 * Turns a Project Gutenberg plain-text file into the text Navigate Ed serves
 * (Action Plan 4.4), and cuts it into pages.
 *
 * The Project Gutenberg licence lets anyone use a public-domain text freely
 * once the licence and every reference to Project Gutenberg are removed, and
 * its trademark may not be used otherwise. So the header (everything before
 * the "*** START OF ... ***" line) and the footer and licence (from the
 * "*** END OF ... ***" line) are cut off, and any line left in the body that
 * names Project Gutenberg is dropped. A file without both lines is refused:
 * the licence cannot then be removed reliably. The header is kept apart for
 * HQ's rights check (it names the translator and edition), never for learners.
 */
class ReadingText
{
    private const START = '/^\*{3}\s*START OF (?:THE|THIS) PROJECT GUTENBERG E-?BOOK.*$/miu';

    private const END = '/^\*{3}\s*END OF (?:THE|THIS) PROJECT GUTENBERG E-?BOOK.*$/miu';

    private const MARK = '/project\s+gutenberg|gutenberg-tm|gutenberg\.org/iu';

    /**
     * @return array{text: string, header: string, removed_lines: int, word_count: int}
     *
     * @throws ReadingFetchFailed when the file is not valid UTF-8 or has no start and end lines
     */
    public function clean(string $raw): array
    {
        if (str_starts_with($raw, "\xEF\xBB\xBF")) {
            $raw = substr($raw, 3);
        }
        if (! mb_check_encoding($raw, 'UTF-8')) {
            throw new ReadingFetchFailed('not_utf8');
        }
        $raw = str_replace(["\r\n", "\r"], "\n", $raw);
        if (! preg_match(self::START, $raw, $start, PREG_OFFSET_CAPTURE) || ! preg_match(self::END, $raw, $end, PREG_OFFSET_CAPTURE, (int) $start[0][1])) {
            throw new ReadingFetchFailed('markers_missing');
        }
        $bodyStart = (int) $start[0][1] + strlen($start[0][0]);
        $header = trim(substr($raw, 0, (int) $start[0][1]));
        $body = substr($raw, $bodyStart, (int) $end[0][1] - $bodyStart);

        $kept = [];
        $removed = 0;
        foreach (explode("\n", $body) as $line) {
            if (preg_match(self::MARK, $line) === 1) {
                $removed++;

                continue;
            }
            $kept[] = rtrim($line);
        }
        $text = trim((string) preg_replace("/\n{3,}/", "\n\n", implode("\n", $kept)));
        if ($text === '') {
            throw new ReadingFetchFailed('empty_text');
        }

        return ['text' => $text, 'header' => mb_substr($header, 0, 3000), 'removed_lines' => $removed,
            'word_count' => (int) preg_match_all('/\S+/u', $text)];
    }

    /**
     * Byte offsets [start, length] of each page: whole paragraphs up to about
     * $size characters; a longer paragraph is cut at a space.
     *
     * @return list<array{0: int, 1: int}>
     */
    public function pages(string $text, int $size): array
    {
        $size = max(200, $size);
        $pages = [];
        $offset = 0;
        $length = strlen($text);
        while ($offset < $length) {
            $window = substr($text, $offset, $size * 4 + 4);
            $chars = mb_strlen($window);
            if ($chars <= $size) {
                $cut = strlen($window);
            } else {
                $head = mb_substr($window, 0, $size);
                $paragraph = strrpos($head, "\n\n");
                $space = strrpos($head, ' ');
                $cut = $paragraph !== false && $paragraph > strlen($head) / 3 ? $paragraph + 2 : ($space !== false && $space > 0 ? $space + 1 : strlen($head));
            }
            $piece = substr($text, $offset, $cut);
            $trimmedStart = strlen($piece) - strlen(ltrim($piece));
            $trimmed = trim($piece);
            if ($trimmed !== '') {
                $pages[] = [$offset + $trimmedStart, strlen($trimmed)];
            }
            $offset += $cut;
        }

        return $pages;
    }
}
