<?php

declare(strict_types=1);

namespace App\Http\Resources\Platform;

use App\Models\Semester;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A term of the school year (openapi.yaml, Term). "current" is whether today,
 * in the school's timezone, falls inside it.
 *
 * @property Semester $resource
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
            'current' => (bool) $this->resource->current,
        ];
    }
}
