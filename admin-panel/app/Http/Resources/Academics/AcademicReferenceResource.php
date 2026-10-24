<?php

declare(strict_types=1);

namespace App\Http\Resources\Academics;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AcademicReferenceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['grades' => GradeLevelResource::collection($this->resource['grades'])->resolve($request),
            'rating_scale' => $this->resource['rating_scale']->map(fn ($rating) => [
                'level' => $rating->level, 'name' => $rating->name,
                'min_basis_points' => $rating->min_basis_points, 'max_basis_points' => $rating->max_basis_points,
            ])->all()];
    }
}
