<?php

declare(strict_types=1);

namespace App\Http\Resources\Platform;

use App\Models\Students;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * GET /api/v2/me/context (openapi.yaml, Context), built from what
 * ContextService::for() returns.
 *
 * @property array<string, mixed> $resource
 */
class ContextResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $context = $this->resource;
        $school = $context['school'];

        return [
            'user' => new ContextUserResource($context['user'], $context['roles']),
            // One school, with the user's roles there, until Phase 1 adds memberships.
            'schools' => [
                [...(new SchoolSummaryResource($school))->toArray($request), 'roles' => $context['roles']],
            ],
            'current_school' => new CurrentSchoolResource($school, $context['session_year']),
            'modules' => $context['modules'],
            'notification_rules' => $context['notification_rules'],
            // An object, also when empty.
            'feature_flags' => (object) $context['feature_flags'],
            'sign_in_methods' => $context['sign_in_methods'],
            'children' => $context['children']->map(fn (Students $child) => new ChildResource($child, $school))->values(),
        ];
    }
}
