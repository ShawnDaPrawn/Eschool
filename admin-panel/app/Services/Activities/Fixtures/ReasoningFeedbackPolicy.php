<?php

declare(strict_types=1);

namespace App\Services\Activities\Fixtures;

use App\Models\User;
use App\Services\Ai\Inference\AuthorizedInferenceScope;
use App\Services\Ai\Inference\Capability;
use App\Services\Ai\Inference\FeatureInferenceRequest;
use App\Services\Ai\Inference\InferenceAudience;
use App\Services\Ai\Inference\InferenceFeaturePolicy;
use App\Services\Ai\Inference\InferenceRequest;
use App\Services\Platform\Tenancy\CurrentSchool;

/**
 * The gateway policy for AI feedback on reasoning (Action Plan 5.3). The only
 * input is one pending review in this school, asked for by the adjudicator
 * calling, for a competition they still judge: its already-redacted text.
 * No learner is in the scope, so no learner id reaches the receipt, and the
 * audience is staff: the answer goes to the adjudicator, never a family.
 */
class ReasoningFeedbackPolicy implements InferenceFeaturePolicy
{
    public function feature(): string
    {
        return ReasoningReviews::FEATURE;
    }

    public function resolve(User $actor, Capability $capability, FeatureInferenceRequest $request): AuthorizedInferenceScope
    {
        abort_unless($capability === Capability::Complete && array_keys($request->payload) === ['review_id'] && is_int($request->payload['review_id']), 422);
        $review = app(ReasoningReviews::class)->pending($actor, $request->payload['review_id']);
        abort_unless(hash_equals((string) $review->operation_id, $request->operationId), 403);

        return new AuthorizedInferenceScope(app(CurrentSchool::class)->id(), (int) $actor->id,
            new InferenceRequest($this->feature(), (string) $review->operation_id, ReasoningReviews::PROMPT, (string) $review->input, ['academic_reasoning_review:'.$review->id]),
            audience: InferenceAudience::Staff);
    }

    public function reauthorize(User $actor, AuthorizedInferenceScope $scope): void
    {
        $source = $scope->inference->sources[0] ?? '';
        abort_unless(count($scope->inference->sources) === 1 && preg_match('/^academic_reasoning_review:([1-9][0-9]*)$/D', $source, $match) === 1, 403);
        $review = app(ReasoningReviews::class)->pending($actor, (int) $match[1]);
        abort_unless($scope->schoolId === app(CurrentSchool::class)->id() && $scope->actorId === (int) $actor->id && $scope->learnerId === null
            && $scope->audience === InferenceAudience::Staff && $scope->inference->operationId === (string) $review->operation_id
            && $scope->inference->input === $review->input, 403);
    }
}
