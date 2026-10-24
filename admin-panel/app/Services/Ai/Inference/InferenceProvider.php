<?php

declare(strict_types=1);

namespace App\Services\Ai\Inference;

/** Implementations must enforce timeout, output limits and request UUID upstream. */
interface InferenceProvider
{
    public function infer(ProviderRequest $request): ProviderResponse;
}
