<?php

declare(strict_types=1);

namespace App\Http\Resources\Academics;

use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @property Subject $resource */
class SubjectStandardMappingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $s = $this->resource;

        return ['subject_id' => (int) $s->id, 'name' => $s->name, 'code' => $s->code,
            'type' => $s->type, 'medium_id' => (int) $s->medium_id, 'standard_id' => $s->standard_id === null ? null : (int) $s->standard_id,
            'standard' => $s->standard === null ? null : (new SubjectStandardResource($s->standard))->resolve($request)];
    }
}
