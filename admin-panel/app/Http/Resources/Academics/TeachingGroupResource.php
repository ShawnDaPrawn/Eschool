<?php

declare(strict_types=1);

namespace App\Http\Resources\Academics;

use App\Services\Academics\TeachingGroups;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TeachingGroupResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return app(TeachingGroups::class)->groupData($this->resource);
    }
}
