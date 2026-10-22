<?php

declare(strict_types=1);

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

/**
 * API v2 errors in the same envelope as every other answer,
 * {error: true, message, data, code}, with the real HTTP status and a string
 * code a client can branch on (listed in openapi.yaml, ErrorCode). v1 is left
 * alone: the installed apps read its bodies as they are.
 *
 * Registered in bootstrap/app.php; returns null for anything outside
 * /api/v2, so Laravel renders those as before.
 */
class ApiV2ExceptionRenderer
{
    /** HTTP status => [code, translation key of the message]. */
    private const CODES = [
        400 => ['bad_request', 'api_bad_request'],
        401 => ['unauthenticated', 'api_unauthenticated'],
        403 => ['forbidden', 'api_forbidden'],
        404 => ['not_found', 'api_not_found'],
        405 => ['method_not_allowed', 'api_method_not_allowed'],
        409 => ['conflict', 'api_conflict'],
        413 => ['payload_too_large', 'api_payload_too_large'],
        419 => ['session_expired', 'api_session_expired'],
        422 => ['validation_failed', 'api_validation_failed'],
        429 => ['too_many_requests', 'api_too_many_requests'],
        500 => ['server_error', 'api_server_error'],
        503 => ['unavailable', 'api_unavailable'],
    ];

    public static function render(Throwable $e, Request $request): ?JsonResponse
    {
        // A response a helper threw on purpose is already the answer.
        if (! $request->is('api/v2', 'api/v2/*') || $e instanceof HttpResponseException) {
            return null;
        }

        if ($e instanceof AuthenticationException) {
            return self::envelope(401);
        }

        if ($e instanceof ValidationException) {
            // The first message, like v1, and every message by field.
            return self::envelope(422, $e->validator->errors()->first(), ['errors' => $e->errors()]);
        }

        if ($e instanceof HttpExceptionInterface) {
            // Keeps Retry-After and the X-RateLimit headers of a 429.
            return self::envelope($e->getStatusCode(), headers: $e->getHeaders());
        }

        // Anything else is a bug: it is reported by the handler, and the
        // client gets no exception text unless the app is in debug mode.
        return self::envelope(500, details: config('app.debug') ? $e->getMessage() : null);
    }

    /**
     * @param  array<string, mixed>|null  $data
     * @param  array<string, string|string[]>  $headers
     */
    public static function envelope(int $status, ?string $message = null, ?array $data = null, array $headers = [], ?string $code = null, ?string $details = null): JsonResponse
    {
        [$defaultCode, $messageKey] = self::CODES[$status] ?? ['http_error', null];

        $body = [
            'error' => true,
            'message' => $message ?? ($messageKey ? trans($messageKey) : (Response::$statusTexts[$status] ?? 'Error')),
            'data' => $data,
            'code' => $code ?? $defaultCode,
        ];
        if ($details !== null) {
            $body['details'] = $details;
        }

        return new JsonResponse($body, $status, $headers);
    }
}
