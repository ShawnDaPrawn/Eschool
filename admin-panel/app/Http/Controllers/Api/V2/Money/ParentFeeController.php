<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2\Money;

use App\Http\Controllers\Controller;
use App\Http\Requests\Money\ParentFeeReadRequest;
use App\Http\Resources\Money\ParentFeeResource;
use App\Services\Money\ParentFeeStatements;
use App\Services\ResponseService;
use Illuminate\Http\JsonResponse;

class ParentFeeController extends Controller
{
    public function index(ParentFeeReadRequest $request): JsonResponse
    {
        $data = app(ParentFeeStatements::class)->index($request->user(), $request->integer('page', 1), $request->integer('per_page', 20));

        return ResponseService::successResponse('data_fetched_successfully', new ParentFeeResource($data));
    }

    public function show(ParentFeeReadRequest $request): JsonResponse
    {
        $data = app(ParentFeeStatements::class)->show($request->user(), (int) $request->route('account'), $request->integer('charges_page', 1), $request->integer('receipts_page', 1), $request->integer('per_page', 20));

        return ResponseService::successResponse('data_fetched_successfully', new ParentFeeResource($data));
    }
}
