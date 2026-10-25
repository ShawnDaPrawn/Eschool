<?php

declare(strict_types=1);

namespace App\Services\Ai\Inference;

use App\Models\User;

interface InferenceFeaturePolicy
{
    public function feature(): string;

    /** Authorize before retrieving sources; resolve only approved, immutable, minimized references. */
    public function resolve(User $actor, Capability $capability, FeatureInferenceRequest $request): AuthorizedInferenceScope;

    /** Recheck the exact scope; never replace it with broader or newer private sources. */
    public function reauthorize(User $actor, AuthorizedInferenceScope $scope): void;
}
