<?php

declare(strict_types=1);

namespace App\Services\Platform\Identity;

use Illuminate\Http\Request;

/** An optional stale-view refusal, never an authentication or permission grant. */
final class ExpectedAccount
{
    public const HEADER = 'X-Account-Id';

    public static function check(Request $request): void
    {
        if (! $request->headers->has(self::HEADER) || $request->user() === null) {
            return;
        }
        $expected = (string) $request->header(self::HEADER);
        abort_unless($expected !== '' && $expected === (string) $request->user()->getAuthIdentifier(), 403, 'Your account changed. Reopen this screen.');
    }
}
