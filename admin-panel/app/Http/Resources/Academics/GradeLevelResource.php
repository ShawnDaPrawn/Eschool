<?php

declare(strict_types=1);

namespace App\Http\Resources\Academics;

use App\Models\Academics\GradeLevel;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @property GradeLevel $resource */
class GradeLevelResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id' => (int) $this->resource->id, 'code' => $this->resource->code, 'name' => $this->resource->name,
            'phase' => $this->resource->phase, 'sort_order' => $this->resource->sort_order];
    }
}
