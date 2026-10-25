<?php

declare(strict_types=1);

namespace App\Services\Ai\Inference;

/** Implementations must enforce timeout, output limits and request UUID upstream. */
interface InferenceProvider
{
    /** Local validation and qualification only: must never dispatch HTTP. */
    public function preflight(ProviderRequest $request): void;

    public function infer(ProviderRequest $request): ProviderResponse;
}
