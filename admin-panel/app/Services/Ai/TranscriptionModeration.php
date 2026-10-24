<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Models\Academics\ContentSource;
use App\Services\Academics\Content\ContentFiles;
use App\Services\Academics\Content\ContentProcessingFailure;

/** Local input preflight/output guards. Every transcript still needs teacher review. */
class TranscriptionModeration
{
    public function input(ContentSource $source): void
    {
        if ($source->scan_state !== 'clean' || ! in_array($source->extension, ContentFiles::MEDIA, true) || $source->bytes < 1 || $source->bytes > (int) config('academic_content.maximum_source_bytes')) {
            throw new ContentProcessingFailure('transcription_input_refused', false);
        }
    }

    public function flags(string $text): array
    {
        if (str_contains($text, "\0") || ! mb_check_encoding($text, 'UTF-8')) {
            return ['invalid_transcript'];
        }

        // High-specificity safeguarding phrases are quarantined for school review;
        // broad curriculum words are not treated as a safety finding.
        return preg_match('/\b(?:send (?:me )?nudes|sexual (?:photos|images) of (?:a )?child)\b/iu', $text) ? ['safeguarding_review'] : [];
    }
}
