<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2\Money;

use App\Http\Controllers\Controller;
use App\Http\Requests\Commerce\StorefrontRequest;
use App\Services\Commerce\VendorOrders;
use App\Services\Commerce\VendorStorefronts;
use App\Services\ResponseService;
use Illuminate\Http\JsonResponse;

class StorefrontController extends Controller
{
    public function index(StorefrontRequest $request): JsonResponse
    {
        return ResponseService::successResponse('data_fetched_successfully', app(VendorStorefronts::class)->index($request->user(), $request->integer('page', 1)));
    }

    public function catalogue(StorefrontRequest $request): JsonResponse
    {
        return ResponseService::successResponse('data_fetched_successfully', app(VendorStorefronts::class)->catalogue($request->user(), (int) $request->route('vendor'), $request->integer('page', 1)));
    }

    public function orders(StorefrontRequest $request): JsonResponse
    {
        return ResponseService::successResponse('data_fetched_successfully', app(VendorStorefronts::class)->orders($request->user(), $request->integer('page', 1)));
    }

    public function show(StorefrontRequest $request): JsonResponse
    {
        return ResponseService::successResponse('data_fetched_successfully', app(VendorStorefronts::class)->receipt($request->user(), (int) $request->route('order')));
    }

    public function quote(StorefrontRequest $request): JsonResponse
    {
        return ResponseService::successResponse('data_fetched_successfully', app(VendorOrders::class)->quote($request->user(), $request->validated()));
    }

    public function purchase(StorefrontRequest $request): JsonResponse
    {
        $order = app(VendorOrders::class)->purchase($request->user(), $request->validated());

        return ResponseService::successResponse('data_fetched_successfully', app(VendorStorefronts::class)->receipt($request->user(), (int) $order->id));
    }

    public function cancel(StorefrontRequest $request): JsonResponse
    {
        $order = app(VendorOrders::class)->cancel($request->user(), (int) $request->route('order'), $request->validated());

        return ResponseService::successResponse('data_fetched_successfully', app(VendorStorefronts::class)->receipt($request->user(), (int) $order->id));
    }
}
