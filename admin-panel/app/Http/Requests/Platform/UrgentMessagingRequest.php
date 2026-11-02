<?php

declare(strict_types=1);

namespace App\Http\Requests\Platform;

use App\Services\Platform\Notifications\UrgentMessaging;
use Illuminate\Foundation\Http\FormRequest;

/** The school's SMS and WhatsApp opt-in (Action Plan 2.7). UrgentMessaging validates again. */
class UrgentMessagingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return UrgentMessaging::mayManage($this->user());
    }

    public function rules(): array
    {
        if ($this->isMethod('GET')) {
            return [];
        }

        return [
            'revision' => ['required', 'integer', 'min:0'],
            'sms_enabled' => ['required', 'boolean'],
            'whatsapp_enabled' => ['required', 'boolean'],
            'sms_types' => ['nullable', 'array'],
            'sms_types.*' => ['string', 'max:80'],
            'whatsapp_types' => ['nullable', 'array'],
            'whatsapp_types.*' => ['string', 'max:80'],
            'whatsapp_consent' => ['nullable', 'boolean'],
        ];
    }
}
