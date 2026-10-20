<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

/**
 * Uploads are saved to the public disk in many places, several of them under the
 * name the client chose. A PHP or HTML file stored there would be served from
 * /storage and run on the server or in the visitor's browser, so files with a
 * script or web-page extension (by name or by content) are refused everywhere.
 */
class RejectDangerousUploads
{
    public const BLOCKED_EXTENSIONS = [
        'php', 'php3', 'php4', 'php5', 'php7', 'php8', 'pht', 'phtml', 'phar', 'phps', 'pgif', 'phpt',
        'shtml', 'shtm', 'stm', 'htaccess', 'htpasswd', 'ini', 'cgi', 'pl', 'py', 'rb', 'sh', 'bash',
        'asp', 'aspx', 'ascx', 'ashx', 'asmx', 'jsp', 'jspx', 'war', 'exe', 'dll', 'bat', 'cmd', 'vbs', 'ps1',
        'html', 'htm', 'xhtml', 'xht', 'mht', 'mhtml', 'hta', 'js', 'mjs', 'swf', 'xml', 'xsl', 'xslt',
    ];

    public function handle(Request $request, Closure $next)
    {
        foreach ($this->files($request->allFiles()) as $file) {
            if ($this->isDangerous($file)) {
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

        return array_intersect($parts, self::BLOCKED_EXTENSIONS) !== [];
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
