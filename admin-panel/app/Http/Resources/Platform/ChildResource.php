<?php

declare(strict_types=1);

namespace App\Http\Resources\Platform;

use App\Models\Students;
use App\Services\Platform\SchoolProfile;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One of a parent's children, for the child switcher (openapi.yaml, Child).
 * "id" is the learner's id, the child_id the v1 parent endpoints take.
 */
class ChildResource extends JsonResource
{
    public function __construct(Students $child, private readonly SchoolProfile $school)
    {
        parent::__construct($child);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Students $child */
        $child = $this->resource;
        $user = $child->user;

        return [
            'id' => (int) $child->id,
            'user_id' => (int) $child->user_id,
            'first_name' => (string) $user->first_name,
            'last_name' => (string) $user->last_name,
            'name' => trim($user->first_name.' '.$user->last_name),
            'image' => ContextUserResource::imageUrl($user),
            'student_number' => $child->getAttribute('student_number'),
            'approved' => (bool) $child->getAttribute('context_approved'),
            'homeroom' => $child->getAttribute('context_homeroom'),
            // The child's school, including the guardian's other schools.
            'school' => new SchoolSummaryResource($this->school),
        ];
    }
}
