<?php

declare(strict_types=1);

namespace App\Http\Resources\Platform;

use App\Models\SessionYear;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

/**
 * A school year with its terms (the semesters table; openapi.yaml,
 * SessionYear).
 *
 * @property SessionYear $resource
 */
class SessionYearResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->resource->id,
            'name' => (string) $this->resource->name,
            'start_date' => self::date($this->resource->start_date),
            'end_date' => self::date($this->resource->end_date),
            'terms' => TermResource::collection($this->resource->semesters),
        ];
    }

    /** A date column as Y-m-d, whether the model casts it or not. */
    public static function date(mixed $value): ?string
    {
        return $value === null || $value === '' ? null : Carbon::parse($value)->toDateString();
    }
}
