<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2\Money;

use App\Http\Controllers\Controller;
use App\Http\Requests\Money\WalletWithdrawalRequest;
use App\Http\Resources\Money\WalletResource;
use App\Services\Money\WalletWithdrawals;
use App\Services\ResponseService;
use Illuminate\Http\JsonResponse;

class WalletWithdrawalController extends Controller
{
    public function index(WalletWithdrawalRequest $request): JsonResponse
    {
        return ResponseService::successResponse('data_fetched_successfully', new WalletResource(app(WalletWithdrawals::class)->index($request->user(), $request->integer('page', 1), $request->integer('per_page', 30))));
    }

    public function request(WalletWithdrawalRequest $request): JsonResponse
    {
        $service = app(WalletWithdrawals::class);
        $row = $service->request($request->user(), $request->validated());

        return ResponseService::successResponse('data_fetched_successfully', new WalletResource($service->receipt($row)));
    }

    public function cancel(WalletWithdrawalRequest $request): JsonResponse
    {
        $service = app(WalletWithdrawals::class);
        $row = $service->finish($request->user(), (int) $request->route('withdrawal'), 'cancelled', $request->validated());

        return ResponseService::successResponse('data_fetched_successfully', new WalletResource($service->receipt($row)));
    }
}
