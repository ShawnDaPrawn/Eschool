<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Platform\School;
use App\Services\Commerce\TicketCounters;
use App\Services\Commerce\VendorOperatorAccess;
use App\Services\Platform\Tenancy\CurrentSchool;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

final class RequireVendorCounter
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(app(VendorOperatorAccess::class)->ownsSession($request), 403);

        return DB::transaction(function () use ($request, $next): Response {
            School::whereKey(app(CurrentSchool::class)->id())->lockForUpdate()->firstOrFail();
            app(VendorOperatorAccess::class)->counter($request->user(), (int) $request->route('vendor'), ($request->routeIs('api.v2.vendor.collect') || $request->routeIs('api.v2.vendor.tickets.redeem')));
            if ($request->routeIs('api.v2.vendor.tickets.*')) {
                app(TicketCounters::class)->authority($request->user(), (int) $request->route('vendor'), $request->routeIs('api.v2.vendor.tickets.redeem'));
            }
            $response = $next($request);
            if ($request->routeIs('api.v2.vendor.collect') && $response->headers->get('Idempotent-Replayed') === 'true') {
                $content = $response->getContent();
                abort_unless(is_string($content), 500);
                $body = json_decode($content, true, flags: JSON_THROW_ON_ERROR);
                abort_unless(is_array($body) && is_array($body['data'] ?? null), 500);
                $body['data']['newly_collected'] = false;
                $response->setContent(json_encode($body, JSON_THROW_ON_ERROR));
                $response->headers->remove('Content-Length');
            }

            if ($request->routeIs('api.v2.vendor.tickets.redeem') && $response->headers->get('Idempotent-Replayed') === 'true') {
                $content = $response->getContent();
                abort_unless(is_string($content), 500);
                $body = json_decode($content, true, flags: JSON_THROW_ON_ERROR);
                abort_unless(is_array($body) && is_array($body['data'] ?? null), 500);
                $body['data']['replayed'] = true;
                $response->setContent(json_encode($body, JSON_THROW_ON_ERROR));
                $response->headers->remove('Content-Length');
            }

            return $response;
        }, 3);
    }
}
