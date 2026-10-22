<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Exceptions\ApiV2ExceptionRenderer;
use App\Models\Platform\IdempotencyKey;
use App\Services\Platform\IdempotencyOutcome;
use App\Services\Platform\IdempotencyService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * The Idempotency-Key header on API v2 writes (alias "idempotent").
 *
 * - `idempotent`: the key is optional; with one, a retried write gets the
 *   first answer again (header Idempotent-Replayed: true) and runs only once.
 * - `idempotent:required`: writes without a key are refused (400). Every money
 *   write uses this.
 *
 * Reads (GET, HEAD, OPTIONS) pass straight through, and so do writes from
 * someone not signed in: keys are remembered per user. The rules are in
 * IdempotencyService.
 */
class HandleIdempotencyKey
{
    public const HEADER = 'Idempotency-Key';

    public function __construct(private readonly IdempotencyService $keys) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next, string $mode = 'optional'): Response
    {
        if ($request->isMethodSafe()) {
            return $next($request);
        }

        $key = (string) $request->header(self::HEADER, '');
        if ($key === '') {
            return $mode === 'required'
                ? ApiV2ExceptionRenderer::envelope(400, trans('api_idempotency_key_missing'), code: 'idempotency_key_missing')
                : $next($request);
        }

        if (! $this->keys->isValidKey($key)) {
            return ApiV2ExceptionRenderer::envelope(400, trans('api_idempotency_key_invalid'), code: 'idempotency_key_invalid');
        }

        $user = $request->user();
        if ($user === null) {
            return $next($request);
        }

        [$outcome, $record] = $this->keys->begin((int) $user->getAuthIdentifier(), $key, $request);

        return match ($outcome) {
            IdempotencyOutcome::Replay => $this->keys->replay($record),
            IdempotencyOutcome::Mismatch => ApiV2ExceptionRenderer::envelope(422, trans('api_idempotency_key_reused'), code: 'idempotency_key_reused'),
            IdempotencyOutcome::InProgress => ApiV2ExceptionRenderer::envelope(409, trans('api_idempotency_key_in_progress'), code: 'idempotency_key_in_progress'),
            IdempotencyOutcome::Proceed => $this->proceed($request, $next, $record),
        };
    }

    /**
     * @param  Closure(Request): Response  $next
     */
    private function proceed(Request $request, Closure $next, IdempotencyKey $record): Response
    {
        try {
            $response = $next($request);
        } catch (Throwable $e) {
            $this->keys->release($record);

            throw $e;
        }

        $this->keys->complete($record, $response);

        return $response;
    }
}
