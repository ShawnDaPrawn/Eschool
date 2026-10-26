<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2\Cards;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cards\CardEncodingRequest;
use App\Services\Cards\CardEncoding;
use Illuminate\Http\JsonResponse;

class CardEncodingController extends Controller
{
    public function download(CardEncodingRequest $request, string $plan): JsonResponse
    {
        return response()->json(['error' => false, 'message' => 'Encrypted personalization prepared.',
            'data' => app(CardEncoding::class)->download($request->bearerToken() ?? '', $plan), 'code' => 200]);
    }

    public function confirm(CardEncodingRequest $request, string $plan): JsonResponse
    {
        return response()->json(['error' => false, 'message' => 'Personalized chip verified.',
            'data' => app(CardEncoding::class)->confirm($request->bearerToken() ?? '', $plan, $request->validated()), 'code' => 200]);
    }
}
