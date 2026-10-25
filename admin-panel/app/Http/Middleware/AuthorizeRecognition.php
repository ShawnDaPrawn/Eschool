<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\Recognition\RecognitionAuthority;
use App\Services\Recognition\RecognitionPanelScope;
use Closure;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class AuthorizeRecognition
{
    public function handle(Request $r, Closure $next): Response
    {
        try {
            $module = (string) $r->route('module');
            $a = app(RecognitionAuthority::class);
            $a->actor($r->user(), $module, ! $r->isMethodSafe());
            if ($r->routeIs('recognition.*')) {
                app(RecognitionPanelScope::class)->authorize($r);
            }
            if (! $r->isMethodSafe() || in_array($r->route()->getActionMethod(), ['configuration', 'learners', 'nominations', 'catalogue'], true)) {
                $a->staff($r->user(), $module, ! $r->isMethodSafe());
            }
            $response = $next($r);
        } catch (HttpExceptionInterface|ModelNotFoundException|ValidationException|HttpResponseException $error) {
            $response = app(ExceptionHandler::class)->render($r, $error);
        }
        $response->headers->set('Cache-Control', 'private, no-store');
        $response->headers->set('Referrer-Policy', 'no-referrer');
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        return $response;
    }
}
