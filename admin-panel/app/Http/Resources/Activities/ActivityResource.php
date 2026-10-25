<?php

declare(strict_types=1);

namespace App\Http\Resources\Activities;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ActivityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return $this->resource;
    }
}
