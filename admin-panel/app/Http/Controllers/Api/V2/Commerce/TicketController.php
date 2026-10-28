<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2\Commerce;

use App\Http\Controllers\Controller;
use App\Http\Requests\Commerce\TicketRequest;
use App\Services\Commerce\TicketHolders;
use App\Services\ResponseService;
use Illuminate\Http\JsonResponse;

class TicketController extends Controller
{
    public function handle(TicketRequest $request): JsonResponse
    {
        $service = app(TicketHolders::class);
        $actor = $request->user();
        $id = (int) $request->route('ticket');
        $data = match (true) {
            $request->routeIs('api.v2.commerce.tickets.index') => $service->listing($actor, $request->integer('page', 1)),
            $request->routeIs('api.v2.commerce.tickets.receive-code') => $service->receiveCode($actor),
            $request->routeIs('api.v2.commerce.tickets.transfer-preview') => $service->previewTransfer($actor, $id, $request->string('receive_code')->toString()),
            $request->routeIs('api.v2.commerce.tickets.transfer') => $service->transfer($actor, $id, $request->validated()),
            default => $service->receipt($actor, $id),
        };

        return ResponseService::successResponse('data_fetched_successfully', $data);
    }
}
