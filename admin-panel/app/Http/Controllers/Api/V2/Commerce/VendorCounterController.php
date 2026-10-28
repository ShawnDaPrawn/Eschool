<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2\Commerce;

use App\Http\Controllers\Controller;
use App\Http\Requests\Commerce\VendorCounterRequest;
use App\Services\Commerce\VendorCounterOrders;
use App\Services\Commerce\VendorSettlements;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class VendorCounterController extends Controller
{
    public function handle(VendorCounterRequest $request): JsonResponse
    {
        $data = DB::transaction(function () use ($request): array {
            $service = app(VendorCounterOrders::class);
            $actor = $request->user();
            $vendor = (int) $request->route('vendor');
            if ($request->routeIs('api.v2.vendor.collect')) {
                return $service->collect($actor, $vendor, $request->validated());
            }

            return match (true) {
                $request->routeIs('api.v2.vendor.settlements') => app(VendorSettlements::class)->listingForOperator($actor, $vendor, $request->validated()),
                $request->routeIs('api.v2.vendor.settlement') => app(VendorSettlements::class)->receiptForOperator($actor, $vendor, (int) $request->route('settlement')),
                $request->routeIs('api.v2.vendor.lookup') => $service->lookup($actor, $vendor, $request->validated()),
                $request->routeIs('api.v2.vendor.receipt') => $service->receipt($actor, $vendor, (int) $request->route('order')),
                $request->routeIs('api.v2.vendor.resolve') => $service->resolve($actor, $vendor, (string) $request->route('operation')),
                default => $service->listing($actor, $vendor, $request->validated()),
            };
        });

        return response()->json(['error' => false, 'message' => '', 'data' => $data, 'code' => 200])->header('Cache-Control', 'private, no-store');
    }
}
