<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gives every request an id, puts it into the log context (so every log line
 * of the request, and of any job it queues, carries it) and returns it in the
 * X-Request-Id header, so a user's report can be matched to the logs.
 *
 * A caller's own X-Request-Id is kept when it is a plain token (letters,
 * digits, dot, dash, underscore, colon; 8 to 128 characters), so the apps can
 * correlate their calls; anything else is replaced, so the header cannot be
 * used to write arbitrary text into the logs. On Cloud Run the trace id from
 * X-Cloud-Trace-Context (or traceparent) is kept too, which links the log
 * lines to the request in Cloud Logging.
 */
class AssignRequestId
{
    public const HEADER = 'X-Request-Id';

    public function handle(Request $request, Closure $next): Response
    {
        $incoming = (string) $request->headers->get(self::HEADER, '');
        $id = preg_match('/^[A-Za-z0-9._:\-]{8,128}$/', $incoming) ? $incoming : (string) Str::uuid();
        $request->headers->set(self::HEADER, $id);

        Context::add('request_id', $id);
        $traceId = $this->traceId($request);
        if ($traceId !== null) {
            Context::add('trace_id', $traceId);
        } else {
            Context::forget('trace_id');
        }

        $response = $next($request);
        $response->headers->set(self::HEADER, $id);

        return $response;
    }

    private function traceId(Request $request): ?string
    {
        // X-Cloud-Trace-Context: TRACE_ID/SPAN_ID;o=OPTIONS
        if (preg_match('#^([0-9a-f]{32})(/|$)#i', (string) $request->headers->get('X-Cloud-Trace-Context', ''), $match)) {
            return strtolower($match[1]);
        }
        // traceparent: VERSION-TRACE_ID-SPAN_ID-FLAGS (W3C Trace Context)
        if (preg_match('/^[0-9a-f]{2}-([0-9a-f]{32})-[0-9a-f]{16}-[0-9a-f]{2}$/i', (string) $request->headers->get('traceparent', ''), $match)) {
            return strtolower($match[1]);
        }

        return null;
    }
}
