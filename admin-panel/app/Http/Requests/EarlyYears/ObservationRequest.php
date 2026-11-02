<?php

declare(strict_types=1);

namespace App\Http\Requests\EarlyYears;

use App\Services\EarlyYears\EarlyYearsAuthority;
use App\Services\EarlyYears\Observations;

class ObservationRequest extends EarlyYearsRequest
{
    public function authorize(): bool
    {
        app(EarlyYearsAuthority::class)->learnerWrite($this->user(), (int) $this->route('learner'));

        return true;
    }

    public function rules(): array
    {
        return [
            'operation_id' => ['required', 'uuid'],
            'development_area_id' => ['required', 'integer', 'min:1'],
            'observed_on' => ['required', 'date_format:Y-m-d'],
            'note' => ['required_without:photo', 'nullable', 'string', 'max:'.Observations::NOTE_MAX],
            'photo' => ['nullable', 'file', 'mimes:jpeg,png', 'extensions:jpg,jpeg,png', 'max:4096'],
        ];
    }

    public function messages(): array
    {
        return ['note.required_without' => __('Write a note or add a photograph.')];
    }
}
