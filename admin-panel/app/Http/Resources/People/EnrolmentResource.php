<?php

declare(strict_types=1);

namespace App\Http\Resources\People;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EnrolmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->resource->id, 'learner_id' => (int) $this->resource->learner_id,
            'class_section_id' => (int) $this->resource->class_section_id, 'session_year_id' => (int) $this->resource->session_year_id,
            'starts_on' => $this->resource->starts_on->toDateString(), 'ends_on' => $this->resource->ends_on?->toDateString(),
            'reason' => $this->resource->reason, 'legacy_dates' => (bool) $this->resource->legacy_dates,
            'previous_enrolment_id' => $this->resource->previous_enrolment_id === null ? null : (int) $this->resource->previous_enrolment_id,
        ];
    }
}
