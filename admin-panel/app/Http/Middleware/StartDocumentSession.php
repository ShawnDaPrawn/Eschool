<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Http\Request;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Session\SessionManager;

/** Keep back/validation navigation on documents, never background assets or lists. */
final class StartDocumentSession extends StartSession
{
    public function __construct(SessionManager $manager, ?callable $cacheFactoryResolver = null)
    {
        parent::__construct($manager, $cacheFactoryResolver ?? static fn () => app(CacheFactory::class));
    }

    protected function storeCurrentUrl(Request $request, $session)
    {
        if (! $request->attributes->get('background_document_response', false)) {
            parent::storeCurrentUrl($request, $session);
        }
    }
}
