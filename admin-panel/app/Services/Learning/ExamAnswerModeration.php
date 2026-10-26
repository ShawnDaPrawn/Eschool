<?php

declare(strict_types=1);

namespace App\Services\Learning;

use App\Models\Learning\ExamAnswer;
use App\Models\User;
use App\Services\Ai\Inference\AiUnavailable;
use App\Services\Ai\Inference\Capability;
use App\Services\Ai\Inference\InferenceModerator;
use App\Services\Ai\Inference\ModerationContext;
use App\Services\Ai\Providers\OpenAiModerator;
use Throwable;

class ExamAnswerModeration implements InferenceModerator
{
    public function preflight(Capability $capability, string|array $input, ModerationContext $context): void
    {
        app(OpenAiModerator::class)->preflight($capability, $input, $context);
    }

    public function token(ExamAnswer $answer): string
    {
        return hash_hmac('sha256', 'exam-question:'.$answer->paper_version_id.':'.$answer->question_number, config('app.key'));
    }

    public function parse(ExamAnswer $answer, array $question, string $output): array
    {
        try {
            $data = json_decode($output, true, 8, JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            throw new AiUnavailable('exam_feedback_invalid');
        }
        if (! is_array($data) || count($data) !== 4 || array_diff(array_keys($data), ['question_token', 'mark_units', 'confidence_basis_points', 'feedback']) !== []
            || ($data['question_token'] ?? null) !== $this->token($answer) || ! is_int($data['mark_units'] ?? null) || $data['mark_units'] < 0 || $data['mark_units'] > $question['max_units']
            || ! is_int($data['confidence_basis_points'] ?? null) || $data['confidence_basis_points'] < 0 || $data['confidence_basis_points'] > 10000
            || ! is_string($data['feedback'] ?? null) || trim($data['feedback']) === '' || strlen($data['feedback']) > 1600 || ! mb_check_encoding($data['feedback'], 'UTF-8')
            || str_contains($data['feedback'], "\0") || preg_match('~(?:https?://|mailto:|<script|<iframe)~iu', $data['feedback'])
            || (mb_strlen($question['memo']) >= 32 && str_contains(mb_strtolower($data['feedback']), mb_strtolower($question['memo'])))) {
            throw new AiUnavailable('exam_feedback_invalid');
        }

        return $data;
    }

    public function flags(Capability $capability, string $direction, string|array $content, ModerationContext $context): array
    {
        $flags = app(OpenAiModerator::class)->flags($capability, $direction, $content, $context);
        if ($flags !== [] || $direction !== 'output') {
            return $flags;
        }
        $answer = ExamAnswer::where('operation_id', $context->operationId)->firstOrFail();
        $actor = User::findOrFail($answer->user_id);
        $answer = app(ExamAnswerAccess::class)->answer($actor, $answer->id);
        try {
            if (! is_string($content)) {
                throw new AiUnavailable('exam_feedback_invalid');
            }
            $this->parse($answer, app(ExamAnswerAccess::class)->question($actor, $answer), $content);
        } catch (AiUnavailable) {
            return ['exam_feedback_invalid'];
        }

        return [];
    }
}
