<?php

declare(strict_types=1);

namespace App\Http\Resources\Academics;

use App\Models\ClassSchool;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @property ClassSchool $resource */
class ClassGradeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['class_id' => (int) $this->resource->id, 'grade_level_id' => $this->resource->grade_level_id,
            'grade_level' => $this->resource->gradeLevel === null ? null : (new GradeLevelResource($this->resource->gradeLevel))->resolve($request)];
    }
}
