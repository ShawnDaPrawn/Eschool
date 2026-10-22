<?php

declare(strict_types=1);

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Services\Platform\Notifications\SesNotifications;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Mail provider feedback (bounces, complaints, deliveries). Authenticated by
 * the provider's signature, so it sits outside CSRF like the payment webhooks.
 */
class MailWebhookController extends Controller
{
    public function ses(Request $request, SesNotifications $notifications): JsonResponse
    {
        [$status, $message] = $notifications->handle($request->getContent());

        return response()->json(['error' => $status >= 400, 'message' => $message], $status);
    }
}
