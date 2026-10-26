<?php

declare(strict_types=1);

namespace App\Services\Learning;

use App\Models\Learning\ExamAnswer;
use App\Models\User;
use App\Services\Ai\Inference\AuthorizedInferenceScope;
use App\Services\Ai\Inference\ResolvedVisionInput;
use App\Services\Ai\Inference\VisionMediaResolver;

class ExamAnswerVisionResolver implements VisionMediaResolver
{
    public function resolve(User $actor, AuthorizedInferenceScope $scope): ResolvedVisionInput
    {
        app(ExamAnswerFeaturePolicy::class)->reauthorize($actor, $scope);
        $answer = ExamAnswer::where('operation_id', $scope->inference->operationId)->where('user_id', $actor->id)->firstOrFail();
        $image = app(ExamAnswerAccess::class)->image($actor, $answer->id);
        app(ExamAnswerFeaturePolicy::class)->reauthorize($actor, $scope);

        return new ResolvedVisionInput($scope->inference->input['text'], [$image]);
    }
}
