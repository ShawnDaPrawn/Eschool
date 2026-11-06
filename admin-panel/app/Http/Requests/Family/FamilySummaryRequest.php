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
            // Quarter hours only: the job runs every 15 minutes (FamilySummaryPreferences::SLOTS).
            'send_at' => ['required', 'date_format:H:i', 'regex:/^([01]\d|2[0-3]):(00|15|30|45)$/'],
            'timezone' => ['sometimes', 'nullable', 'timezone:all'],
        ] : [];
    }

    public function messages(): array
    {
        return ['send_at.regex' => __('Choose a time on the quarter hour, such as 17:30.')];
    }
}
