<?php

declare(strict_types=1);

namespace App\Http\Resources\Academics;

use App\Models\Academics\SubjectStandard;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @property SubjectStandard $resource */
class SubjectStandardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $s = $this->resource;

        return ['id' => (int) $s->id, 'framework' => $s->framework, 'code' => $s->code, 'name' => $s->name,
            'phase' => $s->phase, 'grade_from' => $s->grade_from, 'grade_to' => $s->grade_to,
            'version' => $s->version, 'source_url' => $s->source_url, 'effective_on' => $s->effective_on->toDateString(),
            'component_weights' => $s->component_weights];
    }
}
