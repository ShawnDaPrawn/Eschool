<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\Leadership\LearnerActivity;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ObserveLearnerActivity
{
    public function handle(Request $r, Closure $next): Response
    {
        $response = $next($r);
        if ($response->isSuccessful() && $r->is('api/*') && $r->user() instanceof User) {
            try {
                app(LearnerActivity::class)->record($r->user());
            } catch (\Throwable) {
                // Optional observation cannot break an otherwise valid API reply.
                // Never log a learner identity, endpoint, body or credential.
                logger()->warning('Learner activity observation unavailable');
            }
        }

        return $response;
    }
}
