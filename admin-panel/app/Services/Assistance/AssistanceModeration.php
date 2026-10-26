<?php

declare(strict_types=1);

namespace App\Services\Assistance;

use App\Models\Assistance\AssistanceTurn;
use App\Models\User;
use App\Services\Ai\Inference\AiUnavailable;
use App\Services\Ai\Inference\Capability;
use App\Services\Ai\Inference\InferenceModerator;
use App\Services\Ai\Inference\ModerationContext;
use App\Services\Ai\Providers\OpenAiModerator;
use Throwable;

class AssistanceModeration implements InferenceModerator
{
    public function preflight(Capability $capability, string|array $input, ModerationContext $context): void
    {
        app(OpenAiModerator::class)->preflight($capability, $input, $context);
    }

    public function parse(AssistanceTurn $turn, string|array $output): array
    {
        try {
            $data = is_string($output) ? json_decode($output, true, 8, JSON_THROW_ON_ERROR) : null;
        } catch (Throwable) {
            throw new AiUnavailable('assistance_output_invalid');
        }
        $allowed = array_column($turn->citations, 'token');
        if (! is_array($data) || count($data) !== 4 || array_diff(array_keys($data), ['language', 'text', 'citations', 'handoff']) !== []
            || ($data['language'] ?? null) !== $turn->source_request['language'] || ! is_string($data['text'] ?? null)
            || trim($data['text']) === '' || strlen($data['text']) > 4000 || ! mb_check_encoding($data['text'], 'UTF-8')
            || str_contains($data['text'], "\0") || preg_match('~(?:https?://|mailto:|<script|<iframe)~iu', $data['text'])
            || ! is_bool($data['handoff'] ?? null) || ! is_array($data['citations'] ?? null) || ! array_is_list($data['citations'])
            || count($data['citations']) > count($allowed) || collect($data['citations'])->contains(fn ($token) => ! is_string($token) || ! in_array($token, $allowed, true))
            || count(array_unique($data['citations'])) !== count($data['citations'])
            || ($data['citations'] === [] && ! $data['handoff'])
            || ($turn->purpose === 'translation' && ($data['citations'] !== ['s1'] || $data['handoff']))) {
            throw new AiUnavailable('assistance_output_invalid');
        }

        return $data;
    }

    public function flags(Capability $capability, string $direction, string|array $content, ModerationContext $context): array
    {
        if ($direction === 'input' && is_string($content) && preg_match('/\b(kill myself|end my life|hurt myself|being abused|sexually assaulted)\b/iu', $content)) {
            return ['safeguarding_disclosure'];
        }
        $flags = app(OpenAiModerator::class)->flags($capability, $direction, $content, $context);
        if ($flags !== [] || $direction !== 'output') {
            return $flags;
        }
        $meta = AssistanceTurn::select('id', 'user_id')->findOrFail($context->operationId);
        $turn = app(AssistanceInference::class)->authorize(User::findOrFail($meta->user_id), $meta->id);
        try {
            $this->parse($turn, $content);
        } catch (AiUnavailable) {
            return ['assistance_output_invalid'];
        }

        return [];
    }
}
