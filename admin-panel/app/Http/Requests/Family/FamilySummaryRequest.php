<?php

declare(strict_types=1);

namespace App\Http\Requests\Family;

use Illuminate\Foundation\Http\FormRequest;

/** A guardian's own daily family summary (Action Plan 5.11); the service checks they are a guardian here. */
class FamilySummaryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && (int) $this->user()->status === 1;
    }

    public function rules(): array
    {
        return $this->isMethod('PUT') ? [
            'enabled' => ['required', 'boolean'],
            'send_at' => ['required', 'date_format:H:i'],
            'timezone' => ['sometimes', 'nullable', 'timezone:all'],
        ] : [];
    }
}
