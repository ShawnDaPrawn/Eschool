<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Services\Wellbeing\PublicReportingPage;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

/** Render before Laravel's validation redirect can flash private form fields. */
class PublicReportingExceptionRenderer
{
    public static function render(Throwable $error, Request $request): ?Response
    {
        if (! str_starts_with((string) $request->route()?->getName(), 'public.wellbeing.')) {
            return null;
        }
        $page = app(PublicReportingPage::class);
        if ($error instanceof ValidationException) {
            return $page->failedDraft($request, array_values($error->errors()), 422);
        }
        $status = $error instanceof HttpExceptionInterface ? $error->getStatusCode() : 500;
        if ($status === 404 && in_array($request->route()?->getName(), ['public.wellbeing.submit', 'public.wellbeing.reply'], true)) {
            try {
                if (! $page->writable($request)) {
                    return $page->failedDraft($request, [[__('Reporting is temporarily read-only. Keep your unsent draft privately and use the urgent guidance if you need help now.')]], 423);
                }
            } catch (ModelNotFoundException|HttpExceptionInterface) {
                // A disabled/missing school stays unavailable, without draft reflection.
            }
        }
        if (in_array($status, [409, 422], true) && ! $request->query->has('secret')) {
            return $page->failedDraft($request, [[__($status === 409 ? 'This request changed. Check your private follow-up before trying again.' : 'Check the form and try again.')]], $status);
        }
        $response = $page->unavailable($status);
        if ($error instanceof HttpExceptionInterface) {
            foreach ($error->getHeaders() as $name => $value) {
                if (in_array(strtolower($name), ['retry-after', 'x-ratelimit-limit', 'x-ratelimit-remaining', 'x-ratelimit-reset'], true)) {
                    $response->headers->set($name, $value);
                }
            }
        }

        return $response;
    }
}
