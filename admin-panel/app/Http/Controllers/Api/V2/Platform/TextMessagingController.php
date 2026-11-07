<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V2\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\TextMessagingRequest;
use App\Models\User;
use App\Services\Platform\Notifications\TextNumbers;
use App\Services\Platform\Notifications\UrgentMessaging;
use App\Services\ResponseService;
use Illuminate\Http\JsonResponse;

/**
 * Your SMS and WhatsApp at the current school (Action Plan 2.7): whether your
 * number is verified, a code to verify it, and SMS, WhatsApp, both or neither
 * for each notice the school texts you.
 */
class TextMessagingController extends Controller
{
    public function show(TextMessagingRequest $request, UrgentMessaging $messaging, TextNumbers $numbers): JsonResponse
    {
        return ResponseService::successResponse('data_fetch_successfully', $this->state($request->user(), $messaging, $numbers));
    }

    public function update(TextMessagingRequest $request, UrgentMessaging $messaging, TextNumbers $numbers): JsonResponse
    {
        $messaging->choose($request->user(), (string) $request->validated('type'), $request->boolean('sms'), $request->boolean('whatsapp'));

        return ResponseService::successResponse('data_update_successfully', $this->state($request->user(), $messaging, $numbers));
    }

    public function code(TextMessagingRequest $request, UrgentMessaging $messaging, TextNumbers $numbers): JsonResponse
    {
        $numbers->requestCode($request->user());

        return ResponseService::successResponse('success', $this->state($request->user(), $messaging, $numbers));
    }

    public function confirm(TextMessagingRequest $request, UrgentMessaging $messaging, TextNumbers $numbers): JsonResponse
    {
        $numbers->confirm($request->user(), (string) $request->validated('code'));

        return ResponseService::successResponse('success', $this->state($request->user(), $messaging, $numbers));
    }

    /** @return array{number: array<string, mixed>, notices: list<array<string, mixed>>} */
    private function state(User $user, UrgentMessaging $messaging, TextNumbers $numbers): array
    {
        return ['number' => $numbers->status($user), 'notices' => $messaging->choices($user)];
    }
}
