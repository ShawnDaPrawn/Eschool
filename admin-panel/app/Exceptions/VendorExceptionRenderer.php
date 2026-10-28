<?php

declare(strict_types=1);

namespace App\Exceptions;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/** Private vendor forms never redirect with credentials or reasons as old input. */
final class VendorExceptionRenderer
{
    public static function render(Throwable $error, Request $request): ?Response
    {
        if (! $error instanceof ValidationException) {
            return null;
        }
        if ($request->expectsJson() || ! $request->routeIs('vendor.*', 'vendor-operators.*')) {
            return null;
        }
        if ($request->routeIs('vendor.*')) {
            $messages = $error->validator->errors()->all();

            return response()->view('vendor.error', compact('messages'), 422);
        }
        $title = __('Vendor operators');
        $message = implode(' ', $error->validator->errors()->all());
        $draft = [];
        $returnUrl = route('vendor-operators.index');

        return response()->view('errors.private-year-refusal', compact('title', 'message', 'draft', 'returnUrl'), 422);
    }
}
