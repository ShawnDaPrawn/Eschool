<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\Platform\Files\FileNames;
use Closure;
use enshrined\svgSanitize\Sanitizer;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

/**
 * Uploads are saved to the public disk in many places, several of them under the
 * name the client chose. A PHP or HTML file stored there would be served from
 * /storage and run on the server or in the visitor's browser, so files with a
 * script or web-page extension (by name or by content) are refused everywhere.
 * SVG images are allowed (subjects and the website use them) but scripts, event
 * handlers and external references are stripped from them first, since not every
 * web server applies storage/app/public/.htaccess.
 *
 * Also refused: a picture or PDF whose content is something else (photo.jpg that
 * is really a PDF or a script), and a name that is nothing but dots or carries
 * control characters. PHP and Symfony keep only the last part of a path a client
 * sends, and FileNames::sanitize() cleans every name that is stored or shown.
 */
class RejectDangerousUploads
{
    public const BLOCKED_EXTENSIONS = [
        'php', 'php3', 'php4', 'php5', 'php7', 'php8', 'pht', 'phtml', 'phar', 'phps', 'pgif', 'phpt',
        'shtml', 'shtm', 'stm', 'htaccess', 'htpasswd', 'ini', 'cgi', 'pl', 'py', 'rb', 'sh', 'bash',
        'asp', 'aspx', 'ascx', 'ashx', 'asmx', 'jsp', 'jspx', 'war', 'exe', 'dll', 'bat', 'cmd', 'vbs', 'ps1',
        'html', 'htm', 'xhtml', 'xht', 'mht', 'mhtml', 'hta', 'js', 'mjs', 'swf', 'xml', 'xsl', 'xslt', 'svgz',
    ];

    /** Extensions whose content is checked: a picture must be a picture, a PDF a PDF. */
    private const CONTENT_FAMILIES = [
        'jpg' => 'image/', 'jpeg' => 'image/', 'png' => 'image/', 'gif' => 'image/', 'webp' => 'image/', 'bmp' => 'image/',
        'pdf' => 'application/pdf',
    ];

    /** Content types detected from the file itself (PHP source has no mapped extension). */
    private const BLOCKED_MIME_PATTERN = '#php|html|javascript|ecmascript|^(text|application)/x-(sh|shellscript|perl|python|ruby|msdownload|executable|dosexec)$|^(text|application)/xml$#i';

    /**
     * Images are decoded at about 4 bytes a pixel whatever their file size, so a
     * small, highly compressed picture can need gigabytes. 30 megapixels covers
     * phone and camera photos.
     */
    public const MAX_IMAGE_PIXELS = 30_000_000;

    /** SVGs are parsed into a DOM to sanitise them, which takes many times their size. */
    public const MAX_SVG_BYTES = 2 * 1024 * 1024;

    public function handle(Request $request, Closure $next)
    {
        foreach ($this->files($request->allFiles()) as $file) {
            $message = match (true) {
                $this->isDangerous($file) => trans('The file type is not allowed.'),
                FileNames::isRefused($file->getClientOriginalName()) => trans('The file name is not allowed.'),
                $this->contentDoesNotMatch($file) => trans('The file content does not match its extension.'),
                $this->hasTooManyPixels($file) => trans('The image is too large. Please upload one of at most 30 megapixels.'),
                ! $this->sanitizeSvg($file) => trans('The SVG file is too large or not valid.'),
                default => null,
            };

            if ($message !== null) {
                if ($request->is('api/*') || $request->expectsJson() || $request->ajax()) {
                    return response()->json(['error' => true, 'message' => $message, 'code' => 102], 422);
                }

                return redirect()->back()->withErrors(['message' => $message]);
            }
        }

        return $next($request);
    }

    private function isDangerous(UploadedFile $file): bool
    {
        // Every dot-separated part counts: Apache can run "photo.php.jpg" as PHP.
        $parts = explode('.', strtolower($file->getClientOriginalName()));
        array_shift($parts);
        $parts[] = strtolower((string) $file->guessExtension());

        return array_intersect($parts, self::BLOCKED_EXTENSIONS) !== []
            || preg_match(self::BLOCKED_MIME_PATTERN, (string) $file->getMimeType()) === 1;
    }

    /** A .jpg, .png, .pdf (and so on) whose detected type is of another kind. */
    private function contentDoesNotMatch(UploadedFile $file): bool
    {
        $expected = self::CONTENT_FAMILIES[strtolower($file->getClientOriginalExtension())] ?? null;
        if ($expected === null) {
            return false;
        }

        return ! str_starts_with(strtolower((string) $file->getMimeType()), $expected);
    }

    /** Reads only the image header, so it is cheap even for a huge file. */
    private function hasTooManyPixels(UploadedFile $file): bool
    {
        $size = @getimagesize($file->getRealPath());

        return is_array($size) && $size[0] * $size[1] > self::MAX_IMAGE_PIXELS;
    }

    /**
     * Rewrites an uploaded SVG without scripts, event handlers or remote references.
     * Returns false for an SVG that is too large or can't be parsed (it is refused).
     */
    private function sanitizeSvg(UploadedFile $file): bool
    {
        $isSvg = strtolower($file->getClientOriginalExtension()) === 'svg'
            || $file->guessExtension() === 'svg'
            || str_contains((string) $file->getMimeType(), 'svg');
        if (! $isSvg) {
            return true;
        }
        if ($file->getSize() > self::MAX_SVG_BYTES) {
            return false;
        }

        $sanitizer = new Sanitizer;
        $sanitizer->removeRemoteReferences(true);
        $clean = $sanitizer->sanitize((string) file_get_contents($file->getRealPath()));

        return $clean !== false && file_put_contents($file->getRealPath(), $clean) !== false;
    }

    /**
     * @return iterable<UploadedFile>
     */
    private function files(array $files): iterable
    {
        foreach ($files as $file) {
            if (is_array($file)) {
                yield from $this->files($file);
            } elseif ($file instanceof UploadedFile) {
                yield $file;
            }
        }
    }
}
