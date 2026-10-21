<?php

declare(strict_types=1);

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Thrown when an account has had too many wrong passwords (see AppServiceProvider).
 */
class TooManyLoginFailures extends RuntimeException
{
    public const MAX_FAILURES = 10;

    public function __construct(public readonly string $account, public readonly int $seconds)
    {
        parent::__construct('Too many failed sign-in attempts');
    }

    /** One line instead of a stack trace for every refused attempt. */
    public function report(): void
    {
        Log::warning('Sign-in refused after too many wrong passwords', ['account' => $this->account, 'ip' => request()->ip()]);
    }

    public function render(Request $request): JsonResponse|RedirectResponse
    {
        $message = trans('Too many wrong passwords for this account. Try again in :minutes minutes.', [
            'minutes' => max(1, (int) ceil($this->seconds / 60)),
        ]);

        // The apps show messages from responses shaped like this (see ResponseService).
        if ($request->is('api/*') || $request->expectsJson()) {
            return response()->json(['error' => true, 'message' => $message]);
        }

        return back()->withInput($request->only('email'))->withErrors(['email' => $message]);
    }
}
