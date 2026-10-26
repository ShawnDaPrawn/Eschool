<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2\Live;

use App\Http\Controllers\Controller;
use App\Http\Requests\Live\LiveRequest;
use App\Services\Live\LiveSessions;
use Illuminate\Http\JsonResponse;

class LiveController extends Controller
{
    public function session(LiveRequest $request): JsonResponse
    {
        return $this->result(app(LiveSessions::class)->open($request, $request->user()));
    }

    public function authorizeChannel(LiveRequest $request): JsonResponse
    {
        return $this->result(app(LiveSessions::class)->authorize($request, $request->user(), $request->validated('socket_id'), $request->validated('channel_name')));
    }

    public function presence(LiveRequest $request): JsonResponse
    {
        return $this->result(app(LiveSessions::class)->presence($request, $request->user(), $request->validated('lease_id'), (int) $request->route('group'), $request->isMethod('DELETE')));
    }

    private function result(array $data): JsonResponse
    {
        return response()->json(['error' => false, 'message' => 'Live session updated.', 'data' => $data, 'code' => 200], 200, ['Cache-Control' => 'no-store, private']);
    }
}
