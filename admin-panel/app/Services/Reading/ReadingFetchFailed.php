<?php

declare(strict_types=1);

namespace App\Services\Reading;

use RuntimeException;

/**
 * A book's text could not be fetched or cleaned. The code is a short reason
 * kept on the book's text row for HQ (never a URL, a body or a header value).
 */
class ReadingFetchFailed extends RuntimeException
{
    public const REASONS = [
        'no_mirror' => 'No mirror is configured (READING_MIRROR_URL).',
        'mirror_invalid' => 'The mirror address is not a plain https origin.',
        'unreachable' => 'The mirror could not be reached in time.',
        'http_error' => 'The mirror answered with an error.',
        'wrong_type' => 'The mirror sent something other than plain text.',
        'too_large' => 'The text is larger than the limit.',
        'not_utf8' => 'The text is not UTF-8.',
        'markers_missing' => 'The text has no Project Gutenberg start and end lines, so its licence cannot be removed reliably.',
        'empty_text' => 'The text was empty once the licence was removed.',
        'too_many_pages' => 'The text has more pages than the limit.',
        'storage' => 'The text could not be saved.',
    ];

    public function __construct(public readonly string $reason)
    {
        parent::__construct(self::REASONS[$reason] ?? $reason);
    }
}
