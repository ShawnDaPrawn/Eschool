<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\Wellbeing\WellbeingAccess;
use Closure;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class AuthorizeWellbeing
{
    public function handle(Request $request, Closure $next, string $action): Response
    {
        try {
            $a = app(WellbeingAccess::class);
            $actor = $request->user();
            // The private code travels in a POST body, but follow-up is a read.
            $write = ! $request->isMethodSafe() && ! in_array($action, ['identified-read', 'own-photo-read', 'own-notice-read'], true);
            if (in_array($action, ['configuration', 'managed-cases', 'team-assignments', 'case-assignments', 'category-create', 'category-save', 'team-create', 'team-save', 'case-create', 'case-assignment-create', 'case-assignment-save'], true)) {
                $a->configure($actor, $write);
            } elseif (in_array($action, ['access', 'identified-submit', 'identified-read', 'identified-reply', 'identify', 'own-photo-upload', 'own-photo-read', 'own-notices', 'own-notice-read'], true)) {
                $a->actor($actor, $write);
            } elseif (in_array($action, ['reports', 'report', 'report-respond', 'report-photo'], true)) {
                $a->professional($actor, $write);
                if ($request->route('report') !== null) {
                    $a->report($actor, (int) $request->route('report'), $write);
                }
            } else {
                $a->professional($actor, $write);
                if ($request->route('case') !== null) {
                    $a->case($actor, (int) $request->route('case'), $write);
                }
            }
            $response = $next($request);
        } catch (HttpExceptionInterface|ModelNotFoundException|ValidationException|HttpResponseException $error) {
            $response = app(ExceptionHandler::class)->render($request, $error);
        }
        $response->headers->set('Cache-Control', 'no-store, private');

        return $response;
    }
}
