<?php

declare(strict_types=1);

namespace App\Services\Ai\Providers;

use App\Services\Ai\Inference\AiUnavailable;
use App\Services\Ai\Inference\InferenceDeadline;
use App\Services\Ai\Inference\InferenceNotDispatched;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

/** Fixed-origin, bounded, single-attempt transport; never reports provider error bodies. */
class ProviderTransport
{
    public const VISION_REQUEST_MAXIMUM_BYTES = 5242880;

    private const ORIGINS = ['openai' => 'https://api.openai.com/v1', 'anthropic' => 'https://api.anthropic.com/v1', 'deepseek' => 'https://api.deepseek.com'];

    public function preflight(string $provider, string $path, array $payload, string $operationId, string $stage = 'inference', ?InferenceDeadline $deadline = null, bool $vision = false): void
    {
        $paths = ['openai' => ['/chat/completions', '/embeddings', '/moderations'], 'anthropic' => ['/messages'], 'deepseek' => ['/chat/completions']];
        $key = config('ai.providers.'.$provider.'.key');
        if (! config('ai.inference.enabled') || config('ai.providers.'.$provider.'.qualified') !== true
            || ! is_string($key) || trim($key) === '' || ! in_array($path, $paths[$provider] ?? [], true)
            || ! Str::isUuid($operationId) || ! in_array($stage, ['inference', 'input', 'output'], true)) {
            throw new InferenceNotDispatched('provider_not_qualified');
        }
        try {
            $bytes = json_encode($payload, JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            throw new InferenceNotDispatched('provider_request_invalid');
        }
        if (strlen($bytes) > ($vision ? self::VISION_REQUEST_MAXIMUM_BYTES : 262144)) {
            throw new InferenceNotDispatched('provider_request_limit');
        }
        $this->requestSeconds($deadline);
    }

    private function requestSeconds(?InferenceDeadline $deadline): int|float
    {
        try {
            return $deadline === null ? 15 : min(15, $deadline->remainingSeconds());
        } catch (AiUnavailable $error) {
            throw new InferenceNotDispatched($error->reason);
        }
    }

    public function post(string $provider, string $path, array $payload, string $operationId, string $stage = 'inference', ?InferenceDeadline $deadline = null, bool $vision = false): array
    {
        // Repeat local preflight: qualification may change after input moderation.
        $this->preflight($provider, $path, $payload, $operationId, $stage, $deadline, $vision);
        $seconds = $this->requestSeconds($deadline);
        $until = hrtime(true) + (int) ($seconds * 1000000000);
        $key = config('ai.providers.'.$provider.'.key');
        $headers = ['Idempotency-Key' => $operationId.'-'.$stage, 'X-Client-Request-Id' => $operationId];
        $headers += $provider === 'anthropic' ? ['x-api-key' => $key, 'anthropic-version' => '2023-06-01'] : ['Authorization' => 'Bearer '.$key];
        try {
            $response = Http::withHeaders($headers)->acceptJson()->asJson()->connectTimeout(min(3, $seconds))->timeout($seconds)
                ->withOptions(['allow_redirects' => false, 'stream' => true, 'read_timeout' => $seconds, 'verify' => true])
                ->post(self::ORIGINS[$provider].$path, $payload);
        } catch (Throwable) {
            // HTTP has been entered: timeout/connection errors have an unknown compute outcome.
            throw new AiUnavailable('provider_unavailable');
        }
        $stream = $response->toPsrResponse()->getBody();
        $resource = null;
        try {
            if (! $response->successful()) {
                throw new AiUnavailable('provider_unavailable');
            }
            $resource = $stream->detach();
            if (! is_resource($resource)) {
                throw new AiUnavailable('provider_protocol_invalid');
            }
            $bytes = '';
            while (! feof($resource)) {
                $remaining = ($until - hrtime(true)) / 1000000000;
                if ($remaining <= 0) {
                    throw new AiUnavailable($deadline === null ? 'provider_response_limit' : 'inference_deadline_exceeded');
                }
                // Update blocking socket reads to the remaining overall allowance;
                // an in-memory HTTP fake has no blocking socket to configure.
                $metadata = stream_get_meta_data($resource);
                if (str_contains(strtolower($metadata['stream_type'] ?? ''), 'socket')
                    && ! stream_set_timeout($resource, (int) $remaining, max(1, (int) (($remaining - (int) $remaining) * 1000000)))) {
                    throw new AiUnavailable('provider_unavailable');
                }
                $chunk = fread($resource, 16384);
                if ($chunk === false || (stream_get_meta_data($resource)['timed_out'] ?? false)) {
                    throw new AiUnavailable('provider_unavailable');
                }
                $bytes .= $chunk;
                if (strlen($bytes) > 1048576) {
                    throw new AiUnavailable('provider_response_limit');
                }
                if (hrtime(true) > $until) {
                    throw new AiUnavailable($deadline === null ? 'provider_response_limit' : 'inference_deadline_exceeded');
                }
            }
            $value = json_decode($bytes, true, 32, JSON_THROW_ON_ERROR);
            if (! is_array($value)) {
                throw new AiUnavailable('provider_protocol_invalid');
            }

            return $value;
        } catch (AiUnavailable $error) {
            throw $error;
        } catch (Throwable) {
            throw new AiUnavailable('provider_protocol_invalid');
        } finally {
            if (is_resource($resource)) {
                fclose($resource);
            }
            $stream->close();
        }
    }
}
