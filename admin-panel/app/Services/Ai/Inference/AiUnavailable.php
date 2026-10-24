<?php

declare(strict_types=1);

namespace App\Services\Ai\Inference;

use RuntimeException;

class AiUnavailable extends RuntimeException
{
    public function __construct(public readonly string $reason)
    {
        parent::__construct($reason);
    }
}
