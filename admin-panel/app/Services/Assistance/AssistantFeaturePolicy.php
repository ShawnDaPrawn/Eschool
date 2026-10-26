<?php

declare(strict_types=1);

namespace App\Services\Assistance;

use App\Models\User;
use App\Services\Ai\Inference\AuthorizedInferenceScope;
use App\Services\Ai\Inference\Capability;
use App\Services\Ai\Inference\FeatureInferenceRequest;
use App\Services\Ai\Inference\InferenceAudience;
use App\Services\Ai\Inference\InferenceFeaturePolicy;
use App\Services\Ai\Inference\InferenceRequest;
use App\Services\Platform\Tenancy\CurrentSchool;

class AssistantFeaturePolicy implements InferenceFeaturePolicy
{
    public function feature(): string
    {
        return 'school_assistant';
    }

    public function resolve(User $actor, Capability $capability, FeatureInferenceRequest $request): AuthorizedInferenceScope
    {
        abort_unless($capability === Capability::Complete && array_keys($request->payload) === ['turn_id'] && is_string($request->payload['turn_id']), 422);
        $service = app(AssistanceInference::class);
        $turn = $service->authorize($actor, $request->payload['turn_id']);
        abort_unless($service->feature($turn) === $this->feature() && $request->operationId === $turn->id && $turn->approved_at !== null, 403);
        $references = array_map(fn ($ref) => $ref['kind'].':'.$ref['sha256'], $turn->source_manifest);

        return new AuthorizedInferenceScope(app(CurrentSchool::class)->id(), (int) $actor->id,
            new InferenceRequest($this->feature(), $turn->id, str_replace('_', '-', $this->feature()), $turn->approved_input, $references),
            (int) $turn->learner_id, audience: $turn->office ? InferenceAudience::Staff : InferenceAudience::Family);
    }

    public function reauthorize(User $actor, AuthorizedInferenceScope $scope): void
    {
        $turn = app(AssistanceInference::class)->authorize($actor, $scope->inference->operationId);
        abort_unless(app(AssistanceInference::class)->feature($turn) === $this->feature() && (int) $turn->user_id === $scope->actorId
            && (int) $turn->learner_id === $scope->learnerId && app(CurrentSchool::class)->id() === $scope->schoolId
            && $turn->approved_at !== null && $turn->approved_input === $scope->inference->input
            && $scope->audience === ($turn->office ? InferenceAudience::Staff : InferenceAudience::Family), 403);
    }
}
