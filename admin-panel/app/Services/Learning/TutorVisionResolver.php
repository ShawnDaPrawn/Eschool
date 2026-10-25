<?php

declare(strict_types=1);

namespace App\Services\Learning;

use App\Models\Learning\TutorMessage;
use App\Models\User;
use App\Services\Ai\Inference\AuthorizedInferenceScope;
use App\Services\Ai\Inference\ResolvedVisionInput;
use App\Services\Ai\Inference\VisionMediaResolver;

class TutorVisionResolver implements VisionMediaResolver
{
    public function resolve(User $actor, AuthorizedInferenceScope $scope): ResolvedVisionInput
    {
        $meta = TutorMessage::where('operation_id', $scope->inference->operationId)->select('id')->firstOrFail();
        $message = app(TutorFeaturePolicy::class)->message($actor, $meta->id);
        $manifest = $scope->inference->input;
        abort_unless(is_array($manifest), 422);
        $photos = app(TutorPhotos::class);
        abort_unless($photos->references($actor, $message) === $manifest['media'], 409);
        $images = array_map(fn ($ref) => $photos->image($actor, $message, $ref), $manifest['media']);

        return new ResolvedVisionInput($manifest['text'], $images);
    }
}
