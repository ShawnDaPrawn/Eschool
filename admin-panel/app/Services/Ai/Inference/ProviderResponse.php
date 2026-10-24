<?php

declare(strict_types=1);

namespace App\Services\Ai\Inference;

final readonly class ProviderResponse
{
    public function __construct(public string|array $output, public int $tokens) {}
}
