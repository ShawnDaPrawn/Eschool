<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2\Cards;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cards\CardOfflineRequest;
use App\Services\Cards\CardOfflineSnapshots;
use Illuminate\Http\JsonResponse;

class CardOfflineController extends Controller
{
    public function snapshot(CardOfflineRequest $request): JsonResponse
    {
        return response()->json(['error' => false, 'message' => 'Offline identity snapshot prepared.',
            'data' => app(CardOfflineSnapshots::class)->page($request->bearerToken() ?? '', $request->validated('after_card_id'), $request->validated('request_id')), 'code' => 200]);
    }

    public function reconcile(CardOfflineRequest $request): JsonResponse
    {
        return response()->json(['error' => false, 'message' => 'Offline identity history recorded.',
            'data' => app(CardOfflineSnapshots::class)->reconcile($request->bearerToken() ?? '', $request->validated()), 'code' => 200]);
    }
}
