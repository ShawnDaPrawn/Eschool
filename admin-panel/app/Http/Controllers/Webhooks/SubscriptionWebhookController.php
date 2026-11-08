<?php

declare(strict_types=1);

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Services\Platform\Subscriptions\SubscriptionWebhooks;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * A payment gateway's notices about learner subscriptions (Action Plan 3.20).
 * Authenticated by the gateway adapter's signature check, so it sits outside
 * CSRF like the other webhooks. No gateway is configured yet: until one is,
 * every notice is answered 404.
 */
class SubscriptionWebhookController extends Controller
{
    public function handle(Request $request, string $gateway, SubscriptionWebhooks $webhooks): JsonResponse
    {
        $headers = [];
        foreach ($request->headers->all() as $name => $values) {
            $headers[strtolower((string) $name)] = (string) ($values[0] ?? '');
        }
        // The raw body, not $request->input(): the global middleware trims strings, which would break the signature.
        [$code, $message] = $webhooks->handle($gateway, $request->getContent(), $headers);

        return response()->json(['error' => $code >= 400, 'message' => $message], $code);
    }
}
