<?php

declare(strict_types=1);

namespace App\Services\Assistance;

use App\Models\User;
use App\Services\Ai\Inference\AuthorizedInferenceScope;
use App\Services\Ai\Inference\InferenceAudience;
use App\Services\Ai\Inference\StaffInferenceActorPolicy;

class TranslationFeaturePolicy extends AssistantFeaturePolicy implements StaffInferenceActorPolicy
{
    public function feature(): string
    {
        return 'message_translation';
    }

    public function authorizeStaffActor(User $actor, AuthorizedInferenceScope $scope): void
    {
        abort_unless($scope->audience === InferenceAudience::Staff, 403);
        $this->reauthorize($actor, $scope);
        $turn = app(AssistanceInference::class)->authorize($actor, $scope->inference->operationId);
        abort_unless($turn->office, 403);
        app(AssistanceAccess::class)->office($actor);
    }
}
