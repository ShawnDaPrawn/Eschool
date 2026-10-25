<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\Platform\Modules\ModuleGate;
use App\Services\Transfers\TransferAuthority;
use App\Services\Transfers\TransferPanelScope;
use App\Services\Transfers\TransferQueries;
use Closure;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class AuthorizeTransfer
{
    public function handle(Request $r, Closure $next): Response
    {
        try {
            $a = app(TransferAuthority::class);
            $actor = $r->user();
            $method = $r->route()->getActionMethod();
            $write = ! $r->isMethodSafe();
            if ($r->routeIs('school-transfers.*')) {
                // A disabled panel feature has no page; source authorization
                // still runs before any enabled transfer data is loaded.
                if ($r->isMethodSafe()) {
                    $gate = app(ModuleGate::class);
                    $gate->forget();
                    abort_unless($gate->allows('school_transfers'), 404);
                }
                app(TransferPanelScope::class)->authorize($r);
            } elseif ($method === 'access') {
                app(TransferQueries::class)->access($actor);
            } elseif (in_array($method, ['consent', 'withdraw'], true)) {
                $a->ownConsent($actor, (int) $r->route('package'), $write);
            } elseif ($r->route('lane') !== null) {
                $lane = (string) $r->route('lane');
                if ($r->route('record') !== null) {
                    app(TransferQueries::class)->record($actor, $lane, (int) $r->route('record'));
                } elseif ($lane === 'consents') {
                    abort_unless(app(TransferQueries::class)->access($actor)['guardian'], 403);
                } else {
                    $a->staff($actor);
                }
            } else {
                $a->staff($actor, $write);
                if ($r->route('package') !== null) {
                    $method === 'accept' ? $a->incoming($actor, (int) $r->route('package'), true) : $a->outgoing($actor, (int) $r->route('package'), true);
                }
            }
            $response = $next($r);
        } catch (HttpExceptionInterface|ModelNotFoundException|ValidationException|HttpResponseException $error) {
            $response = app(ExceptionHandler::class)->render($r, $error);
        }
        $response->headers->set('Cache-Control', 'private, no-store');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'no-referrer');

        return $response;
    }
}
