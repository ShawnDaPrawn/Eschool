<?php

declare(strict_types=1);

namespace App\Services\Learning;

use App\Models\Learning\TeachingDraft;
use App\Models\User;
use App\Services\Ai\Inference\AuthorizedInferenceScope;
use App\Services\Ai\Inference\Capability;
use App\Services\Ai\Inference\FeatureInferenceRequest;
use App\Services\Ai\Inference\InferenceAudience;
use App\Services\Ai\Inference\InferenceFeaturePolicy;
use App\Services\Ai\Inference\InferenceRequest;
use App\Services\Platform\Tenancy\CurrentSchool;

class TeachingDraftFeaturePolicy implements InferenceFeaturePolicy
{
    public function feature(): string
    {
        return 'teaching_assistant';
    }

    public function draft(User $actor, string $id): TeachingDraft
    {
        $row = app(TeachingDraftAccess::class)->draft($actor, $id, true);
        abort_unless(in_array($row->state, ['pending', 'ready'], true), 409, 'This teaching draft cannot be generated.');
        app(TeachingDraftContext::class)->reauthorize($actor, $row);
        if ($row->kind === 'script_marks') {
            app(TeachingDraftPhotos::class)->references($actor, $row);
        }

        return $row;
    }

    public function resolve(User $actor, Capability $capability, FeatureInferenceRequest $request): AuthorizedInferenceScope
    {
        abort_unless(array_keys($request->payload) === ['draft_id'] && is_string($request->payload['draft_id']), 422);
        $draft = $this->draft($actor, $request->payload['draft_id']);
        abort_unless($request->operationId === $draft->operation_id, 409);
        $vision = $draft->kind === 'script_marks';
        abort_unless($capability === ($vision ? Capability::Vision : Capability::Complete), 422);
        $group = app(TeachingDraftAccess::class)->group($actor, (int) $draft->teaching_group_id, true);
        $input = $draft->context_text;
        $references = ['content:'.$draft->content_version_id.':'.$draft->source_manifest['source']['sha256'], 'prompt:'.$draft->source_manifest['prompt_sha256']];
        foreach ($draft->source_manifest['notes'] as $note) {
            $references[] = 'teaching-note:'.$note['id'].':'.$note['revision'].':'.$note['sha256'];
        }
        if ($vision) {
            $photos = app(TeachingDraftPhotos::class)->references($actor, $draft);
            $input = ['text' => $input, 'media' => $photos];
            foreach ($photos as $photo) {
                $references[] = 'teaching-photo:'.$photo['id'].':'.$photo['sha256'];
            }
        }

        return new AuthorizedInferenceScope(app(CurrentSchool::class)->id(), (int) $actor->id,
            new InferenceRequest('teaching_assistant', $request->operationId, 'teaching-assistant', $input, $references),
            $draft->learner_id === null ? null : (int) $draft->learner_id, (int) $group->subject_id, audience: InferenceAudience::Staff);
    }

    public function reauthorize(User $actor, AuthorizedInferenceScope $scope): void
    {
        $meta = TeachingDraft::where('operation_id', $scope->inference->operationId)->select('id')->firstOrFail();
        $draft = $this->draft($actor, $meta->id);
        $group = app(TeachingDraftAccess::class)->group($actor, (int) $draft->teaching_group_id, true);
        abort_unless(app(CurrentSchool::class)->id() === $scope->schoolId && (int) $actor->id === $scope->actorId
            && (int) $group->subject_id === $scope->subjectId && ($draft->learner_id === null ? null : (int) $draft->learner_id) === $scope->learnerId, 403);
    }
}
