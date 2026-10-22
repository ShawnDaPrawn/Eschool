<?php

declare(strict_types=1);

namespace App\Logging;

use Illuminate\Container\Container;
use Illuminate\Log\Context\Repository as ContextRepository;
use Illuminate\Routing\Route;
use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;
use Throwable;

/**
 * Adds the request fields to every record of the json channel: the request id
 * that AssignRequestId put into the log context (queued jobs carry it on from
 * the request that queued them), the signed-in user's id, the school id (null
 * until Phase 1 sets it in the context), and the matched route's pattern and
 * method. Ids and patterns only: nothing here reads the request's input.
 */
final class AddRequestContext implements ProcessorInterface
{
    public function __invoke(LogRecord $record): LogRecord
    {
        $app = Container::getInstance();
        $context = $app->bound(ContextRepository::class) ? $app->make(ContextRepository::class) : null;
        $route = $this->route($app);

        return $record->with(extra: [
            ...$record->extra,
            'request_id' => $context?->get('request_id'),
            'trace_id' => $context?->get('trace_id'),
            'user_id' => $this->userId($app),
            'school_id' => $context?->get('school_id'),
            'route' => $route?->uri(),
            'method' => $route !== null ? $app->make('request')->getMethod() : null,
        ]);
    }

    /**
     * The id of a user some guard has already resolved for this request. The
     * auth manager is never created here, and a guard is only asked whether it
     * holds a user, so logging cannot trigger a database lookup.
     */
    private function userId(Container $app): int|string|null
    {
        try {
            if (! $app->resolved('auth')) {
                return null;
            }
            $guard = $app->make('auth')->guard();

            return $guard->hasUser() ? $guard->id() : null;
        } catch (Throwable) {
            return null;
        }
    }

    private function route(Container $app): ?Route
    {
        try {
            if (! $app->bound('request')) {
                return null;
            }
            $route = $app->make('request')->route();

            return $route instanceof Route ? $route : null;
        } catch (Throwable) {
            return null;
        }
    }
}
