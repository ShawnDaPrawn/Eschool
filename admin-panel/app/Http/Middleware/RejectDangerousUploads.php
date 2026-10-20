<?php

declare(strict_types=1);

namespace App\Http\Middleware;

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
 */
class RejectDangerousUploads
{
    public const BLOCKED_EXTENSIONS = [
        'php', 'php3', 'php4', 'php5', 'php7', 'php8', 'pht', 'phtml', 'phar', 'phps', 'pgif', 'phpt',
        'shtml', 'shtm', 'stm', 'htaccess', 'htpasswd', 'ini', 'cgi', 'pl', 'py', 'rb', 'sh', 'bash',
        'asp', 'aspx', 'ascx', 'ashx', 'asmx', 'jsp', 'jspx', 'war', 'exe', 'dll', 'bat', 'cmd', 'vbs', 'ps1',
        'html', 'htm', 'xhtml', 'xht', 'mht', 'mhtml', 'hta', 'js', 'mjs', 'swf', 'xml', 'xsl', 'xslt', 'svgz',
    ];

    /** Content types detected from the file itself (PHP source has no mapped extension). */
    private const BLOCKED_MIME_PATTERN = '#php|html|javascript|ecmascript|^(text|application)/x-(sh|shellscript|perl|python|ruby|msdownload|executable|dosexec)$|^(text|application)/xml$#i';

    public function handle(Request $request, Closure $next)
    {
        foreach ($this->files($request->allFiles()) as $file) {
            if ($this->isDangerous($file) || ! $this->sanitizeSvg($file)) {
                $message = trans('The file type is not allowed.');

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

    /**
     * Rewrites an uploaded SVG without scripts, event handlers or remote references.
     * Returns false for an SVG that can't be parsed (it is refused).
     */
    private function sanitizeSvg(UploadedFile $file): bool
    {
        $isSvg = strtolower($file->getClientOriginalExtension()) === 'svg'
            || $file->guessExtension() === 'svg'
            || str_contains((string) $file->getMimeType(), 'svg');
        if (! $isSvg) {
            return true;
        }

        $sanitizer = new Sanitizer();
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
