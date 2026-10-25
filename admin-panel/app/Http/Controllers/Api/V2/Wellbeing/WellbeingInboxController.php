<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2\Wellbeing;

use App\Http\Controllers\Controller;
use App\Http\Requests\Wellbeing\WellbeingRequest;
use App\Services\Wellbeing\WellbeingNotices;
use Illuminate\Http\JsonResponse;

class WellbeingInboxController extends Controller
{
    public function index(WellbeingRequest $request): JsonResponse
    {
        return $this->response(app(WellbeingNotices::class)->inbox($request->user(), (int) $request->validated('page', 1), $request->validated('area')));
    }

    public function read(WellbeingRequest $request, int $notice): JsonResponse
    {
        return $this->response(app(WellbeingNotices::class)->markRead($request->user(), $notice));
    }

    private function response(array $data): JsonResponse
    {
        return response()->json(['error' => false, 'message' => 'Private notice request completed.', 'data' => $data, 'code' => 200], 200, ['Cache-Control' => 'no-store, private']);
    }
}
