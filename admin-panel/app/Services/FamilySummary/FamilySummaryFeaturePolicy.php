<?php

declare(strict_types=1);

namespace App\Services\FamilySummary;

use App\Models\Family\FamilyDailySummaryPart;
use App\Models\User;
use App\Services\Ai\Inference\AuthorizedInferenceScope;
use App\Services\Ai\Inference\Capability;
use App\Services\Ai\Inference\FeatureInferenceRequest;
use App\Services\Ai\Inference\InferenceAudience;
use App\Services\Ai\Inference\InferenceFeaturePolicy;
use App\Services\Ai\Inference\InferenceRequest;
use App\Services\Platform\Tenancy\CurrentSchool;

/**
 * The gateway policy for the daily family summary (Action Plan 5.11). The
 * only input is one stored part of this school, owned by the guardian asking,
 * whose children are still theirs; the model receives that part's minimised
 * facts (FamilySummaryWriter::modelInput) and nothing else. One part, one
 * school: no other school's facts ever reach this school's AI receipt.
 */
class FamilySummaryFeaturePolicy implements InferenceFeaturePolicy
{
    public function feature(): string
    {
        return FamilySummaryWriter::FEATURE;
    }

    public function resolve(User $actor, Capability $capability, FeatureInferenceRequest $request): AuthorizedInferenceScope
    {
        abort_unless($capability === Capability::Complete && array_keys($request->payload) === ['part_id'] && is_int($request->payload['part_id']), 422);
        $part = $this->part($actor, $request->payload['part_id']);
        abort_unless(hash_equals((string) $part->operation_id, $request->operationId), 403);

        return new AuthorizedInferenceScope(app(CurrentSchool::class)->id(), (int) $actor->id,
            new InferenceRequest($this->feature(), (string) $part->operation_id, FamilySummaryWriter::PROMPT, FamilySummaryWriter::modelInput($part->facts), ['family_summary_part:'.$part->id]),
            audience: InferenceAudience::Family);
    }

    public function reauthorize(User $actor, AuthorizedInferenceScope $scope): void
    {
        $source = $scope->inference->sources[0] ?? '';
        abort_unless(count($scope->inference->sources) === 1 && preg_match('/^family_summary_part:([1-9][0-9]*)$/D', $source, $match) === 1, 403);
        $part = $this->part($actor, (int) $match[1]);
        abort_unless($scope->schoolId === app(CurrentSchool::class)->id() && $scope->actorId === (int) $actor->id && $scope->learnerId === null
            && $scope->audience === InferenceAudience::Family && $scope->inference->operationId === (string) $part->operation_id
            && $scope->inference->input === FamilySummaryWriter::modelInput($part->facts), 403);
    }

    private function part(User $actor, int $id): FamilyDailySummaryPart
    {
        $authority = app(FamilySummaryAuthority::class);
        $fresh = $authority->guardian($actor);
        $part = FamilyDailySummaryPart::query()->where('user_id', $fresh->id)->where('expires_at', '>', now())->findOrFail($id);
        foreach ((array) $part->learner_ids as $learnerId) {
            $authority->child($fresh, (int) $learnerId);
        }

        return $part;
    }
}
