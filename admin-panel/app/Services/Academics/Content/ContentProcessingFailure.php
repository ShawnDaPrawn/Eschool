<?php

declare(strict_types=1);

namespace App\Services\Academics\Content;

use RuntimeException;

class ContentProcessingFailure extends RuntimeException
{
    public function __construct(public readonly string $failureCode, public readonly bool $retryable = true, public readonly bool $chargeReservation = true)
    {
        parent::__construct($failureCode);
    }
}
