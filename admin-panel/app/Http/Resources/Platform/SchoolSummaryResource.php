<?php

declare(strict_types=1);

namespace App\Http\Resources\Platform;

use App\Services\Platform\SchoolProfile;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A school in a list: the user's schools, a child's school (openapi.yaml,
 * SchoolSummary).
 *
 * @property SchoolProfile $resource
 */
class SchoolSummaryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'name' => $this->resource->name,
            'short_name' => $this->resource->shortName,
            'crest_url' => $this->resource->crestUrl,
        ];
    }
}
