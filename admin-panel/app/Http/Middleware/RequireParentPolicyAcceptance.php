<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\ParentPolicyService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * A parent or teacher accepts NavigateEd, and the school's privacy notice and
 * terms when those already exist, once. Later visits are not asked again.
 */
class RequireParentPolicyAcceptance
{
    public function __construct(private readonly ParentPolicyService $policies) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user || $request->is('api/parent/policy-acceptance', 'api/teacher/policy-acceptance') || ! $this->policies->needsAcceptance($user)) {
            return $next($request);
        }

        return response()->json([
            'error' => true,
            'message' => 'Accept the terms before you continue.',
            'code' => 'policy_acceptance_required',
        ], Response::HTTP_FORBIDDEN);
    }
}
