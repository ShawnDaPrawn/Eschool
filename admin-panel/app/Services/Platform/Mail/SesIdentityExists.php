<?php

declare(strict_types=1);

namespace App\Services\Platform\Mail;

use RuntimeException;
use Throwable;

/** SES already has an email identity of that name (SesIdentities::create). */
class SesIdentityExists extends RuntimeException
{
    public function __construct(public readonly string $domain, ?Throwable $previous = null)
    {
        parent::__construct('SES already has this email identity.', 0, $previous);
    }
}
