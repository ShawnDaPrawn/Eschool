<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\Admissions\IntakePanelPage;
use App\Services\Admissions\IntakePanelScope;
use Closure;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class AuthorizeIntake
{
    public function handle(Request $r, Closure $next): Response
    {
        try {
            app(IntakePanelScope::class)->authorize($r);
            $response = $next($r);
        } catch (ValidationException $error) {
            $response = app(IntakePanelPage::class)->failure($r, $error->errors(), 422);
        } catch (HttpExceptionInterface|ModelNotFoundException $error) {
            if ($error instanceof HttpExceptionInterface && in_array($error->getStatusCode(), [409, 422, 503], true)) {
                $response = app(IntakePanelPage::class)->failure($r, [[$error->getStatusCode() === 409 ? 'Compare the current record before saving this change again.' : 'Check the form and try again.']], $error->getStatusCode());
            } else {
                $response = app(ExceptionHandler::class)->render($r, $error);
            }
        }
        $response->headers->set('Cache-Control', 'private, no-store');
        $response->headers->set('Referrer-Policy', 'no-referrer');

        return $response;
    }
}
