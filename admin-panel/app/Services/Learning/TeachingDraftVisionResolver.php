<?php

declare(strict_types=1);

namespace App\Services\Learning;

use App\Models\Learning\TeachingDraft;
use App\Models\User;
use App\Services\Ai\Inference\AuthorizedInferenceScope;
use App\Services\Ai\Inference\ResolvedVisionInput;
use App\Services\Ai\Inference\VisionMediaResolver;

class TeachingDraftVisionResolver implements VisionMediaResolver
{
    public function resolve(User $actor, AuthorizedInferenceScope $scope): ResolvedVisionInput
    {
        $meta = TeachingDraft::where('operation_id', $scope->inference->operationId)->select('id')->firstOrFail();
        $draft = app(TeachingDraftFeaturePolicy::class)->draft($actor, $meta->id);
        $input = $scope->inference->input;
        $photos = app(TeachingDraftPhotos::class);
        abort_unless(is_array($input) && $photos->references($actor, $draft) === $input['media'], 409);
        $images = array_map(fn ($ref) => $photos->image($actor, $draft, $ref), $input['media']);

        return new ResolvedVisionInput($input['text'], $images);
    }
}
