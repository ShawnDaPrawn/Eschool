<?php

declare(strict_types=1);

namespace App\Services\Platform\SignIn;

/**
 * Who a verified ID token says the person is (Action Plan 5.9): the stable
 * subject (Google sub, Microsoft oid) with its tenant (Microsoft tid, empty
 * for Google), and the verified email address.
 */
final class ProviderIdentity
{
    public function __construct(
        public readonly string $provider,
        public readonly string $tenantId,
        public readonly string $subject,
        public readonly string $email,
    ) {}

    public function emailDomain(): string
    {
        return strtolower((string) substr(strrchr($this->email, '@') ?: '', 1));
    }
}
