<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Pastoral\SupportDisclosure;
use App\Services\Pastoral\SupportAuthority;
use Closure;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class AuthorizeSupport
{
    public function handle(Request $request, Closure $next, string $operation): Response
    {
        try {
            $a = app(SupportAuthority::class);
            $actor = $request->user();
            $write = ! $request->isMethod('GET');
            if ($operation === 'access') {
                abort_unless($a->audiences($actor) !== [], 403);
            } elseif (in_array($operation, ['assignments', 'assign', 'revoke-assignment'], true) || $request->route('audience') === 'principal') {
                $a->principal($actor, $write);
            } elseif (in_array($operation, ['teacher', 'disclosure'], true)) {
                $a->teacher($actor);
                if ($operation === 'disclosure') {
                    $a->disclosure($actor, (int) $request->route('disclosure'));
                }
            } else {
                $a->professional($actor, $write);
                if ($request->route('plan') !== null) {
                    $a->plan($actor, (int) $request->route('plan'), $write);
                }
                if ($operation === 'revoke-disclosure') {
                    $row = SupportDisclosure::select('id', 'support_plan_id')->findOrFail((int) $request->route('disclosure'));
                    $a->plan($actor, (int) $row->support_plan_id, true);
                }
            }
            $response = $next($request);
        } catch (HttpExceptionInterface|ModelNotFoundException|ValidationException|HttpResponseException $error) {
            // Refusals and private validation responses must not enter a browser
            // or intermediary cache either. Unexpected exceptions still report.
            $response = app(ExceptionHandler::class)->render($request, $error);
        }
        $response->headers->set('Cache-Control', 'no-store, private');

        return $response;
    }
}
