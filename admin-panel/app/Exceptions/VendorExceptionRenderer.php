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
        if ($request->expectsJson() || ! $request->routeIs('vendor.*', 'vendor-operators.*', 'vendor-settlements.*')) {
            return null;
        }
        if ($request->routeIs('vendor.*')) {
            $messages = $error->validator->errors()->all();

            return response()->view('vendor.error', compact('messages'), 422);
        }
        $title = $request->routeIs('vendor-settlements.*') ? __('Vendor settlements') : __('Vendor operators');
        $message = implode(' ', $error->validator->errors()->all());
        $draft = [];
        $returnUrl = route($request->routeIs('vendor-settlements.*') ? 'vendor-settlements.index' : 'vendor-operators.index');

        return response()->view('errors.private-year-refusal', compact('title', 'message', 'draft', 'returnUrl'), 422);
    }
}
