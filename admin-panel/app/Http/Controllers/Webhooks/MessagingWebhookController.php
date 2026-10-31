<?php

declare(strict_types=1);

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Services\Platform\Notifications\TwilioStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * SMS and WhatsApp delivery status from Twilio (Action Plan 2.7).
 * Authenticated by X-Twilio-Signature, so it sits outside CSRF like the
 * other webhooks.
 */
class MessagingWebhookController extends Controller
{
    public function twilio(Request $request, TwilioStatus $status): JsonResponse
    {
        // The raw body, not $request->input(): the global middleware trims
        // strings and turns empty ones into null, which would break the signature.
        [$code, $message] = $status->handle(TwilioStatus::parseForm($request->getContent()), (string) $request->header('X-Twilio-Signature', ''));

        return response()->json(['error' => $code >= 400, 'message' => $message], $code);
    }
}
