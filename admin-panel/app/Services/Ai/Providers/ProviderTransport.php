<?php

declare(strict_types=1);

namespace App\Services\Ai\Providers;

use App\Services\Ai\Inference\AiUnavailable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

/** Fixed-origin, bounded, single-attempt transport; never reports provider error bodies. */
class ProviderTransport
{
    private const ORIGINS = ['openai' => 'https://api.openai.com/v1', 'anthropic' => 'https://api.anthropic.com/v1', 'deepseek' => 'https://api.deepseek.com'];

    public function post(string $provider, string $path, array $payload, string $operationId, string $stage = 'inference'): array
    {
        $paths = ['openai' => ['/chat/completions', '/embeddings', '/moderations'], 'anthropic' => ['/messages'], 'deepseek' => ['/chat/completions']];
        $key = config('ai.providers.'.$provider.'.key');
        if (! config('ai.inference.enabled') || config('ai.providers.'.$provider.'.qualified') !== true
            || ! is_string($key) || trim($key) === '' || ! in_array($path, $paths[$provider] ?? [], true)
            || ! Str::isUuid($operationId) || ! in_array($stage, ['inference', 'input', 'output'], true)) {
            throw new AiUnavailable('provider_not_qualified');
        }
        if (strlen(json_encode($payload, JSON_THROW_ON_ERROR)) > 262144) {
            throw new AiUnavailable('provider_request_limit');
        }
        $headers = ['Idempotency-Key' => $operationId.'-'.$stage, 'X-Client-Request-Id' => $operationId];
        $headers += $provider === 'anthropic' ? ['x-api-key' => $key, 'anthropic-version' => '2023-06-01'] : ['Authorization' => 'Bearer '.$key];
        try {
            $response = Http::withHeaders($headers)->acceptJson()->asJson()->connectTimeout(3)->timeout(15)
                ->withOptions(['allow_redirects' => false, 'stream' => true, 'read_timeout' => 15, 'verify' => true])
                ->post(self::ORIGINS[$provider].$path, $payload);
        } catch (Throwable) {
            throw new AiUnavailable('provider_unavailable');
        }
        $stream = $response->toPsrResponse()->getBody();
        try {
            if (! $response->successful()) {
                throw new AiUnavailable('provider_unavailable');
            }
            $bytes = '';
            $deadline = microtime(true) + 15;
            while (! $stream->eof()) {
                $bytes .= $stream->read(16384);
                if (strlen($bytes) > 1048576 || microtime(true) > $deadline) {
                    throw new AiUnavailable('provider_response_limit');
                }
            }
            $value = json_decode($bytes, true, 32, JSON_THROW_ON_ERROR);
            if (! is_array($value)) {
                throw new AiUnavailable('provider_protocol_invalid');
            }

            return $value;
        } catch (AiUnavailable $e) {
            throw $e;
        } catch (Throwable) {
            throw new AiUnavailable('provider_protocol_invalid');
        } finally {
            $stream->close();
        }
    }
}
