<?php

declare(strict_types=1);

namespace App\Services\Ai\Inference;

class FeaturePolicyRegistry
{
    public function policy(string $feature): InferenceFeaturePolicy
    {
        $policies = config('ai.inference.feature_policies', []);
        $class = is_array($policies) ? ($policies[$feature] ?? null) : null;
        if (! is_string($class) || ! is_a($class, InferenceFeaturePolicy::class, true)) {
            throw new AiUnavailable('feature_policy_unavailable');
        }
        $policy = app($class);
        if (! $policy instanceof InferenceFeaturePolicy || $policy->feature() !== $feature) {
            throw new AiUnavailable('feature_policy_unavailable');
        }

        return $policy;
    }
}
