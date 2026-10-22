<?php

declare(strict_types=1);

namespace App\Services\Platform;

/** What to do with a write that carries an Idempotency-Key. */
enum IdempotencyOutcome
{
    /** First time this key is seen (or it expired): do the work and remember the answer. */
    case Proceed;

    /** Same key, same request, already answered: send the stored answer again. */
    case Replay;

    /** Same key, different request: refuse (422). */
    case Mismatch;

    /** Same key while the first request is still running: refuse (409), the client retries later. */
    case InProgress;
}
