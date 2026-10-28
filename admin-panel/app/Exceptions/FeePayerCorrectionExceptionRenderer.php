<?php

declare(strict_types=1);

namespace App\Exceptions;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

final class FeePayerCorrectionExceptionRenderer
{
    public static function render(Throwable $error, Request $request): ?Response
    {
        if (! $error instanceof ValidationException || $request->expectsJson() || ! $request->routeIs('fee-payer-corrections.*')) {
            return null;
        }
        $title = __('Unpaid payer corrections');
        $message = implode(' ', $error->validator->errors()->all());
        $draft = [];
        $returnUrl = route('fee-payer-corrections.index');

        return response()->view('errors.private-year-refusal', compact('title', 'message', 'draft', 'returnUrl'), 422);
    }
}
