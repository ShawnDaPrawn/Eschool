<?php

declare(strict_types=1);

namespace App\Services\Academics\Content;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use JsonException;

/** The only HTTP adapter for the private converter; never usable by clients. */
class ConverterClient
{
    private function origin(): string
    {
        $origin = rtrim((string) config('academic_content.converter_url'), '/');
        $p = parse_url($origin);
        if (! is_array($p) || ! isset($p['host']) || isset($p['user']) || isset($p['pass']) || isset($p['query']) || isset($p['fragment']) || ! empty($p['path'])
            || ! in_array($p['scheme'] ?? '', config('academic_content.allow_http') ? ['https', 'http'] : ['https'], true)) {
            throw new ContentProcessingFailure('converter_unavailable');
        }

        return $origin;
    }

    public function run(array $source, bool $transcribe = false): array
    {
        $origin = $this->origin();
        $token = (string) config('academic_content.converter_token');
        if (strlen($token) < 32 || preg_match('/[\r\n]/', $token)) {
            throw new ContentProcessingFailure('converter_unavailable');
        }
        $headers = [];
        if (config('academic_content.converter_iam')) {
            try {
                $identity = Http::withHeaders(['Metadata-Flavor' => 'Google'])->connectTimeout(2)->timeout(3)->withoutRedirecting()
                    ->get('http://metadata.google.internal/computeMetadata/v1/instance/service-accounts/default/identity', ['audience' => $origin, 'format' => 'full']);
                $jwt = $identity->body();
                if (! $identity->successful() || strlen($jwt) > 8192 || ! preg_match('/^[A-Za-z0-9_-]+\.[A-Za-z0-9_-]+\.[A-Za-z0-9_-]+$/D', $jwt)) {
                    throw new ContentProcessingFailure('converter_identity_unavailable');
                }
                $headers['X-Serverless-Authorization'] = 'Bearer '.$jwt;
            } catch (ConnectionException) {
                throw new ContentProcessingFailure('converter_identity_unavailable');
            }
        }
        try {
            $response = Http::withToken($token)->withHeaders($headers)->asJson()->connectTimeout(5)->timeout((int) config('academic_content.converter_timeout'))
                ->withOptions(['allow_redirects' => false, 'stream' => true])->post($origin.($transcribe ? '/transcribe' : '/convert'), $source);
        } catch (ConnectionException) {
            throw new ContentProcessingFailure('converter_unavailable');
        }
        if (! $response->successful()) {
            $errorStream = $response->toPsrResponse()->getBody();
            try {
                $errorBody = $errorStream->read(4096);
            } finally {
                $errorStream->close();
            }
            $error = json_decode($errorBody, true);
            $code = is_array($error) ? ($error['error'] ?? '') : '';
            $preflight = ['converter_busy', 'transcription_unavailable', 'invalid_request', 'invalid_source_url', 'source_unavailable', 'source_size_changed', 'source_checksum_changed', 'source_encoding_refused', 'source_too_large', 'transcription_duration_limit'];
            if (in_array($code, $preflight, true)) {
                throw new ContentProcessingFailure($code, $response->status() >= 500, false);
            }
            throw new ContentProcessingFailure($response->status() >= 500 ? 'converter_unavailable' : 'conversion_refused', $response->status() >= 500);
        }
        $maximum = (int) config('academic_content.maximum_markdown_bytes');
        $stream = $response->toPsrResponse()->getBody();
        $body = '';
        try {
            $deadline = microtime(true) + (int) config('academic_content.converter_timeout');
            while (! $stream->eof()) {
                $body .= $stream->read(65536);
                if (strlen($body) > $maximum * 3 + 16384 || microtime(true) > $deadline) {
                    throw new ContentProcessingFailure('converter_response_limit', false);
                }
            }
            $data = json_decode($body, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new ContentProcessingFailure('invalid_converter_response', false);
        } finally {
            $stream->close();
        }
        if (! is_array($data)) {
            throw new ContentProcessingFailure('invalid_converter_response', false);
        }
        $seconds = $data['duration_seconds'] ?? null;
        $language = $data['language'] ?? null;
        $markdown = $data['markdown'] ?? null;
        if (! is_array($data) || ! is_string($markdown) || trim($markdown) === '' || ! mb_check_encoding($markdown, 'UTF-8') || strlen($markdown) > $maximum
            || ! is_int($seconds) || $seconds < 0 || $seconds > min((int) config('ai.transcribe.maximum_seconds'), (int) ($source['maximum_seconds'] ?? config('ai.transcribe.maximum_seconds'))) || (! $transcribe && $seconds !== 0)
            || ($transcribe && $seconds === 0) || ($language !== null && (! is_string($language) || ! preg_match('/^[a-z]{2,3}$/D', $language)))
            || ($data['review_required'] ?? false) !== true || ($data['generation_id'] ?? null) !== $source['generation_id'] || ($data['source_sha256'] ?? null) !== $source['source_sha256']) {
            throw new ContentProcessingFailure('invalid_converter_response', false);
        }

        return ['markdown' => $markdown, 'duration_seconds' => $seconds, 'language' => $language];
    }
}
