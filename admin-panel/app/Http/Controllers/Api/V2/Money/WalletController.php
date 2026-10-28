<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2\Money;

use App\Http\Controllers\Controller;
use App\Http\Requests\Money\WalletRequest;
use App\Http\Resources\Money\WalletResource;
use App\Services\Money\Wallets;
use App\Services\ResponseService;
use Illuminate\Http\JsonResponse;

class WalletController extends Controller
{
    public function index(WalletRequest $request): JsonResponse
    {
        return ResponseService::successResponse('data_fetched_successfully', new WalletResource(app(Wallets::class)->accessible($request->user())));
    }

    public function statement(WalletRequest $request): JsonResponse
    {
        return ResponseService::successResponse('data_fetched_successfully', new WalletResource(app(Wallets::class)->statement($request->user(), (int) $request->route('wallet'), $request->integer('page', 1), $request->integer('per_page', 30))));
    }

    public function open(WalletRequest $request): JsonResponse
    {
        $wallet = app(Wallets::class)->open($request->user(), $request->validated());

        return ResponseService::successResponse('data_fetched_successfully', new WalletResource(app(Wallets::class)->statement($request->user(), (int) $wallet->id)));
    }

    public function limits(WalletRequest $request): JsonResponse
    {
        app(Wallets::class)->controls($request->user(), (int) $request->route('wallet'), $request->validated());

        return ResponseService::successResponse('data_fetched_successfully', new WalletResource(app(Wallets::class)->statement($request->user(), (int) $request->route('wallet'))));
    }
}
