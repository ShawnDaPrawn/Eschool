<?php

declare(strict_types=1);

namespace App\Services\Platform\Subscriptions\Gateways;

/**
 * A verified gateway notice in Navigate Ed's own terms. `reference` is the
 * subscription's reference (NES-000001), which the checkout carries; amounts
 * are integer cents. Anything else the gateway sent is not kept.
 */
final class GatewayEvent
{
    public const PAYMENT_SUCCEEDED = 'payment.succeeded';

    public const SUBSCRIPTION_CANCELLED = 'subscription.cancelled';

    public function __construct(
        public readonly string $id,
        public readonly string $type,
        public readonly string $reference,
        public readonly ?string $chargeId = null,
        public readonly ?int $amountCents = null,
        public readonly ?string $currency = null,
        public readonly ?string $paidOn = null,
        public readonly ?string $providerRef = null,
    ) {}
}
