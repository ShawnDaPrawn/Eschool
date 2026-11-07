<?php

declare(strict_types=1);

namespace App\Services\Learning;

use App\Models\Learning\TutorSession;
use App\Models\User;
use App\Services\Ai\AiGateway;
use App\Services\Ai\Inference\AiUnavailable;
use App\Services\Ai\Inference\Capability;
use App\Services\Ai\Inference\FeatureInferenceRequest;
use App\Services\Platform\Tenancy\CurrentSchool;

/**
 * Chooses which section of each selected content version the tutor reads
 * (Action Plan 3.10), by comparing gateway embeddings of the learner's
 * minimized question and of each section. Every vector is metered, capped and
 * moderated by the gateway like any other inference; a section's vector is
 * replayed from its completed receipt for the rest of the session. When
 * retrieval is switched off, or anything fails, the tutor reads the first
 * 3,000 characters exactly as v1 does.
 */
class TutorRetrieval
{
    /** Section vectors one turn may request. */
    public const MAXIMUM_SECTIONS = 16;

    public static function enabled(): bool
    {
        $model = config('ai.inference.features.tutor_retrieval.model');

        return config('ai.inference.enabled') === true && config('ai.inference.features.tutor_retrieval.enabled') === true
            && is_string($model) && $model !== '';
    }

    /**
     * A stable operation UUID for one retrieval vector. A section's identity
     * includes its index as well as its hash, so identical sections in one
     * document are separate requests rather than an operation conflict.
     */
    public static function operation(array $parts): string
    {
        $hash = hash_hmac('sha256', implode('|', array_merge(['tutor_retrieval'], $parts)), (string) config('app.key'));

        return substr($hash, 0, 8).'-'.substr($hash, 8, 4).'-8'.substr($hash, 13, 3).'-'
            .dechex(8 | (hexdec($hash[16]) & 3)).substr($hash, 17, 3).'-'.substr($hash, 20, 12);
    }

    /**
     * @return array<int, array{method: string, index: int, sha256: string}> version id => choice
     */
    public function select(User $actor, TutorSession $session, string $turnOperation, string $question): array
    {
        $choices = [];
        $candidates = [];
        foreach ($session->source_version_ids as $id) {
            $sections = TutorSections::split(app(TutorContext::class)->version($actor, $session, (int) $id)->markdown);
            $choices[(int) $id] = ['method' => count($sections) > 1 ? 'opening' : 'single_section', 'index' => 0, 'sha256' => hash('sha256', $sections[0] ?? '')];
            if (count($sections) > 1) {
                $candidates[(int) $id] = $sections;
            }
        }
        if ($candidates === [] || ! self::enabled() || trim($question) === '') {
            return $choices;
        }
        try {
            $query = $this->vector($actor, ['kind' => 'query', 'session_id' => $session->id, 'turn_operation_id' => $turnOperation,
                'text' => mb_substr($question, 0, 1000)], self::operation(['query', (string) app(CurrentSchool::class)->id(), (string) $session->learner_id,
                    $session->id, $turnOperation, hash('sha256', app(TutorContext::class)->minimize($actor, mb_substr($question, 0, 1000)))]));
            $budget = self::MAXIMUM_SECTIONS;
            $chosen = [];
            foreach ($candidates as $id => $sections) {
                $best = null;
                foreach ($sections as $index => $section) {
                    if ($budget-- <= 0) {
                        break 2;
                    }
                    $sha = hash('sha256', $section);
                    $vector = $this->vector($actor, ['kind' => 'section', 'session_id' => $session->id, 'version_id' => $id, 'index' => $index, 'sha256' => $sha],
                        self::operation(['section', (string) app(CurrentSchool::class)->id(), (string) $session->learner_id, $session->id, (string) $id, (string) $index, $sha,
                            (string) config('ai.inference.features.tutor_retrieval.model')]));
                    $score = $this->cosine($query, $vector);
                    if ($best === null || $score > $best[0]) {
                        $best = [$score, $index, $sha];
                    }
                }
                if ($best !== null) {
                    $chosen[$id] = ['method' => 'embeddings', 'index' => $best[1], 'sha256' => $best[2]];
                }
            }
        } catch (AiUnavailable) {
            return $choices; // Disabled, capped, paused, flagged or unreachable: read the opening as v1 does.
        }

        return array_replace($choices, $chosen);
    }

    /** @return list<float> */
    private function vector(User $actor, array $payload, string $operation): array
    {
        $result = app(AiGateway::class)->runFeature($actor, Capability::Embed, new FeatureInferenceRequest('tutor_retrieval', $operation, $payload));
        if (! is_array($result->output) || $result->output === []) {
            throw new AiUnavailable('tutor_retrieval_invalid');
        }

        return array_map('floatval', $result->output);
    }

    private function cosine(array $a, array $b): float
    {
        if (count($a) !== count($b)) {
            throw new AiUnavailable('tutor_retrieval_invalid');
        }
        $dot = $left = $right = 0.0;
        foreach ($a as $i => $value) {
            $dot += $value * $b[$i];
            $left += $value * $value;
            $right += $b[$i] * $b[$i];
        }
        if ($left <= 0.0 || $right <= 0.0) {
            throw new AiUnavailable('tutor_retrieval_invalid');
        }

        return $dot / (sqrt($left) * sqrt($right));
    }
}
