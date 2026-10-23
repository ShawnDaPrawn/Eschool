<?php

declare(strict_types=1);

namespace App\Http\Requests\Platform;

use App\Services\Platform\Notifications\NotificationRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class NotificationPreferenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && (int) $this->user()->status === 1;
    }

    public function rules(): array
    {
        return $this->isMethod('GET') ? [] : [
            'type' => ['required', 'string', 'max:80'],
            'channel' => ['required', Rule::in(NotificationRules::CHANNELS)],
            'enabled' => ['required', 'boolean'],
        ];
    }
}
