<?php

declare(strict_types=1);

namespace App\Http\Resources\Platform;

use App\Models\Term;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A term of the school year (openapi.yaml, Term). "current" is whether today,
 * in the school's timezone, falls inside it.
 *
 * @property Term $resource
 */
class TermResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->resource->id,
            'name' => (string) $this->resource->name,
            'start_date' => SessionYearResource::date($this->resource->start_date),
            'end_date' => SessionYearResource::date($this->resource->end_date),
            'grade_from' => $this->resource->grade_from, 'grade_to' => $this->resource->grade_to,
            'marks_lock_on' => SessionYearResource::date($this->resource->marks_lock_on),
            'reports_on' => SessionYearResource::date($this->resource->reports_on),
            'current' => (bool) $this->resource->current,
        ];
    }
}
