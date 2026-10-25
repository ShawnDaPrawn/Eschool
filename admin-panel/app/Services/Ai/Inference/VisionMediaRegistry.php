<?php

declare(strict_types=1);

namespace App\Services\Ai\Inference;

class VisionMediaRegistry
{
    public function resolver(string $feature): VisionMediaResolver
    {
        $resolvers = config('ai.inference.vision_resolvers', []);
        $class = is_array($resolvers) ? ($resolvers[$feature] ?? null) : null;
        if (! is_string($class) || ! is_a($class, VisionMediaResolver::class, true)) {
            throw new AiUnavailable('media_policy_required');
        }

        return app($class);
    }
}
