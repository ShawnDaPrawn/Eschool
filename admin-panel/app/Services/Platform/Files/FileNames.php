<?php

declare(strict_types=1);

namespace App\Services\Platform\Files;

use Illuminate\Http\UploadedFile;

/**
 * The server's sanitizeFileName, the counterpart of the apps' one in
 * packages/eschool_core/lib/src/utils/file_name_utils.dart.
 *
 * A name a client sent becomes safe to use as a single file name inside a
 * folder, and to show or put in a URL: path separators, control characters and
 * characters not allowed in file names (plus the URL and HTML specials # % ' `)
 * are replaced with "_", ".." runs collapse to ".", and leading or trailing dots
 * and spaces are removed, so the name cannot climb out of its folder or hide
 * as a dotfile. Every getClientOriginalName() in app/ goes through it.
 */
final class FileNames
{
    /** Longest name kept, in characters; the extension is kept when cutting. */
    public const MAX_LENGTH = 150;

    private const UNSAFE = '/[\/\\\\:*?"<>|#%\'`\x00-\x1F\x7F]/u';

    public static function sanitize(string $name, string $fallback = 'file'): string
    {
        $clean = (string) preg_replace(self::UNSAFE, '_', $name);
        if (! mb_check_encoding($clean, 'UTF-8')) {
            $clean = (string) preg_replace('/[^\x20-\x7E]/', '_', $name);
            $clean = (string) preg_replace(self::UNSAFE, '_', $clean);
        }
        while (str_contains($clean, '..')) {
            $clean = str_replace('..', '.', $clean);
        }
        $clean = (string) preg_replace('/^[.\s]+|[.\s]+$/u', '', $clean);

        if (mb_strlen($clean) > self::MAX_LENGTH) {
            $extension = pathinfo($clean, PATHINFO_EXTENSION);
            $keep = $extension !== '' && mb_strlen($extension) < 16 ? '.'.$extension : '';
            $clean = rtrim(mb_substr($clean, 0, self::MAX_LENGTH - mb_strlen($keep)), '. ').$keep;
        }

        return $clean === '' ? $fallback : $clean;
    }

    /** The sanitised version of the name the client gave an upload. */
    public static function forUpload(UploadedFile $file, string $fallback = 'file'): string
    {
        return self::sanitize($file->getClientOriginalName(), $fallback);
    }

    /**
     * Whether a client's name is unusable rather than merely untidy: it is
     * nothing but dots and spaces (".", "..") or carries control characters.
     */
    public static function isRefused(string $name): bool
    {
        return trim($name, ". \t") === '' || preg_match('/[\x00-\x1F\x7F]/', $name) === 1;
    }
}
