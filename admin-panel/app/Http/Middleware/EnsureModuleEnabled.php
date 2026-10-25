<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\Platform\Identity\AppTokens;
use App\Services\Platform\Identity\PortalSession;
use App\Services\Platform\Modules\ModuleGate;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureModuleEnabled
{
    public function handle(Request $request, Closure $next, string $key, string $operation = 'auto'): Response
    {
        if ($operation === 'portal' && ! PortalSession::isPortal($request)) {
            return $next($request);
        }
        if ($key === 'web_current' && PortalSession::isPortal($request) && $request->session()->get('portal_app') === AppTokens::FACILITATOR) {
            $key = 'web_facilitator';
        }
        if ($key === 'web_current') {
            $role = collect(['Parent', 'Student', 'Teacher'])->first(fn ($role) => $request->user()?->hasRole($role));
            if ($role === null) {
                return $next($request);
            }
            $key = 'web_'.match ($role) {
                'Parent' => 'parent', 'Student' => 'learner', default => 'teacher'
            };
        }
        $gate = app(ModuleGate::class);
        abort_unless($gate->allows($key), 404);
        $write = $operation === 'write' || ($operation === 'auto' && ! $request->isMethodSafe());
        abort_if($write && ! $gate->writable($key), 403, 'This module is read-only.');

        return $next($request);
    }
}
