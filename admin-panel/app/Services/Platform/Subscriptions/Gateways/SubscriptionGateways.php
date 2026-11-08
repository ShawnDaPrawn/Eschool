<?php

declare(strict_types=1);

namespace App\Services\Platform\Subscriptions\Gateways;

/**
 * Finds the adapter for a webhook's key: only the gateway named in
 * config('subscriptions.gateway'), and only when its class is listed under
 * config('subscriptions.gateways') and implements SubscriptionGateway.
 * Anything else is "not configured".
 */
final class SubscriptionGateways
{
    public function resolve(string $key): ?SubscriptionGateway
    {
        $chosen = config('subscriptions.gateway');
        $classes = config('subscriptions.gateways', []);
        if (! is_string($chosen) || $chosen === '' || ! hash_equals($chosen, $key) || ! is_array($classes) || ! is_string($classes[$key] ?? null)) {
            return null;
        }
        $adapter = app($classes[$key]);

        return $adapter instanceof SubscriptionGateway && $adapter->key() === $key ? $adapter : null;
    }
}
