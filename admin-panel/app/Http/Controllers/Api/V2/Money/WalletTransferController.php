<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2\Money;

use App\Http\Controllers\Controller;
use App\Http\Requests\Money\WalletTransferRequest;
use App\Http\Resources\Money\WalletResource;
use App\Services\Money\WalletOperations;
use App\Services\Money\WalletTransfers;
use App\Services\ResponseService;
use Illuminate\Http\JsonResponse;

class WalletTransferController extends Controller
{
    public function index(WalletTransferRequest $request): JsonResponse
    {
        return ResponseService::successResponse('data_fetched_successfully', new WalletResource(app(WalletTransfers::class)->index($request->user(), $request->integer('page', 1), $request->integer('per_page', 30))));
    }

    public function transfer(WalletTransferRequest $request): JsonResponse
    {
        $service = app(WalletTransfers::class);
        $row = $service->transfer($request->user(), $request->validated());

        return ResponseService::successResponse('data_fetched_successfully', new WalletResource($service->receipt($row)));
    }

    public function resolve(WalletTransferRequest $request): JsonResponse
    {
        return ResponseService::successResponse('data_fetched_successfully', new WalletResource(app(WalletOperations::class)->resolve($request->user(), (string) $request->route('operation'))));
    }
}
