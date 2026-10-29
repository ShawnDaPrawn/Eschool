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
        if (! $error instanceof ValidationException || $request->expectsJson() || ! $request->routeIs('fee-payer-corrections.*', 'fee-late-charges.*')) {
            return null;
        }
        $title = $request->routeIs('fee-late-charges.*') ? __('One-off late charges') : __('Unpaid payer corrections');
        $message = implode(' ', $error->validator->errors()->all());
        $draft = [];
        $returnRoute = $request->routeIs('fee-late-charges.*') ? 'fee-late-charges.index' : 'fee-payer-corrections.index';
        if ($request->routeIs('fee-late-charges.policy', 'fee-late-charges.save-policy')) {
            $returnRoute = 'fee-late-charges.policy';
        }
        $returnUrl = route($returnRoute);

        return response()->view('errors.private-year-refusal', compact('title', 'message', 'draft', 'returnUrl'), 422);
    }
}
