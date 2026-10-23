<?php

declare(strict_types=1);

namespace App\Http\Requests\People;

use Illuminate\Foundation\Http\FormRequest;

class EnrolmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        if ($this->isMethod('GET')) {
            return ['date' => ['nullable', 'date_format:Y-m-d'], 'page' => ['sometimes', 'integer', 'min:1'], 'per_page' => ['sometimes', 'integer', 'min:1', 'max:100']];
        }

        return [
            'class_section_id' => ['required', 'integer', 'exists:class_sections,id'],
            'session_year_id' => ['required', 'integer', 'exists:session_years,id'],
            'starts_on' => ['required', 'date_format:Y-m-d'],
            'reason' => ['required', 'string', 'min:3', 'max:255'],
        ];
    }
}
