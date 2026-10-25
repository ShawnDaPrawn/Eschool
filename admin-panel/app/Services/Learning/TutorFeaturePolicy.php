<?php

declare(strict_types=1);

namespace App\Services\Learning;

use App\Models\Learning\TutorMessage;
use App\Models\Subject;
use App\Models\User;
use App\Services\Ai\Inference\AiUnavailable;
use App\Services\Ai\Inference\AuthorizedInferenceScope;
use App\Services\Ai\Inference\Capability;
use App\Services\Ai\Inference\FeatureInferenceRequest;
use App\Services\Ai\Inference\InferenceFeaturePolicy;
use App\Services\Ai\Inference\InferenceRequest;
use App\Services\Platform\Tenancy\CurrentSchool;

class TutorFeaturePolicy implements InferenceFeaturePolicy
{
    public function feature(): string
    {
        return 'tutor';
    }

    public function message(User $actor, string $id): TutorMessage
    {
        app(TutorAccess::class)->learner($actor);
        $meta = TutorMessage::select('id', 'school_id', 'user_id', 'tutor_session_id', 'state', 'purged_at')->findOrFail($id);
        abort_unless((int) $meta->user_id === (int) $actor->id && $meta->purged_at === null, 404);
        $session = app(TutorAccess::class)->session($actor, $meta->tutor_session_id);
        abort_unless(in_array($meta->state, ['pending', 'completed'], true), 409, 'This tutor turn is unavailable.');
        $message = TutorMessage::findOrFail($meta->id);
        app(TutorContext::class)->reauthorize($actor, $session, $message->source_manifest);

        return $message;
    }

    public function resolve(User $actor, Capability $capability, FeatureInferenceRequest $request): AuthorizedInferenceScope
    {
        abort_unless(array_keys($request->payload) === ['message_id'] && is_string($request->payload['message_id']), 422);
        $message = $this->message($actor, $request->payload['message_id']);
        abort_unless($request->operationId === $message->operation_id, 409);
        $session = app(TutorAccess::class)->session($actor, $message->tutor_session_id, true);
        $hasPhotos = $message->media_ids !== [];
        abort_unless($capability === ($hasPhotos ? Capability::Vision : Capability::Complete), 422);
        $minimize = app(TutorContext::class);
        $input = json_encode(['subject_token' => hash_hmac('sha256', 'subject:'.$session->subject_id, config('app.key')),
            'subject' => $minimize->minimize($actor, (string) Subject::whereKey($session->subject_id)->value('name')),
            'phase' => trim((string) $message->working) === '' ? 'hint' : 'correction', 'approved_context' => $message->context_text,
            'question' => $minimize->minimize($actor, $message->learner_text), 'working' => $minimize->minimize($actor, (string) $message->working)], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (strlen(json_encode($input, JSON_THROW_ON_ERROR)) > 60000 || ($hasPhotos && strlen($input) > 32768)) {
            throw new AiUnavailable('tutor_context_limit');
        }
        $references = array_map(fn ($ref) => 'content:'.$ref['id'].':'.$ref['sha256'], $message->source_manifest['versions']);
        foreach ($message->source_manifest['notes'] as $ref) {
            $references[] = 'tutor-note:'.$ref['id'].':'.$ref['revision'].':'.$ref['sha256'];
        }
        if ($hasPhotos) {
            $media = app(TutorPhotos::class)->references($actor, $message);
            $input = ['text' => $input, 'media' => $media];
            foreach ($media as $ref) {
                $references[] = 'photo:'.$ref['id'].':'.$ref['sha256'];
            }
        }

        return new AuthorizedInferenceScope(app(CurrentSchool::class)->id(), (int) $actor->id,
            new InferenceRequest('tutor', $request->operationId, 'tutor', $input, $references), (int) $session->learner_id, (int) $session->subject_id);
    }

    public function reauthorize(User $actor, AuthorizedInferenceScope $scope): void
    {
        $message = TutorMessage::where('operation_id', $scope->inference->operationId)->select('id')->firstOrFail();
        $message = $this->message($actor, $message->id);
        $session = app(TutorAccess::class)->session($actor, $message->tutor_session_id, true);
        abort_unless((int) $session->learner_id === $scope->learnerId && (int) $session->subject_id === $scope->subjectId
            && (int) $actor->id === $scope->actorId && app(CurrentSchool::class)->id() === $scope->schoolId, 403);
        if ($message->media_ids !== []) {
            app(TutorPhotos::class)->references($actor, $message);
        }
    }
}
