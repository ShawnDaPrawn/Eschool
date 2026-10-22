<?php

declare(strict_types=1);

namespace App\Services\Platform;

use App\Models\Platform\IdempotencyKey;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Symfony\Component\HttpFoundation\Response;

/**
 * Remembers API writes sent with an Idempotency-Key, so a client that retries
 * (a dropped connection, a double tap) gets the first answer again instead of
 * a second payment, mark or record. Follows the IETF Idempotency-Key header
 * draft: keys are per user, kept 24 hours; a key reused for a different
 * request is refused with 422, and one whose first request is still running
 * with 409.
 */
class IdempotencyService
{
    /** How long a key and its answer are kept. */
    public const TTL_HOURS = 24;

    /**
     * A first request that has not answered after this long is taken to have
     * died (Cloud Run's request timeout is shorter), so the key can be used again.
     */
    public const IN_FLIGHT_SECONDS = 300;

    public const MAX_KEY_LENGTH = 255;

    /** Answers that are not stored: the client may retry these with the same key. */
    private const NOT_STORED = [401, 403, 408, 409, 425, 429];

    /** Visible ASCII, 1 to 255 characters (UUIDs, ULIDs and the like). */
    public function isValidKey(string $key): bool
    {
        return strlen($key) <= self::MAX_KEY_LENGTH && preg_match('/^[\x21-\x7E]+$/', $key) === 1;
    }

    /**
     * Starts handling a write with this key, or says why not.
     *
     * @return array{0: IdempotencyOutcome, 1: ?IdempotencyKey}
     */
    public function begin(int $userId, string $key, Request $request): array
    {
        $keyHash = hash('sha256', $key);
        $requestHash = $this->fingerprint($request);

        $existing = IdempotencyKey::query()->where('user_id', $userId)->where('key_hash', $keyHash)->first();
        if ($existing !== null && $this->isStale($existing)) {
            $existing->delete();
            $existing = null;
        }

        if ($existing !== null) {
            return match (true) {
                ! hash_equals($existing->request_hash, $requestHash) => [IdempotencyOutcome::Mismatch, $existing],
                $existing->response_status === null => [IdempotencyOutcome::InProgress, $existing],
                default => [IdempotencyOutcome::Replay, $existing],
            };
        }

        try {
            $record = IdempotencyKey::query()->create([
                'user_id' => $userId,
                'key' => $key,
                'key_hash' => $keyHash,
                'method' => $request->method(),
                'route' => $request->route()?->getName() ?? $request->route()?->uri() ?? $request->path(),
                'request_hash' => $requestHash,
                'expires_at' => now()->addHours(self::TTL_HOURS),
            ]);
        } catch (UniqueConstraintViolationException) {
            // The same key arrived twice at once; the other request has it.
            return [IdempotencyOutcome::InProgress, null];
        }

        return [IdempotencyOutcome::Proceed, $record];
    }

    /** Stores the answer to replay, or frees the key when the answer may be retried. */
    public function complete(IdempotencyKey $record, Response $response): void
    {
        $status = $response->getStatusCode();
        $content = $response->getContent();

        if ($status >= 500 || in_array($status, self::NOT_STORED, true) || $content === false || ! mb_check_encoding($content, 'UTF-8')) {
            $this->release($record);

            return;
        }

        $record->update([
            'response_status' => $status,
            'response_headers' => array_filter(['Content-Type' => $response->headers->get('Content-Type')]),
            'response_body' => $content,
        ]);
    }

    /** Frees the key: the request failed in a way the client may retry. */
    public function release(IdempotencyKey $record): void
    {
        $record->delete();
    }

    /** The stored answer, marked as a replay. */
    public function replay(IdempotencyKey $record): Response
    {
        return response((string) $record->response_body, (int) $record->response_status, [
            ...($record->response_headers ?? []),
            'Idempotent-Replayed' => 'true',
        ]);
    }

    /**
     * What makes two requests "the same": method, path, every input value
     * (sorted, so field order does not matter) and every uploaded file's
     * name, size and content. Multipart boundaries and header order are left
     * out because a retry changes them.
     */
    public function fingerprint(Request $request): string
    {
        $files = array_map(
            fn (UploadedFile $file) => [$file->getClientOriginalName(), $file->getSize(), hash_file('sha256', (string) $file->getRealPath())],
            array_filter(Arr::dot($request->allFiles()), fn ($file) => $file instanceof UploadedFile),
        );

        return hash('sha256', (string) json_encode([
            $request->method(),
            $request->path(),
            self::sorted($request->input()),
            self::sorted($files),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION | JSON_INVALID_UTF8_SUBSTITUTE));
    }

    private function isStale(IdempotencyKey $record): bool
    {
        return $record->expires_at->isPast()
            || ($record->response_status === null && $record->created_at->lt(now()->subSeconds(self::IN_FLIGHT_SECONDS)));
    }

    private static function sorted(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }
        if (! array_is_list($value)) {
            ksort($value, SORT_STRING);
        }

        return array_map(self::sorted(...), $value);
    }
}
