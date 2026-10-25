<?php

declare(strict_types=1);

namespace App\Http\Resources\Wellbeing;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WellbeingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return json_decode(json_encode($this->resource, JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);
    }
}
