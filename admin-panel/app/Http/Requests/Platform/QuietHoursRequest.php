<?php

declare(strict_types=1);

namespace App\Http\Requests\Platform;

use Illuminate\Foundation\Http\FormRequest;

class QuietHoursRequest extends FormRequest
{
    /** Anyone signed in sets their own quiet hours. */
    public function authorize(): bool
    {
        return $this->user() !== null && (int) $this->user()->status === 1;
    }

    public function rules(): array
    {
        return $this->isMethod('GET') ? [] : [
            'enabled' => ['required', 'boolean'],
            'starts_at' => ['required', 'date_format:H:i'],
            'ends_at' => ['required', 'date_format:H:i', 'different:starts_at'],
            'timezone' => ['sometimes', 'nullable', 'timezone:all'],
        ];
    }
}
