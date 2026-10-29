<?php

declare(strict_types=1);

namespace App\Exceptions;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

final class CashupExceptionRenderer
{
    public static function render(Throwable $error, Request $request): ?Response
    {
        if (! $error instanceof ValidationException || $request->expectsJson() || ! $request->routeIs('cashups.*', 'ticket-events.*')) {
            return null;
        }
        $title = $request->routeIs('ticket-events.*') ? __('Events and admission tickets') : __('Daily cash-up');
        $message = implode(' ', $error->validator->errors()->all());
        $draft = [];
        $returnUrl = route($request->routeIs('ticket-events.*') ? 'ticket-events.index' : 'cashups.index');

        return response()->view('errors.private-year-refusal', compact('title', 'message', 'draft', 'returnUrl'), 422);
    }
}
