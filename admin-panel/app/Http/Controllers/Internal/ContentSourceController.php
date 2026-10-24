<?php

declare(strict_types=1);

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Services\Academics\Content\ContentFiles;
use App\Services\Academics\Content\ContentPipeline;
use App\Services\Platform\Audit\Audit;
use App\Services\Platform\Tenancy\CurrentSchool;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\RateLimiter;

class ContentSourceController extends Controller
{
    public function __invoke(Request $request, int $school, int $version, string $generation): Response
    {
        abort_unless($request->hasValidSignature(false), 403);
        $expiry = filter_var($request->query('expires'), FILTER_VALIDATE_INT);
        abort_unless($expiry !== false && $expiry <= now()->addSeconds(330)->getTimestamp(), 403);
        $key = 'converter-source:'.hash('sha256', (string) $request->query('signature'));
        abort_if(RateLimiter::tooManyAttempts($key, 10), 429);
        RateLimiter::hit($key, 300);

        return app(CurrentSchool::class)->run($school, function () use ($version, $generation) {
            $source = app(ContentPipeline::class)->source($version, $generation);
            $bytes = app(ContentFiles::class)->read($source);
            Audit::record('academics.content_source_delivered', $source, [], ['version_id' => $version, 'service' => 'converter', 'sha256' => $source->sha256]);

            return response($bytes, 200, ['Content-Type' => 'application/octet-stream', 'Content-Length' => (string) strlen($bytes), 'Cache-Control' => 'private, no-store, no-transform', 'X-Content-Type-Options' => 'nosniff', 'Content-Security-Policy' => 'sandbox']);
        });
    }
}
