<?php

declare(strict_types=1);

namespace App\Services\Platform\Subscriptions\Gateways;

/**
 * The provider-neutral seam for a card or debit-order gateway (Action Plan
 * 3.20). The gateway is not chosen yet, so no adapter ships; one is added
 * under config('subscriptions.gateways') by its key and switched on with
 * config('subscriptions.gateway').
 *
 * A gateway's notices arrive at POST /webhook/subscriptions/{key}, like the
 * other signed webhooks: the adapter checks the signature on the exact raw
 * body (never the trimmed request input), and only then reads it into a
 * GatewayEvent. Everything after that (the receipt registry, the price
 * check, the period, the audit trail) is the same as for an EFT HQ records.
 * The adapter never writes anything itself.
 */
interface SubscriptionGateway
{
    /** The key in the webhook URL and in config, lower case: [a-z][a-z0-9_]{1,31}. */
    public function key(): string;

    /**
     * The notice, when its signature verifies; null when it does not.
     *
     * @param  array<string, string>  $headers  lower-case names, first value each
     */
    public function verify(string $rawBody, array $headers): ?GatewayEvent;
}
