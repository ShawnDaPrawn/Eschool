<?php

declare(strict_types=1);

namespace App\Services\Ai;

use App\Services\Ai\Inference\AiUnavailable;
use JsonException;

class PromptRegistry
{
    /** @return array{key: string, version: string, system: string, sha256: string} */
    public function get(string $key): array
    {
        if (! preg_match('/^[a-z][a-z0-9_-]{0,63}$/D', $key)) {
            throw new AiUnavailable('prompt_unknown');
        }
        $path = resource_path('ai/prompts/'.$key.'.json');
        if (! is_file($path) || filesize($path) > 32768) {
            throw new AiUnavailable('prompt_unknown');
        }
        $raw = file_get_contents($path);
        try {
            $value = json_decode($raw, true, 16, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new AiUnavailable('prompt_invalid');
        }
        if (! is_array($value) || ($value['key'] ?? null) !== $key || ! is_string($value['version'] ?? null)
            || ! preg_match('/^[0-9]+\.[0-9]+\.[0-9]+$/D', $value['version'])
            || ! is_string($value['system'] ?? null) || trim($value['system']) === '') {
            throw new AiUnavailable('prompt_invalid');
        }

        return ['key' => $key, 'version' => $value['version'], 'system' => $value['system'], 'sha256' => hash('sha256', $raw)];
    }
}
