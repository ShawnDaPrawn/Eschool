<?php

declare(strict_types=1);

namespace App\Http\Requests\EarlyYears;

use App\Models\EarlyYears\Observation;
use App\Services\EarlyYears\EarlyYearsAuthority;
use App\Services\EarlyYears\Observations;

/** Withdrawing an observation, or raising an early concern from it. */
class ObservationActionRequest extends EarlyYearsRequest
{
    public function authorize(): bool
    {
        $observation = Observation::whereNull('withdrawn_at')->findOrFail((int) $this->route('observation'));
        app(EarlyYearsAuthority::class)->learnerWrite($this->user(), (int) $observation->learner_id);

        return true;
    }

    public function rules(): array
    {
        if ($this->routeIs('early-years.observations.withdraw')) {
            return ['revision' => ['required', 'integer', 'min:1']];
        }

        return [
            'concern' => ['required', 'string', 'min:10', 'max:'.Observations::CONCERN_MAX],
            'owner_id' => ['required', 'integer', 'min:1'],
            'review_on' => ['required', 'date_format:Y-m-d'],
        ];
    }
}
