<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2\Money;

use App\Http\Controllers\Controller;
use App\Http\Requests\Money\WalletDepositRequest;
use App\Http\Resources\Money\WalletResource;
use App\Services\Money\WalletDeposits;
use App\Services\ResponseService;
use Illuminate\Http\JsonResponse;

class WalletDepositController extends Controller
{
    public function index(WalletDepositRequest $request): JsonResponse
    {
        return ResponseService::successResponse('data_fetched_successfully', new WalletResource(app(WalletDeposits::class)->index($request->user(), $request->integer('page', 1), $request->integer('per_page', 30))));
    }

    public function claim(WalletDepositRequest $request): JsonResponse
    {
        $service = app(WalletDeposits::class);
        $row = $service->claim($request->user(), $request->validated());

        return ResponseService::successResponse('data_fetched_successfully', new WalletResource($service->receipt($row)));
    }

    public function cancel(WalletDepositRequest $request): JsonResponse
    {
        $service = app(WalletDeposits::class);
        $row = $service->finish($request->user(), (int) $request->route('claim'), 'cancelled', $request->validated());

        return ResponseService::successResponse('data_fetched_successfully', new WalletResource($service->receipt($row)));
    }
}
