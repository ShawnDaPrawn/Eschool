<?php

declare(strict_types=1);

namespace App\Http\Resources\Academics;

use App\Services\Academics\TeachingGroupQueries;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AcademicAssignmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return app(TeachingGroupQueries::class)->rowData($this->resource, (string) $request->route('kind'));
    }
}
