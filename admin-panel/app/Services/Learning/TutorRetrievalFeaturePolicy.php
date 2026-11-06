<?php

declare(strict_types=1);

namespace App\Services\Learning;

use App\Models\Learning\TutorSession;
use App\Models\User;
use App\Services\Ai\Inference\AuthorizedInferenceScope;
use App\Services\Ai\Inference\Capability;
use App\Services\Ai\Inference\FeatureInferenceRequest;
use App\Services\Ai\Inference\InferenceFeaturePolicy;
use App\Services\Ai\Inference\InferenceRequest;
use App\Services\Platform\Tenancy\CurrentSchool;

/**
 * Embeddings for choosing a content section (Action Plan 3.10). The payload
 * names only the learner's own open session and either the learner's
 * minimized question for one turn, or one section of a content version the
 * session already selected. The server rebuilds the input and the operation
 * identity, so a caller cannot embed other text or replay another learner's
 * vectors.
 */
class TutorRetrievalFeaturePolicy implements InferenceFeaturePolicy
{
    public function feature(): string
    {
        return 'tutor_retrieval';
    }

    public function resolve(User $actor, Capability $capability, FeatureInferenceRequest $request): AuthorizedInferenceScope
    {
        abort_unless($capability === Capability::Embed, 422);
        [$session, $input, $references, $operation] = $this->authorize($actor, $request->payload);
        abort_unless(hash_equals($operation, $request->operationId), 409);

        return new AuthorizedInferenceScope(app(CurrentSchool::class)->id(), (int) $actor->id,
            new InferenceRequest('tutor_retrieval', $request->operationId, 'tutor-retrieval', $input, $references), (int) $session->learner_id, (int) $session->subject_id);
    }

    public function reauthorize(User $actor, AuthorizedInferenceScope $scope): void
    {
        $session = app(TutorAccess::class)->session($actor, $this->sessionId($scope), true);
        abort_unless((int) $session->learner_id === $scope->learnerId && (int) $session->subject_id === $scope->subjectId
            && (int) $actor->id === $scope->actorId && app(CurrentSchool::class)->id() === $scope->schoolId, 403);
        foreach ($scope->inference->sources as $source) {
            if (str_starts_with($source, 'content-section:')) {
                [, $version, $index, $sha] = explode(':', $source);
                $sections = TutorSections::split(app(TutorContext::class)->version($actor, $session, (int) $version)->markdown);
                abort_unless(isset($sections[(int) $index]) && hash_equals($sha, hash('sha256', $sections[(int) $index])), 409);
            }
        }
    }

    private function sessionId(AuthorizedInferenceScope $scope): string
    {
        foreach ($scope->inference->sources as $source) {
            if (str_starts_with($source, 'tutor-session:')) {
                return substr($source, 14);
            }
        }
        abort(403);
    }

    /** @return array{0: TutorSession, 1: string, 2: list<string>, 3: string} */
    private function authorize(User $actor, array $payload): array
    {
        $kind = $payload['kind'] ?? null;
        $keys = $kind === 'query' ? ['kind', 'session_id', 'turn_operation_id', 'text'] : ['kind', 'session_id', 'version_id', 'index', 'sha256'];
        abort_unless(in_array($kind, ['query', 'section'], true) && array_keys($payload) === $keys && is_string($payload['session_id']), 422);
        $session = app(TutorAccess::class)->session($actor, $payload['session_id'], true);
        $school = (string) app(CurrentSchool::class)->id();
        $references = ['tutor-session:'.$session->id];
        if ($kind === 'query') {
            abort_unless(is_string($payload['turn_operation_id']) && is_string($payload['text']) && mb_strlen($payload['text']) <= 1000 && trim($payload['text']) !== '', 422);
            $text = app(TutorContext::class)->minimize($actor, $payload['text']);
            $operation = TutorRetrieval::operation(['query', $school, (string) $session->learner_id, $session->id, $payload['turn_operation_id'], hash('sha256', $text)]);

            return [$session, $text, $references, $operation];
        }
        abort_unless(is_int($payload['version_id']) && is_int($payload['index']) && is_string($payload['sha256'])
            && in_array($payload['version_id'], array_map('intval', $session->source_version_ids), true), 422);
        $version = app(TutorContext::class)->version($actor, $session, $payload['version_id']);
        $sections = TutorSections::split($version->markdown);
        $section = $sections[$payload['index']] ?? null;
        abort_unless(is_string($section) && hash_equals($payload['sha256'], hash('sha256', $section)), 409);
        $references[] = 'content-section:'.$payload['version_id'].':'.$payload['index'].':'.$payload['sha256'];
        $operation = TutorRetrieval::operation(['section', $school, (string) $session->learner_id, $session->id, (string) $payload['version_id'], $payload['sha256'],
            (string) config('ai.inference.features.tutor_retrieval.model')]);

        return [$session, app(TutorContext::class)->minimize($actor, $section), $references, $operation];
    }
}
