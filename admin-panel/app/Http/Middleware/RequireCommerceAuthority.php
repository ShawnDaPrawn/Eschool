<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\Commerce\VendorOrders;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireCommerceAuthority
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user() !== null, 401);
        abort_if($request->query->count() > 0, 422);
        if ($request->routeIs('api.v2.commerce.purchase')) {
            app(VendorOrders::class)->authorizePurchase($request->user(), $request->all());
        } else {
            app(VendorOrders::class)->authorizeOrder($request->user(), (int) $request->route('order'), true);
        }

        return $next($request);
    }
}
