<?php

declare(strict_types=1);

namespace App\Services\Learning;

use App\Models\Learning\ExamAnswer;
use App\Models\User;
use App\Services\Ai\Inference\AiUnavailable;
use App\Services\Ai\Inference\AuthorizedInferenceScope;
use App\Services\Ai\Inference\Capability;
use App\Services\Ai\Inference\FeatureInferenceRequest;
use App\Services\Ai\Inference\InferenceFeaturePolicy;
use App\Services\Ai\Inference\InferenceRequest;
use App\Services\Platform\Tenancy\CurrentSchool;

class ExamAnswerFeaturePolicy implements InferenceFeaturePolicy
{
    public function feature(): string
    {
        return 'exam_coach';
    }

    public function resolve(User $actor, Capability $capability, FeatureInferenceRequest $request): AuthorizedInferenceScope
    {
        abort_unless($capability === Capability::Vision && array_keys($request->payload) === ['answer_id'] && is_string($request->payload['answer_id']), 422);
        $answer = app(ExamAnswerAccess::class)->answer($actor, $request->payload['answer_id'], false, true);
        abort_unless($answer->operation_id === $request->operationId && in_array($answer->state, ['pending', 'completed'], true), 409);
        $question = app(ExamAnswerAccess::class)->question($actor, $answer);
        $minimize = app(TutorContext::class);
        $text = json_encode(['question_token' => app(ExamAnswerModeration::class)->token($answer), 'question' => $minimize->minimize($actor, $question['prompt']),
            'memo' => $minimize->minimize($actor, $question['memo']), 'maximum_units' => $question['max_units'], 'working' => $minimize->minimize($actor, (string) $answer->working)], JSON_THROW_ON_ERROR);
        if (strlen($text) > 32768) {
            throw new AiUnavailable('exam_context_limit');
        }
        $ref = app(ExamAnswerAccess::class)->reference($answer);

        return new AuthorizedInferenceScope(app(CurrentSchool::class)->id(), (int) $actor->id,
            new InferenceRequest('exam_coach', $request->operationId, 'exam_coach', ['text' => $text, 'media' => [$ref]],
                ['paper-version:'.$answer->paper_version_id.':'.$answer->paper_fingerprint, 'photo:'.$answer->id.':'.$answer->sha256]), (int) $answer->learner_id, (int) $answer->subject_id);
    }

    public function reauthorize(User $actor, AuthorizedInferenceScope $scope): void
    {
        abort_unless(app(CurrentSchool::class)->id() === $scope->schoolId && (int) $actor->id === $scope->actorId, 403);
        $meta = ExamAnswer::where('operation_id', $scope->inference->operationId)->where('user_id', $actor->id)->firstOrFail();
        $row = app(ExamAnswerAccess::class)->answer($actor, $meta->id, false, true);
        abort_unless((int) $row->learner_id === $scope->learnerId && (int) $row->subject_id === $scope->subjectId
            && $scope->inference->input['media'] === [app(ExamAnswerAccess::class)->reference($row)], 403);
    }
}
