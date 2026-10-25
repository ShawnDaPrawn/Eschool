<?php

declare(strict_types=1);

namespace App\Services\Learning;

use App\Models\Learning\TutorMessage;
use App\Models\Learning\TutorSession;
use App\Models\User;
use App\Services\Ai\Inference\AiUnavailable;
use App\Services\Ai\Inference\Capability;
use App\Services\Ai\Inference\InferenceModerator;
use App\Services\Ai\Inference\ModerationContext;
use App\Services\Ai\Providers\OpenAiModerator;
use Throwable;

class TutorModeration implements InferenceModerator
{
    public function localFlags(string $text): array
    {
        return preg_match('/\b(kill myself|end my life|hurt myself|being abused|sexually assaulted)\b/iu', $text)
            ? ['safeguarding_disclosure'] : [];
    }

    public function preflight(Capability $capability, string|array $input, ModerationContext $context): void
    {
        app(OpenAiModerator::class)->preflight($capability, $input, $context);
    }

    public function parse(TutorMessage $message, string $output): array
    {
        try {
            $data = json_decode($output, true, 8, JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            throw new AiUnavailable('tutor_output_invalid');
        }
        $session = TutorSession::findOrFail($message->tutor_session_id);
        $token = hash_hmac('sha256', 'subject:'.$session->subject_id, config('app.key'));
        $kinds = trim((string) $message->working) === '' ? ['hint', 'question', 'refusal'] : ['hint', 'question', 'correction', 'refusal'];
        if (! is_array($data) || count($data) !== 4 || array_diff(array_keys($data), ['subject_token', 'kind', 'text', 'question']) !== []
            || ($data['subject_token'] ?? null) !== $token || ! in_array($data['kind'] ?? null, $kinds, true)
            || ! is_string($data['text'] ?? null) || trim($data['text']) === '' || strlen($data['text']) > 1600
            || ! is_string($data['question'] ?? null) || trim($data['question']) === '' || strlen($data['question']) > 400
            || ! mb_check_encoding($data['text'].$data['question'], 'UTF-8') || str_contains($data['text'].$data['question'], "\0")
            || preg_match('~(?:https?://|mailto:|<script|<iframe)~iu', $data['text'].$data['question'])) {
            throw new AiUnavailable('tutor_output_invalid');
        }

        return $data;
    }

    public function flags(Capability $capability, string $direction, string|array $content, ModerationContext $context): array
    {
        if ($direction === 'input') {
            $local = $this->localFlags(is_string($content) ? $content : $content['text']);
            if ($local !== []) {
                return $local;
            }
        }
        $flags = app(OpenAiModerator::class)->flags($capability, $direction, $content, $context);
        if ($flags !== [] || $direction !== 'output') {
            return $flags;
        }
        $meta = TutorMessage::where('operation_id', $context->operationId)->select('id', 'user_id')->firstOrFail();
        $message = app(TutorFeaturePolicy::class)->message(User::findOrFail($meta->user_id), $meta->id);
        try {
            if (! is_string($content)) {
                throw new AiUnavailable('tutor_output_invalid');
            }
            $this->parse($message, $content);
        } catch (AiUnavailable) {
            return ['tutor_output_invalid'];
        }

        return [];
    }
}
