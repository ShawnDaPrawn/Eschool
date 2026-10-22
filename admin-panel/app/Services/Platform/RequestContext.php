<?php

declare(strict_types=1);

namespace App\Services\Platform;

use Illuminate\Support\Facades\Context;
use Illuminate\Support\Str;

/**
 * The request id that ties log lines, audit rows and queued work to the
 * request that caused them. Kept in Laravel's Context under "request_id",
 * which log entries include and queued jobs carry with them. The
 * AssignRequestId middleware sets it for every HTTP request; elsewhere
 * (console, schedule, a job queued without one) it is taken from a valid
 * X-Request-Id header, else Cloud Run's trace id, else a new UUID.
 */
class RequestContext
{
    public const KEY = 'request_id';

    public static function requestId(): string
    {
        $id = Context::get(self::KEY);
        if (is_string($id) && $id !== '') {
            return $id;
        }

        $id = self::fromHeaders() ?? (string) Str::uuid();
        Context::add(self::KEY, $id);

        return $id;
    }

    /** Whether the code runs for an HTTP request (as opposed to console, queue or schedule). */
    public static function inHttpRequest(): bool
    {
        return app()->bound('request') && request()->route() !== null;
    }

    private static function fromHeaders(): ?string
    {
        if (! self::inHttpRequest()) {
            return null;
        }
        $header = (string) request()->headers->get('X-Request-Id', '');
        if (preg_match('/^[A-Za-z0-9._:-]{8,128}$/', $header)) {
            return $header;
        }
        // X-Cloud-Trace-Context: TRACE_ID/SPAN_ID;o=1
        $trace = (string) request()->headers->get('X-Cloud-Trace-Context', '');
        if (preg_match('/^([a-f0-9]{32})\//i', $trace, $match)) {
            return strtolower($match[1]);
        }

        return null;
    }
}
